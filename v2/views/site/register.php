<?php

/** @var yii\web\View $this */
/** @var app\models\RegisterForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Crea account';
?>

<div class="auth-page">
    <div class="auth-card">

        <a href="<?= Yii::$app->homeUrl ?>" class="auth-logo">
            GOLD <span>MANAGER</span>
        </a>
        <p class="auth-subtitle">Registrati e prendi in mano la tua squadra</p>

        <div class="text-center mb-4">
            <span class="auth-badge">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="#f59e0b" stroke-width="1.5"/><path d="M4 6l1.5 1.5L8 4.5" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                La tua squadra sarà generata automaticamente in Serie C
            </span>
        </div>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger rounded-3 small py-2 mb-3">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin([
            'id'      => 'register-form',
            'options' => ['novalidate' => true],
        ]); ?>

        <div class="auth-input-group">
            <?= $form->field($model, 'username', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Scegli un username (lettere, numeri, _)',
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
                    'placeholder' => 'Minimo 6 caratteri',
                ],
            ])->passwordInput()->label('Password') ?>
        </div>

        <div class="auth-input-group">
            <?= $form->field($model, 'passwordConfirm', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ripeti la password',
                ],
            ])->passwordInput()->label('Conferma password') ?>
        </div>

        <?= Html::submitButton('Registrati & Entra in campo', ['class' => 'btn-auth', 'name' => 'register-button']) ?>

        <?php ActiveForm::end(); ?>

        <hr class="auth-divider">

        <div class="auth-footer">
            Hai già un account? <?= Html::a('Accedi', ['/site/login']) ?>
        </div>

    </div>
</div>
