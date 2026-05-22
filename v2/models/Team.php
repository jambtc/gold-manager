<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "team".
 *
 * @property int         $id
 * @property string      $name
 * @property int|null    $user_id
 * @property int|null    $is_cpu
 * @property int         $budget
 * @property string|null $logo    LONGBLOB
 * @property string|null $color_left
 * @property string|null $color_right
 * @property int         $created_at
 * @property int         $updated_at
 *
 * @property User|null    $user
 * @property Player[]     $players
 * @property Formation[]  $formations
 * @property Staff[]      $staff
 * @property Stadium|null $stadium
 * @property Contract[]   $contracts
 * @property Transfer[]   $transfersOut
 * @property Transfer[]   $transfersIn
 * @property Fixture[]    $homeFixtures
 * @property Fixture[]    $awayFixtures
 * @property Standing[]   $standings
 */
class Team extends ActiveRecord
{
    public const DEFAULT_COLOR_LEFT = '#f59e0b';
    public const DEFAULT_COLOR_RIGHT = '#dc2626';
    private const UI_COLOR_PALETTE = [
        '#f59e0b', '#dc2626', '#2563eb', '#7c3aed', '#059669',
        '#ea580c', '#0891b2', '#be123c', '#4f46e5', '#16a34a',
    ];

    public static function tableName(): string { return '{{%team}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['user_id', 'is_cpu', 'budget'], 'integer'],
            [['logo'], 'string'],
            [['color_left', 'color_right'], 'string', 'max' => 7],
            [['color_left', 'color_right'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'skipOnEmpty' => true],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Name',
            'user_id'    => 'Manager',
            'is_cpu'     => 'CPU Team',
            'budget'     => 'Budget (€)',
            'logo'       => 'Logo',
            'color_left' => 'Colore SX',
            'color_right'=> 'Colore DX',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function sanitizeHexColor(?string $value, string $fallback): string
    {
        $value = strtoupper(trim((string) $value));
        if (preg_match('/^#[0-9A-F]{6}$/', $value) === 1) {
            return $value;
        }
        return strtoupper($fallback);
    }

    public static function randomUiColors(): array
    {
        $pool = self::UI_COLOR_PALETTE;
        shuffle($pool);
        $left = $pool[0] ?? self::DEFAULT_COLOR_LEFT;
        $right = $pool[1] ?? self::DEFAULT_COLOR_RIGHT;
        if ($left === $right) {
            $right = self::DEFAULT_COLOR_RIGHT;
        }
        return [strtoupper($left), strtoupper($right)];
    }

    public function ensureUiColors(bool $save = true): void
    {
        $left = self::sanitizeHexColor($this->color_left, self::DEFAULT_COLOR_LEFT);
        $right = self::sanitizeHexColor($this->color_right, self::DEFAULT_COLOR_RIGHT);

        if ($left === strtoupper(self::DEFAULT_COLOR_LEFT) && $this->color_left === null) {
            [$left, $candidateRight] = self::randomUiColors();
            if ($this->color_right === null) {
                $right = $candidateRight;
            }
        }

        if ($right === $left) {
            foreach (self::UI_COLOR_PALETTE as $candidate) {
                $candidate = strtoupper($candidate);
                if ($candidate !== $left) {
                    $right = $candidate;
                    break;
                }
            }
        }

        $dirty = ($this->color_left !== $left) || ($this->color_right !== $right);
        $this->color_left = $left;
        $this->color_right = $right;

        if ($save && !$this->getIsNewRecord() && $dirty) {
            $this->save(false, ['color_left', 'color_right']);
        }
    }

    // ── Domain helpers ─────────────────────────────────────────────────

    /**
     * Total wage bill per season: sum of all active player contracts + staff salaries.
     */
    public function totalWageBill(): int
    {
        $playerWages = (int) Contract::find()
            ->where(['team_id' => $this->id, 'status' => Contract::STATUS_ACTIVE])
            ->sum('salary');

        $staffWages = (int) Staff::find()
            ->where(['team_id' => $this->id])
            ->sum('salary');

        return $playerWages + $staffWages;
    }

    // ── Relations ───────────────────────────────────────────────────────

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPlayers()
    {
        return $this->hasMany(Player::class, ['team_id' => 'id']);
    }

    public function getFormations()
    {
        return $this->hasMany(Formation::class, ['team_id' => 'id']);
    }

    public function getStaff()
    {
        return $this->hasMany(Staff::class, ['team_id' => 'id']);
    }

    public function getStadium()
    {
        return $this->hasOne(Stadium::class, ['team_id' => 'id']);
    }

    public function getContracts()
    {
        return $this->hasMany(Contract::class, ['team_id' => 'id']);
    }

    public function getTransfersOut()
    {
        return $this->hasMany(Transfer::class, ['from_team_id' => 'id']);
    }

    public function getTransfersIn()
    {
        return $this->hasMany(Transfer::class, ['to_team_id' => 'id']);
    }

    public function getHomeFixtures()
    {
        return $this->hasMany(Fixture::class, ['home_team_id' => 'id']);
    }

    public function getAwayFixtures()
    {
        return $this->hasMany(Fixture::class, ['away_team_id' => 'id']);
    }

    public function getStandings()
    {
        return $this->hasMany(Standing::class, ['team_id' => 'id']);
    }
}
