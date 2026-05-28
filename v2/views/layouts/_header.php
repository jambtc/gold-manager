<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\models\User;
use app\assets\HeaderNotificationsAsset;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/** @var User|null $identity */
$identity = Yii::$app->user->identity;
$isAdmin  = $identity && $identity->isAdmin();

if ($identity && !$isAdmin) {
    HeaderNotificationsAsset::register($this);
}

$managerItems = [
    ['label' => '<i class="bi bi-speedometer2"></i> ' . Yii::t('app', 'Dashboard'), 'url' => ['/site/index']],
    [
        'label'   => '<i class="bi bi-people"></i> ' . Yii::t('app', 'Team'),
        'visible' => !Yii::$app->user->isGuest && !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-people-fill"></i> ' . Yii::t('app', 'Squad'),        'url' => ['/team/view']],
            ['label' => '<i class="bi bi-grid-3x3"></i> ' . Yii::t('app', 'Tactic'),        'url' => ['/formation/view']],
            ['label' => '<i class="bi bi-lightning-charge"></i> ' . Yii::t('app', 'Training'), 'url' => ['/training/index']],
        ],
    ],
    [
        'label'   => '<i class="bi bi-calendar-event"></i> ' . Yii::t('app', 'Matches'),
        'visible' => !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-list-ul"></i> ' . Yii::t('app', 'Calendar'),      'url' => ['/fixture/index']],
            ['label' => '<i class="bi bi-play-circle"></i> ' . Yii::t('app', 'Friendlies'),  'url' => ['/friendly/index']],
            ['label' => '<i class="bi bi-trophy"></i> ' . Yii::t('app', 'Standings'),       'url' => ['/standing/index']],
            ['label' => '<i class="bi bi-bar-chart"></i> ' . Yii::t('app', 'Statistics'),   'url' => ['/stats/scorers']],
        ],
    ],
    [
        'label'   => '<i class="bi bi-building-gear"></i> ' . Yii::t('app', 'Club'),
        'visible' => !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-person-badge"></i> ' . Yii::t('app', 'Staff'),      'url' => ['/staff/view']],
            ['label' => '<i class="bi bi-binoculars"></i> ' . Yii::t('app', 'Scouting'),    'url' => ['/scouting/index']],
            ['label' => '<i class="bi bi-building"></i> ' . Yii::t('app', 'Stadium'),         'url' => ['/stadium/view']],
            ['label' => '<i class="bi bi-graph-up"></i> ' . Yii::t('app', 'Economy'),       'url' => ['/economy/index']],
            ['label' => '<i class="bi bi-briefcase"></i> ' . Yii::t('app', 'Sponsor'),       'url' => ['/sponsor/index']],
        ],
    ],
    [
        'label' => (function() use ($identity, $isAdmin): string {
            if ($isAdmin || !$identity) return '<i class="bi bi-shop"></i> ' . Yii::t('app', 'Market');
            $team = \app\models\Team::findOne(['user_id' => $identity->id]);
            if (!$team) return '<i class="bi bi-shop"></i> ' . Yii::t('app', 'Market');
            $pending = (int) \app\models\TransferOffer::find()
                ->where(['to_team_id' => $team->id, 'status' => 'pending'])
                ->andWhere(['>', 'expires_at', time()])
                ->count();
            $badge = $pending > 0
                ? '<span style="display:inline-flex;align-items:center;justify-content:center;background:var(--accent-red);color:#fff;border-radius:50%;font-size:.55rem;font-weight:900;width:14px;height:14px;margin-left:.3rem">' . $pending . '</span>'
                : '';
            return '<i class="bi bi-shop"></i> ' . Yii::t('app', 'Market') . $badge;
        })(),
        'url' => ['/transfer/market'],
        'visible' => !$isAdmin,
        'encode' => false,
    ],
];

$adminItems = [
    ['label' => '<i class="bi bi-sliders"></i> ' . Yii::t('app', 'Admin Panel'),    'url' => ['/admin/index']],
    ['label' => '<i class="bi bi-play-circle"></i> ' . Yii::t('app', 'Friendly'),    'url' => ['/admin/friendly']],
    ['label' => '<i class="bi bi-cpu"></i> Queue Jobs',            'url' => ['/admin/jobs']],
];

$navItems = Yii::$app->user->isGuest
    ? []
    : ($isAdmin ? $adminItems : $managerItems);
?>
<header id="header">
    <?php NavBar::begin([
        'brandLabel' => 'GOLD <span class="text-white">MANAGER</span>',
        'brandUrl'   => Yii::$app->homeUrl,
        'options'    => ['class' => 'navbar-expand-md navbar-dark fixed-top'],
    ]) ?>

    <?= Nav::widget([
        'options'      => ['class' => 'navbar-nav me-auto mb-2 mb-md-0'],
        'encodeLabels' => false,
        'items'        => $navItems,
    ]) ?>

    <div class="navbar-nav ms-auto align-items-center">
        <?php if (Yii::$app->user->isGuest): ?>
            <?= Html::a(Yii::t('app', 'Login'), ['/site/login'], ['class' => 'nav-link']) ?>
            <?= Html::a(Yii::t('app', 'Register'), ['/site/register'], ['class' => 'btn btn-gold ms-lg-3']) ?>
        <?php else: ?>
            <?php if (!$isAdmin): ?>
            <?php $unread = \app\components\NewsService::unreadCount($identity->id); ?>
            <?php
            $this->registerJs(
                'window.GM_HEADER_NOTIFICATIONS = ' . Json::htmlEncode([
                    'enabled' => true,
                    'initialUnread' => (int) $unread,
                    'lastEventId' => 0,
                    'streamUrl' => Url::to(['/notification/stream']),
                    'pollUrl' => Url::to(['/notification/poll']),
                    'pollMs' => 12000,
                ]) . ';',
                View::POS_HEAD
            );
            ?>
            <a id="gm-news-bell" href="<?= Url::to(['/news/index']) ?>" class="nav-link position-relative me-1" title="<?= Yii::t('app', 'News') ?>">
                <i id="gm-news-bell-icon" class="bi bi-bell<?= $unread > 0 ? '-fill text-gold' : '' ?>"></i>
                <span id="gm-news-bell-badge" style="position:absolute;top:4px;right:2px;background:var(--accent-red);color:#fff;border-radius:50%;font-size:.55rem;font-weight:900;width:14px;height:14px;align-items:center;justify-content:center;line-height:1;display:<?= $unread > 0 ? 'flex' : 'none' ?>"><?= $unread > 0 ? min($unread, 99) : '' ?></span>
            </a>
            <?php endif; ?>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-bold <?= $isAdmin ? 'text-danger' : 'text-gold' ?>"
                   href="#" id="userDropdown" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <?= Html::encode($identity->username) ?>
                    <?php if ($isAdmin): ?>
                        <span class="nav-admin-badge">ADMIN</span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark gm-card" aria-labelledby="userDropdown">
                    <?php if ($isAdmin): ?>
                        <li><?= Html::a('<i class="bi bi-sliders"></i> ' . Yii::t('app', 'Admin Panel'), ['/admin/index'], ['class' => 'dropdown-item']) ?></li>
                    <?php else: ?>
                        <li><?= Html::a('<i class="bi bi-person-gear"></i> ' . Yii::t('app', 'Profile'), ['/user/profile'], ['class' => 'dropdown-item']) ?></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <?= Html::beginForm(['/site/logout'])
                            . Html::submitButton(
                                '<i class="bi bi-box-arrow-right"></i> ' . Yii::t('app', 'Logout'),
                                ['class' => 'dropdown-item logout text-danger']
                            )
                            . Html::endForm()
                        ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <?php NavBar::end() ?>
</header>
