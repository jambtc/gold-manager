<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Contract;
use app\models\Competition;
use app\models\Player;
use app\models\Team;
use app\models\Transfer;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class PlayerController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionView(int $id, string $back = ''): string
    {
        $player = Player::findOne($id);
        if (!$player) {
            throw new NotFoundHttpException('Giocatore non trovato.');
        }

        $contract  = Contract::findOne(['player_id' => $id, 'status' => Contract::STATUS_ACTIVE]);
        $myTeam    = Team::findOne(['user_id' => Yii::$app->user->id]);
        $isMyPlayer = $myTeam && $player->team_id === $myTeam->id;
        $season = (int) Competition::find()->where(['!=', 'type', 'friendly'])->max('season');
        if ($season <= 0) {
            $season = 1;
        }

        $seasonStats = Yii::$app->db->createCommand(
            'SELECT
                COUNT(ps.id) AS matches,
                COALESCE(SUM(ps.minutes_played), 0) AS minutes_played,
                COALESCE(SUM(ps.goals), 0) AS goals,
                COALESCE(SUM(ps.assists), 0) AS assists,
                COALESCE(SUM(ps.yellow_cards), 0) AS yellow_cards,
                COALESCE(SUM(ps.red_cards), 0) AS red_cards,
                COALESCE(SUM(ps.saves), 0) AS saves,
                COALESCE(SUM(ps.clean_sheet), 0) AS clean_sheets
             FROM {{%player_stat}} ps
             JOIN {{%fixture}} f ON f.id = ps.fixture_id
             JOIN {{%competition}} c ON c.id = f.competition_id
             WHERE ps.player_id = :playerId
               AND c.type <> "friendly"
               AND c.season = :season',
            [':playerId' => $player->id, ':season' => $season]
        )->queryOne();

        $activeTransfer = Transfer::find()
            ->where(['player_id' => $id])
            ->andWhere(['status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE]])
            ->one();

        // SIP-0068: quadrant/cell experience heatmaps
        $quadrantHeatmap = $player->getQuadrantHeatmap();
        $cellHeatmap     = $player->getCellHeatmap();

        // Prev/next navigation ordered GK → DF → MF → FW, then by number
        $teammates = Player::find()
            ->where(['team_id' => $player->team_id])
            ->orderBy(new \yii\db\Expression("FIELD(position,'GK','DF','MF','FW'), number ASC, name ASC"))
            ->select('id')
            ->column();
        $idx = array_search($player->id, $teammates);
        $prevPlayerId = ($idx !== false && $idx > 0) ? (int) $teammates[$idx - 1] : null;
        $nextPlayerId = ($idx !== false && $idx < count($teammates) - 1) ? (int) $teammates[$idx + 1] : null;

        return $this->render('view', [
            'player'           => $player,
            'contract'         => $contract,
            'valuator'         => Yii::$app->playerValuator,
            'myTeam'           => $myTeam,
            'isMyPlayer'       => $isMyPlayer,
            'seasonStats'      => $seasonStats,
            'season'           => $season,
            'backUrl'          => $back,
            'activeTransfer'   => $activeTransfer,
            'quadrantHeatmap'  => $quadrantHeatmap,
            'cellHeatmap'      => $cellHeatmap,
            'prevPlayerId'     => $prevPlayerId,
            'nextPlayerId'     => $nextPlayerId,
        ]);
    }
}
