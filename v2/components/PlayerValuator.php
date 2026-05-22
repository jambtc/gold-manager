<?php

declare(strict_types=1);

namespace app\components;

use app\models\Contract;
use app\models\Player;
use yii\base\Component;

/**
 * Calculates a player's market value based on:
 *  - General skill level
 *  - Age (peak 24-29, decline after 30, youth premium for ≤21)
 *  - Position scarcity multiplier
 *  - Form, freshness, condition
 *  - Experience
 *
 * Formula inspired by the legacy `mercato.stipendio` / `mercato.valore` fields.
 */
class PlayerValuator extends Component
{
    /** Base value in € per skill point */
    public int $basePerSkill = 50_000;

    /**
     * Compute market value in €.
     */
    public function marketValue(Player $player): int
    {
        $base = $player->general_skill * $this->basePerSkill;

        // ── Age curve ───────────────────────────────────────────────────
        $base *= $this->ageFactor($player->age);

        // ── Position scarcity ───────────────────────────────────────────
        $base *= $this->positionFactor($player->position);

        // ── Physical condition ──────────────────────────────────────────
        $condMult = ($player->form / 100) * 0.2
                  + ($player->freshness / 100) * 0.1
                  + ($player->condition / 100) * 0.1
                  + 0.60; // min 60 % of base
        $base *= $condMult;

        // ── Experience bonus (up to +15 %) ─────────────────────────────
        $expBonus = min(0.15, $player->experience / 1000);
        $base    *= (1 + $expBonus);

        return (int) round($base, -3); // round to nearest 1000
    }

    /**
     * Suggest a fair weekly/seasonal salary based on market value.
     * Rule of thumb: salary ≈ 10 % of market value per season.
     */
    public function suggestedSalary(Player $player): int
    {
        return (int) round($this->marketValue($player) * 0.10, -2);
    }

    // ───────────────────────────────────────────────────────────────────

    private function ageFactor(int $age): float
    {
        return match (true) {
            $age <= 18 => 0.65,   // youth, high potential but unproven
            $age <= 21 => 0.85,   // promising
            $age <= 23 => 0.95,   // developing
            $age <= 29 => 1.00,   // peak
            $age <= 31 => 0.85,   // slight decline
            $age <= 33 => 0.65,   // clear decline
            $age <= 35 => 0.40,   // veteran, last legs
            default    => 0.20,   // ancient
        };
    }

    private function positionFactor(string $position): float
    {
        return match (strtoupper($position)) {
            'PO'              => 0.90,  // GK cheaper historically
            'DS', 'DD'        => 0.95,  // full-backs
            'D'               => 1.00,  // central defenders
            'CS', 'CD'        => 1.00,  // wide midfielders
            'C'               => 1.10,  // central midfielders (most coveted)
            'AS', 'AD'        => 1.05,  // wide forwards
            'A'               => 1.15,  // strikers (most expensive)
            default           => 1.00,
        };
    }
}
