<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int      $id
 * @property int      $player_id
 * @property int|null $from_team_id
 * @property int|null $to_team_id
 * @property int|null $offered_by_team
 * @property int      $fee
 * @property int|null $asking_fee
 * @property int      $proposed_salary
 * @property string   $status          listed|bid_made|accepted|rejected|completed|cancelled
 * @property string   $transfer_type   sale|loan|free
 * @property int|null $loan_return_season
 * @property int|null $loan_ends_at       Unix ts when loan expires (SIP-0078)
 * @property int      $listed_at
 * @property int|null $resolved_at
 *
 * @property Player   $player
 * @property Team|null $fromTeam
 * @property Team|null $toTeam
 */
class Transfer extends ActiveRecord
{
    public const TYPE_SALE = 'sale';
    public const TYPE_LOAN = 'loan';
    public const TYPE_FREE = 'free';

    const STATUS_LISTED    = 'listed';
    const STATUS_BID_MADE  = 'bid_made';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function tableName(): string { return '{{%transfer}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['player_id', 'listed_at'], 'required'],
            [['player_id', 'from_team_id', 'to_team_id', 'offered_by_team',
              'fee', 'asking_fee', 'proposed_salary', 'listed_at', 'resolved_at',
              'loan_return_season', 'loan_ends_at'], 'integer'],
            [['status', 'transfer_type'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_LISTED, self::STATUS_BID_MADE, self::STATUS_ACCEPTED,
                self::STATUS_REJECTED, self::STATUS_COMPLETED, self::STATUS_CANCELLED,
            ]],
            [['transfer_type'], 'in', 'range' => [
                self::TYPE_SALE,
                self::TYPE_LOAN,
                self::TYPE_FREE,
            ]],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'player_id'       => 'Player',
            'from_team_id'    => 'Selling Team',
            'to_team_id'      => 'Buying Team',
            'offered_by_team' => 'Bid By',
            'fee'             => 'Transfer Fee (€)',
            'asking_fee'      => 'Asking Fee (€)',
            'proposed_salary' => 'Proposed Salary (€)',
            'status'          => 'Status',
            'transfer_type'   => 'Transfer Type',
            'loan_return_season' => 'Loan Return Season',
            'loan_ends_at'       => 'Loan Ends At',
            'listed_at'       => 'Listed At',
            'resolved_at'     => 'Resolved At',
        ];
    }

    /**
     * Complete a transfer: move player, deduct/credit budgets, update contract.
     */
    public function complete(int $currentSeason): bool
    {
        if ($this->status !== self::STATUS_ACCEPTED) {
            return false;
        }

        $player   = $this->player;
        $fromTeam = $this->fromTeam;
        $toTeam   = $this->toTeam;

        if (!$player || !$toTeam) return false;

        if ($this->transfer_type === self::TYPE_FREE) {
            $player->team_id = $toTeam->id;
            $player->save(false);

            Contract::updateAll(['status' => Contract::STATUS_TRANSFERRED], [
                'player_id' => $player->id,
                'status' => Contract::STATUS_ACTIVE,
            ]);

            $contract = new Contract();
            $contract->player_id = $player->id;
            $contract->team_id = $toTeam->id;
            $contract->salary = max(0, (int) $this->proposed_salary);
            $contract->season_start = $currentSeason;
            $contract->season_end = $currentSeason + 1;
            $contract->status = Contract::STATUS_ACTIVE;
            $contract->save(false);
        } elseif ($this->transfer_type === self::TYPE_LOAN) {
            $player->team_id = $toTeam->id;
            $player->save(false);

            if ($this->fee > 0) {
                $toTeam->budget -= $this->fee;
                $toTeam->save(false);
                if ($fromTeam) {
                    $fromTeam->budget += $this->fee;
                    $fromTeam->save(false);
                }
            }
        } else {
            $player->team_id = $toTeam->id;
            $player->save(false);

            $toTeam->budget -= $this->fee;
            $toTeam->save(false);

            if ($fromTeam) {
                $fromTeam->budget += $this->fee;
                $fromTeam->save(false);
            }

            Contract::updateAll(['status' => Contract::STATUS_TRANSFERRED], [
                'player_id' => $player->id,
                'status' => Contract::STATUS_ACTIVE,
            ]);

            $contract = new Contract();
            $contract->player_id = $player->id;
            $contract->team_id = $toTeam->id;
            $contract->salary = max(0, (int) $this->proposed_salary);
            $contract->season_start = $currentSeason;
            $contract->season_end = $currentSeason + 2; // default 3-season deal
            $contract->status = Contract::STATUS_ACTIVE;
            $contract->save(false);
        }

        $this->status      = self::STATUS_COMPLETED;
        $this->resolved_at = time();
        $this->save(false);

        return true;
    }

    /** Whether this loan has expired (loan_ends_at in the past). */
    public function isLoanExpired(): bool
    {
        return $this->transfer_type === self::TYPE_LOAN
            && $this->loan_ends_at !== null
            && $this->loan_ends_at <= time();
    }

    public function getPlayer()   { return $this->hasOne(Player::class, ['id' => 'player_id']);    }
    public function getFromTeam() { return $this->hasOne(Team::class,   ['id' => 'from_team_id']); }
    public function getToTeam()   { return $this->hasOne(Team::class,   ['id' => 'to_team_id']);   }
    public function getOffers()   { return $this->hasMany(TransferOffer::class, ['transfer_id' => 'id']); }
}
