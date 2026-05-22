<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Accedi';
?>

<div class="auth-page">
    <div class="auth-card">

        <a href="<?= Yii::$app->homeUrl ?>" class="auth-logo">
            GOLD <span>MANAGER</span>
        </a>
        <p class="auth-subtitle">Accedi al tuo account per continuare</p>

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
                    'placeholder' => 'Il tuo username',
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
                'label'       => 'Ricordami',
                'labelOptions'=> ['class' => 'form-check-label text-secondary small'],
            ]) ?>
        </div>

        <?= Html::submitButton('Accedi', ['class' => 'btn-auth', 'name' => 'login-button']) ?>

        <?php ActiveForm::end(); ?>

        <hr class="auth-divider">

        <div class="auth-footer">
            Non hai un account? <?= Html::a('Registrati ora', ['/site/register']) ?>
        </div>

    </div>
</div>
