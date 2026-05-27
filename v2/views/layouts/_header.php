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
    ['label' => '<i class="bi bi-speedometer2"></i> Dashboard', 'url' => ['/site/index']],
    [
        'label'   => '<i class="bi bi-people"></i> Squadra',
        'visible' => !Yii::$app->user->isGuest && !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-people-fill"></i> Rosa',        'url' => ['/team/view']],
            ['label' => '<i class="bi bi-grid-3x3"></i> Tattica',        'url' => ['/formation/view']],
            ['label' => '<i class="bi bi-lightning-charge"></i> Allenamento', 'url' => ['/training/index']],
        ],
    ],
    [
        'label'   => '<i class="bi bi-calendar-event"></i> Partite',
        'visible' => !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-list-ul"></i> Calendario',      'url' => ['/fixture/index']],
            ['label' => '<i class="bi bi-play-circle"></i> Amichevoli',  'url' => ['/friendly/index']],
            ['label' => '<i class="bi bi-trophy"></i> Classifica',       'url' => ['/standing/index']],
            ['label' => '<i class="bi bi-bar-chart"></i> Statistiche',   'url' => ['/stats/scorers']],
        ],
    ],
    [
        'label'   => '<i class="bi bi-building-gear"></i> Club',
        'visible' => !$isAdmin,
        'items'   => [
            ['label' => '<i class="bi bi-person-badge"></i> Staff',      'url' => ['/staff/view']],
            ['label' => '<i class="bi bi-binoculars"></i> Scouting',    'url' => ['/scouting/index']],
            ['label' => '<i class="bi bi-building"></i> Stadio',         'url' => ['/stadium/view']],
            ['label' => '<i class="bi bi-graph-up"></i> Economia',       'url' => ['/economy/index']],
            ['label' => '<i class="bi bi-briefcase"></i> Sponsor',       'url' => ['/sponsor/index']],
        ],
    ],
    [
        'label' => (function() use ($identity, $isAdmin): string {
            if ($isAdmin || !$identity) return '<i class="bi bi-shop"></i> Mercato';
            $team = \app\models\Team::findOne(['user_id' => $identity->id]);
            if (!$team) return '<i class="bi bi-shop"></i> Mercato';
            $pending = (int) \app\models\TransferOffer::find()
                ->where(['to_team_id' => $team->id, 'status' => 'pending'])
                ->andWhere(['>', 'expires_at', time()])
                ->count();
            $badge = $pending > 0
                ? '<span style="display:inline-flex;align-items:center;justify-content:center;background:var(--accent-red);color:#fff;border-radius:50%;font-size:.55rem;font-weight:900;width:14px;height:14px;margin-left:.3rem">' . $pending . '</span>'
                : '';
            return '<i class="bi bi-shop"></i> Mercato' . $badge;
        })(),
        'url' => ['/transfer/market'],
        'visible' => !$isAdmin,
        'encode' => false,
    ],
];

$adminItems = [
    ['label' => '<i class="bi bi-sliders"></i> Pannello Admin',    'url' => ['/admin/index']],
    ['label' => '<i class="bi bi-play-circle"></i> Amichevole',    'url' => ['/admin/friendly']],
    ['label' => '<i class="bi bi-cpu"></i> Queue Jobs',            'url' => ['/admin/jobs']],
];

$navItems = $isAdmin ? $adminItems : $managerItems;
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
            <?= Html::a('Accedi', ['/site/login'], ['class' => 'nav-link']) ?>
            <?= Html::a('Registrati', ['/site/register'], ['class' => 'btn btn-gold ms-lg-3']) ?>
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
            <a id="gm-news-bell" href="<?= Url::to(['/news/index']) ?>" class="nav-link position-relative me-1" title="Notizie">
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
                        <li><?= Html::a('<i class="bi bi-sliders"></i> Pannello Admin', ['/admin/index'], ['class' => 'dropdown-item']) ?></li>
                    <?php else: ?>
                        <li><?= Html::a('<i class="bi bi-person-gear"></i> Profilo', ['/user/profile'], ['class' => 'dropdown-item']) ?></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <?= Html::beginForm(['/site/logout'])
                            . Html::submitButton(
                                '<i class="bi bi-box-arrow-right"></i> Esci',
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
