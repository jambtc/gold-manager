<?php

declare(strict_types=1);

namespace app\controllers;

use app\jobs\WorldSeedJob;
use app\models\ContactForm;
use app\models\LoginForm;
use app\models\RegisterForm;
use Yii;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Security;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        private readonly Security $security,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'register'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['register'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['pending'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'transparent' => true,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string|Response
    {
        $team         = null;
        $nextFixture  = null;
        $nextFriendlyFixture = null;
        $recentFixtures = [];
        $standing     = null;
        $competition  = null;
        $leagueTable  = [];

        if (!Yii::$app->user->isGuest) {
            /** @var \app\models\User $identity */
            $identity = Yii::$app->user->identity;

            if ($identity->isAdmin()) {
                return $this->redirect(['/admin/index']);
            }

            $team = \app\models\Team::findOne(['user_id' => $identity->id]);
            if ($team) {
                $team->ensureUiColors();
            }

            if ($team) {
                $nextFixture = \app\models\Fixture::find()
                    ->alias('f')
                    ->innerJoin(\app\models\Competition::tableName() . ' c', 'c.id = f.competition_id')
                    ->where(['or', ['home_team_id' => $team->id], ['away_team_id' => $team->id]])
                    ->andWhere(['f.status' => \app\models\Fixture::STATUS_SCHEDULED])
                    ->andWhere(['!=', 'c.type', 'friendly'])
                    ->orderBy(['match_date' => SORT_ASC])
                    ->one();

                $nextFriendlyFixture = \app\models\Fixture::find()
                    ->alias('f')
                    ->innerJoin(\app\models\Competition::tableName() . ' c', 'c.id = f.competition_id')
                    ->where(['or', ['home_team_id' => $team->id], ['away_team_id' => $team->id]])
                    ->andWhere(['f.status' => \app\models\Fixture::STATUS_SCHEDULED])
                    ->andWhere(['c.type' => 'friendly'])
                    ->orderBy(['match_date' => SORT_ASC])
                    ->one();

                $recentFixtures = \app\models\Fixture::find()
                    ->where(['or', ['home_team_id' => $team->id], ['away_team_id' => $team->id]])
                    ->andWhere(['status' => \app\models\Fixture::STATUS_FINISHED])
                    ->orderBy(['match_date' => SORT_DESC])
                    ->limit(5)
                    ->all();

                $standing = \app\models\Standing::find()
                    ->where(['team_id' => $team->id])
                    ->one();

                if ($standing) {
                    $competition = \app\models\Competition::findOne($standing->competition_id);
                    $leagueTable = \app\models\Standing::find()
                        ->with('team')
                        ->where(['competition_id' => $standing->competition_id])
                        ->orderBy(['points' => SORT_DESC, 'goals_for' => SORT_DESC])
                        ->all();
                }
            }
        }

        $latestNews = [];
        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin()) {
            $latestNews = \app\components\NewsService::latest(Yii::$app->user->id, 3);
        }

        return $this->render('index', [
            'team'           => $team,
            'nextFixture'    => $nextFixture,
            'nextFriendlyFixture' => $nextFriendlyFixture,
            'recentFixtures' => $recentFixtures,
            'standing'       => $standing,
            'competition'    => $competition,
            'leagueTable'    => $leagueTable,
            'latestNews'     => $latestNews,
        ]);
    }

    /**
     * SIP-0092: Public platform stats for landing page.
     */
    public function actionStats(): \yii\web\Response
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $db  = Yii::$app->db;
        $day = mktime(0, 0, 0);

        return $this->asJson([
            'managers'      => (int) $db->createCommand('SELECT COUNT(*) FROM {{%user}} WHERE status=1')->queryScalar(),
            'live_now'      => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status=1')->queryScalar(),
            'matches_today' => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status=2 AND match_date >= :d', [':d' => $day])->queryScalar(),
            'goals_today'   => (int) $db->createCommand('SELECT COALESCE(SUM(home_score+away_score),0) FROM {{%fixture}} WHERE status=2 AND match_date >= :d', [':d' => $day])->queryScalar(),
            'total_matches' => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status=2')->queryScalar(),
            'total_goals'   => (int) $db->createCommand('SELECT COALESCE(SUM(home_score+away_score),0) FROM {{%fixture}} WHERE status=2')->queryScalar(),
            'leagues'       => (int) $db->createCommand("SELECT COUNT(*) FROM {{%competition}} WHERE type='league'")->queryScalar(),
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm($this->security);

        if ($model->load($this->request->post()) && $model->login()) {
            /** @var \app\models\User $identity */
            $identity = Yii::$app->user->identity;
            if ($identity->isAdmin()) {
                return $this->redirect(['/admin/index']);
            }
            $team = \app\models\Team::findOne(['user_id' => $identity->id]);
            if ($team) {
                $team->ensureUiColors();
            }
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', ['model' => $model]);
    }

    /**
     * Registration: creates user, pushes WorldSeedJob to queue, redirects to pending page.
     */
    public function actionRegister(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new RegisterForm();

        if ($model->load($this->request->post())) {
            $user = $model->register();

            if ($user !== null) {
                Yii::$app->user->login($user, 0);
                Yii::$app->queue->push(new WorldSeedJob(['userId' => $user->id]));
                return $this->redirect(['/site/pending']);
            }
        }

        return $this->render('register', ['model' => $model]);
    }

    /**
     * Waiting room: polls /api/v1/user/status until team is ready.
     */
    public function actionPending(): Response|string
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        /** @var \app\models\User $identity */
        $identity = Yii::$app->user->identity;

        if ($identity->isReady()) {
            return $this->goHome();
        }

        if ($identity->status === \app\models\User::STATUS_ERROR) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error during team generation. Contact administrator.'));
            return $this->redirect(['/site/login']);
        }

        return $this->render('pending');
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        $contact = $model->load($this->request->post()) && $model->contact(
            $this->mailer,
            Yii::$app->params['adminEmail'],
            Yii::$app->params['senderEmail'],
            Yii::$app->params['senderName'],
        );

        if ($contact) {
            Yii::$app->session->setFlash(
                'success',
                'Thank you for contacting us. We will respond to you as soon as possible.',
            );

            return $this->refresh();
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }
}
