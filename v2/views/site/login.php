<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Login');
?>

<div class="auth-page">
    <div class="auth-card">

        <a href="<?= Yii::$app->homeUrl ?>" class="auth-logo">
            GOLD <span>MANAGER</span>
        </a>
        <p class="auth-subtitle"><?= Yii::t('app', 'Log in to your account to continue') ?></p>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger rounded-3 small py-2 mb-3">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin([
            'id'      => 'login-form',
            'options' => ['novalidate' => true],
        ]); ?>

        <div class="auth-input-group">
            <?= $form->field($model, 'username', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => Yii::t('app', 'Your username'),
                    'autofocus'   => true,
                ],
            ])->label('Username') ?>
        </div>

        <div class="auth-input-group">
            <?= $form->field($model, 'password', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => '••••••••',
                ],
            ])->passwordInput()->label('Password') ?>
        </div>

        <div class="mb-3">
            <?= $form->field($model, 'rememberMe')->checkbox([
                'label'       => Yii::t('app', 'Remember me'),
                'labelOptions'=> ['class' => 'form-check-label text-secondary small'],
            ]) ?>
        </div>

        <?= Html::submitButton(Yii::t('app', 'Login'), ['class' => 'btn-auth', 'name' => 'login-button']) ?>

        <?php ActiveForm::end(); ?>

        <hr class="auth-divider">

        <div class="auth-footer">
            <?= Yii::t('app', 'Don\'t have an account?') ?> <?= Html::a(Yii::t('app', 'Register now'), ['/site/register']) ?>
        </div>

    </div>
</div>
