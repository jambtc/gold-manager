<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Competition;
use app\models\Standing;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class StatsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionScorers(): string|Response
    {
        return $this->renderRanking('scorers');
    }

    public function actionAssists(): string|Response
    {
        return $this->renderRanking('assists');
    }

    public function actionKeepers(): string|Response
    {
        [$season, $tier, $allSeasons, $country] = $this->resolveFilters();
        $tierOptions = Competition::TIER_NAMES;

        $params = [':season' => $season];
        $extraSql = '';
        if ($tier !== null) {
            $extraSql .= ' AND c.tier = :tier ';
            $params[':tier'] = $tier;
        }
        if ($country !== 'ALL') {
            $extraSql .= ' AND c.country_code = :cc ';
            $params[':cc'] = $country;
        }

        $rows = Yii::$app->db->createCommand(
            'SELECT
                p.id AS player_id,
                p.name AS player_name,
                t.name AS team_name,
                SUM(ps.minutes_played) AS minutes_played,
                COUNT(ps.id) AS matches,
                SUM(
                    CASE
                      WHEN t.id = f.home_team_id THEN f.away_score
                      WHEN t.id = f.away_team_id THEN f.home_score
                      ELSE 0
                    END
                ) AS goals_conceded,
                SUM(ps.saves) AS saves,
                SUM(ps.clean_sheet) AS clean_sheets
             FROM {{%player_stat}} ps
             JOIN {{%player}} p ON p.id = ps.player_id
             JOIN {{%team}} t ON t.id = ps.team_id
             JOIN {{%fixture}} f ON f.id = ps.fixture_id
             JOIN {{%competition}} c ON c.id = f.competition_id
             WHERE c.type <> "friendly"
               AND c.season = :season
               AND p.position = "GK"
               ' . $extraSql . '
             GROUP BY p.id, p.name, t.name
             HAVING minutes_played > 0
             ORDER BY clean_sheets DESC, saves DESC, goals_conceded ASC, minutes_played DESC'
            ,
            $params
        )->queryAll();

        if (count($rows) > 20) {
            $cutoff = (int) ($rows[19]['clean_sheets'] ?? 0);
            $rows = array_values(array_filter($rows, fn($r) => (int)$r['clean_sheets'] >= $cutoff));
        }

        return $this->render('keepers', [
            'rows' => $rows,
            'season' => $season,
            'tier' => $tier,
            'tierOptions' => $tierOptions,
            'allSeasons' => $allSeasons,
            'country' => $country,
        ]);
    }

    public function actionTeam(): string|Response
    {
        [$season, $tier, $allSeasons, $country] = $this->resolveFilters();
        $tierOptions = Competition::TIER_NAMES;

        $query = Standing::find()
            ->alias('s')
            ->joinWith(['competition c', 'team t'])
            ->where(['c.season' => $season])
            ->andWhere(['!=', 'c.type', 'friendly']);

        if ($tier !== null) {
            $query->andWhere(['c.tier' => $tier]);
        }
        if ($country !== 'ALL') {
            $query->andWhere(['c.country_code' => $country]);
        }

        $rows = $query
            ->orderBy([
                's.points' => SORT_DESC,
                's.goals_for' => SORT_DESC,
                's.goals_against' => SORT_ASC,
            ])
            ->all();

        return $this->render('team', [
            'rows' => $rows,
            'season' => $season,
            'tier' => $tier,
            'tierOptions' => $tierOptions,
            'allSeasons' => $allSeasons,
            'country' => $country,
        ]);
    }

    private function renderRanking(string $mode): string|Response
    {
        [$season, $tier, $allSeasons, $country] = $this->resolveFilters();
        $tierOptions = Competition::TIER_NAMES;
        $sortField = $mode === 'assists' ? 'assists' : 'goals';

        $params = [':season' => $season];
        $extraSql = '';
        if ($tier !== null) {
            $extraSql .= ' AND c.tier = :tier ';
            $params[':tier'] = $tier;
        }
        if ($country !== 'ALL') {
            $extraSql .= ' AND c.country_code = :cc ';
            $params[':cc'] = $country;
        }

        $rows = Yii::$app->db->createCommand(
            'SELECT
                p.id AS player_id,
                p.name AS player_name,
                p.position AS position,
                t.name AS team_name,
                SUM(ps.goals) AS goals,
                SUM(ps.assists) AS assists,
                SUM(ps.yellow_cards) AS yellow_cards,
                SUM(ps.red_cards) AS red_cards,
                COUNT(ps.id) AS matches
             FROM {{%player_stat}} ps
             JOIN {{%player}} p ON p.id = ps.player_id
             JOIN {{%team}} t ON t.id = ps.team_id
             JOIN {{%fixture}} f ON f.id = ps.fixture_id
             JOIN {{%competition}} c ON c.id = f.competition_id
             WHERE c.type <> "friendly"
               AND c.season = :season
               ' . $extraSql . '
             GROUP BY p.id, p.name, p.position, t.name
             HAVING ' . $sortField . ' > 0
             ORDER BY ' . $sortField . ' DESC, goals DESC, assists DESC, matches ASC'
            ,
            $params
        )->queryAll();

        if (count($rows) > 20) {
            $cutoff = (int) ($rows[19][$sortField] ?? 0);
            $rows = array_values(array_filter($rows, fn($r) => (int)$r[$sortField] >= $cutoff));
        }

        return $this->render($mode === 'assists' ? 'assists' : 'scorers', [
            'rows' => $rows,
            'season' => $season,
            'tier' => $tier,
            'tierOptions' => $tierOptions,
            'allSeasons' => $allSeasons,
            'country' => $country,
        ]);
    }

    /**
     * Returns the country_code filter to apply to stats queries.
     * Defaults to the logged-in user's country; 'ALL' shows every country.
     */
    private function resolveCountry(): string
    {
        $req = Yii::$app->request->get('country', '');
        if ($req === 'ALL') {
            return 'ALL';
        }
        if ($req !== '' && \app\components\CountryContext::normalize($req) === $req) {
            return $req;
        }
        if (!Yii::$app->user->isGuest) {
            /** @var \app\models\User $identity */
            $identity = Yii::$app->user->identity;
            return \app\components\CountryContext::normalize((string) ($identity->country_code ?? 'IT'));
        }
        return 'ALL';
    }

    /**
     * @return array{0:int,1:int|null,2:int[],3:string}
     */
    private function resolveFilters(): array
    {
        $country = $this->resolveCountry();

        $query = Competition::find()
            ->select('season')
            ->where(['!=', 'type', 'friendly'])
            ->distinct()
            ->orderBy(['season' => SORT_DESC]);
        if ($country !== 'ALL') {
            $query->andWhere(['country_code' => $country]);
        }
        $allSeasons = $query->column();

        $latestSeason = !empty($allSeasons) ? (int) $allSeasons[0] : 1;
        $season = (int) Yii::$app->request->get('season', $latestSeason);
        if (!in_array($season, array_map('intval', $allSeasons), true)) {
            $season = $latestSeason;
        }

        $tierRaw = Yii::$app->request->get('tier', '');
        $tier = $tierRaw === '' ? null : (int) $tierRaw;
        if ($tier !== null && !array_key_exists($tier, Competition::TIER_NAMES)) {
            $tier = null;
        }

        return [$season, $tier, array_map('intval', $allSeasons), $country];
    }
}
