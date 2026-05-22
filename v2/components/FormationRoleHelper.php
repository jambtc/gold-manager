<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;

class FormationRoleHelper
{
    public const ROLE_CAPTAIN  = 'captain';
    public const ROLE_PENALTY  = 'penalty';
    public const ROLE_FREEKICK = 'freekick';
    public const ROLE_CORNER   = 'corner';

    /**
     * @return array<string, array{
     *   manual_id:int|null,
     *   manual_player:Player|null,
     *   auto_id:int|null,
     *   auto_player:Player|null,
     *   effective_id:int|null,
     *   effective_player:Player|null,
     *   is_auto:bool
     * }>
     */
    public function resolveRolePlayers(Formation $formation): array
    {
        $starters = $this->loadStarters($formation);
        $byId = [];
        foreach ($starters as $starter) {
            $byId[(int) $starter->id] = $starter;
        }

        return [
            self::ROLE_CAPTAIN  => $this->resolveOneRole($formation, self::ROLE_CAPTAIN,  $starters, $byId),
            self::ROLE_PENALTY  => $this->resolveOneRole($formation, self::ROLE_PENALTY,  $starters, $byId),
            self::ROLE_FREEKICK => $this->resolveOneRole($formation, self::ROLE_FREEKICK, $starters, $byId),
            self::ROLE_CORNER   => $this->resolveOneRole($formation, self::ROLE_CORNER,   $starters, $byId),
        ];
    }

    public function resolveCaptain(Formation $formation): ?Player
    {
        $resolved = $this->resolveRolePlayers($formation);
        return $resolved[self::ROLE_CAPTAIN]['effective_player'];
    }

    public function resolvePenaltyTaker(Formation $formation): ?Player
    {
        $resolved = $this->resolveRolePlayers($formation);
        return $resolved[self::ROLE_PENALTY]['effective_player'];
    }

    public function resolveFreeKickTaker(Formation $formation): ?Player
    {
        $resolved = $this->resolveRolePlayers($formation);
        return $resolved[self::ROLE_FREEKICK]['effective_player'];
    }

    public function resolveCornerTaker(Formation $formation): ?Player
    {
        $resolved = $this->resolveRolePlayers($formation);
        return $resolved[self::ROLE_CORNER]['effective_player'];
    }

    public function precisionCapForSetPiece(Formation $formation, string $setPieceType): int
    {
        $taker = null;
        if ($setPieceType === 'penalty') {
            $taker = $this->resolvePenaltyTaker($formation);
        } elseif ($setPieceType === 'freekick') {
            $taker = $this->resolveFreeKickTaker($formation);
        }

        if (!$taker) {
            return 30;
        }

        $bonus = (($taker->skill_tc * 0.2) + ($taker->skill_tr * 0.3)) / 10;
        return min(60, 30 + (int) $bonus);
    }

    /**
     * @param Player[] $starters
     * @param array<int, Player> $byId
     * @return array{
     *   manual_id:int|null,
     *   manual_player:Player|null,
     *   auto_id:int|null,
     *   auto_player:Player|null,
     *   effective_id:int|null,
     *   effective_player:Player|null,
     *   is_auto:bool
     * }
     */
    private function resolveOneRole(Formation $formation, string $role, array $starters, array $byId): array
    {
        $field = $this->roleField($role);
        $manualId = $formation->$field ? (int) $formation->$field : null;
        $manualPlayer = $manualId && isset($byId[$manualId]) ? $byId[$manualId] : null;
        $autoPlayer = $this->pickBest($role, $starters);
        $autoId = $autoPlayer ? (int) $autoPlayer->id : null;

        if ($manualPlayer) {
            return [
                'manual_id' => $manualId,
                'manual_player' => $manualPlayer,
                'auto_id' => $autoId,
                'auto_player' => $autoPlayer,
                'effective_id' => $manualId,
                'effective_player' => $manualPlayer,
                'is_auto' => false,
            ];
        }

        return [
            'manual_id' => null,
            'manual_player' => null,
            'auto_id' => $autoId,
            'auto_player' => $autoPlayer,
            'effective_id' => $autoId,
            'effective_player' => $autoPlayer,
            'is_auto' => $autoPlayer !== null,
        ];
    }

    /**
     * @return Player[]
     */
    public function loadStarters(Formation $formation): array
    {
        $slots = $formation->isRelationPopulated('slots')
            ? $formation->slots
            : FormationSlot::find()
                ->where(['formation_id' => $formation->id])
                ->andWhere(['<=', 'zone', 63])
                ->with('player')
                ->all();

        $starters = [];
        foreach ($slots as $slot) {
            if (!$slot->player || (int) $slot->zone > 63) {
                continue;
            }
            $starters[] = $slot->player;
        }
        return $starters;
    }

    private function roleField(string $role): string
    {
        return match ($role) {
            self::ROLE_CAPTAIN  => 'captain_player_id',
            self::ROLE_PENALTY  => 'penalty_player_id',
            self::ROLE_FREEKICK => 'freekick_player_id',
            self::ROLE_CORNER   => 'corner_player_id',
            default             => '',
        };
    }

    /**
     * @param Player[] $starters
     */
    private function pickBest(string $role, array $starters): ?Player
    {
        $best = null;
        $bestScore = null;
        foreach ($starters as $player) {
            $score = match ($role) {
                self::ROLE_CAPTAIN => $this->captainScore($player),
                self::ROLE_PENALTY => $this->penaltyScore($player),
                self::ROLE_FREEKICK => $this->freeKickScore($player),
                self::ROLE_CORNER   => $this->cornerScore($player),
            default             => -INF,
            };
            if ($best === null || $score > $bestScore || ($score === $bestScore && (int) $player->id < (int) $best->id)) {
                $best = $player;
                $bestScore = $score;
            }
        }
        return $best;
    }

    private function captainScore(Player $player): float
    {
        // Experience counts 1.5× vs skill — veteran leadership matters.
        // charBonus is a multiplier on the combined base so experience
        // still influences the final score even for non-carismatico players.
        $charBonus = match (strtolower((string) $player->character)) {
            'carismatico' => 2.0,   // natural leader — strong multiplier
            'popolare'    => 1.30,  // well-liked
            'ambizioso'   => 1.10,  // wants the armband
            'introverso'  => 0.60,  // avoids the spotlight
            'razionale'   => 1.05,
            default       => 1.00,
        };

        $base = $player->general_skill + ($player->experience * 1.5);

        // Extra veteran bonus: players with experience > 70 get additional weight
        $veteranBonus = max(0.0, ($player->experience - 70) * 0.8);

        return $base * $charBonus + $veteranBonus;
    }

    private function penaltyScore(Player $player): float
    {
        $footBonus = match (strtoupper((string) $player->foot)) {
            'R' => 20.0,
            'L' => -20.0,
            'LR' => 0.0,
            default => 0.0,
        };
        $score = ($player->general_skill + ($player->skill_tc * 1.3) + $player->skill_tr) + $footBonus;
        if ($this->hasSkillHint($player, ['rigori', 'rigorista', 'penalty'])) {
            $score *= 2;
        }
        return $score;
    }

    private function freeKickScore(Player $player): float
    {
        $score = ($player->skill_tc * 1.3) + $player->skill_tr;
        if ($this->hasSkillHint($player, ['calcidipunizione', 'calci di punizione', 'punizioni', 'freekick'])) {
            $score *= 2;
        }
        return $score;
    }

    private function cornerScore(Player $player): float
    {
        // Corners: cross quality primary, shooting secondary
        $score = ($player->skill_cr * 1.5) + $player->skill_tr;
        if ($this->hasSkillHint($player, ['corner', 'angolo', 'cross', 'fascia'])) {
            $score *= 2;
        }
        return $score;
    }

    /**
     * Without a dedicated "special skill" column in v2, use character text hints.
     *
     * @param string[] $hints
     */
    private function hasSkillHint(Player $player, array $hints): bool
    {
        $value = strtolower((string) $player->character);
        if ($value === '') {
            return false;
        }
        foreach ($hints as $hint) {
            if (str_contains($value, strtolower($hint))) {
                return true;
            }
        }
        return false;
    }
}

