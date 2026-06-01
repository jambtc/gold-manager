<?php

/** @var yii\web\View $this */
/** @var app\models\RegisterForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Create account');
?>

<div class="auth-page">
    <div class="auth-card">

        <a href="<?= Yii::$app->homeUrl ?>" class="auth-logo">
            GOLD <span>MANAGER</span>
        </a>
        <p class="auth-subtitle"><?= Yii::t('app', 'Register and take control of your team') ?></p>

        <div class="text-center mb-4">
            <span class="auth-badge">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="#f59e0b" stroke-width="1.5"/><path d="M4 6l1.5 1.5L8 4.5" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= Yii::t('app', 'Your team will be automatically generated in Serie C of the selected country') ?>
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
            <?= $form->field($model, 'countryCode', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class' => 'form-select',
                ],
            ])->dropDownList($model->countryOptions())->label(Yii::t('app', 'Country')) ?>
        </div>

        <div class="auth-input-group">
            <?= $form->field($model, 'username', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => Yii::t('app', 'Choose a username (letters, numbers, _)'),
                    'autofocus'   => true,
                ],
            ])->label(Yii::t('app', 'Username')) ?>
        </div>

        <div class="auth-input-group">
            <?= $form->field($model, 'password', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => Yii::t('app', 'Minimum 6 characters'),
                ],
            ])->passwordInput()->label(Yii::t('app', 'Password')) ?>
        </div>

        <div class="auth-input-group">
            <?= $form->field($model, 'passwordConfirm', [
                'template'     => "{label}\n{input}\n{error}",
                'labelOptions' => [],
                'inputOptions' => [
                    'class'       => 'form-control',
                    'placeholder' => Yii::t('app', 'Repeat password'),
                ],
            ])->passwordInput()->label(Yii::t('app', 'Confirm password')) ?>
        </div>

        <?= Html::submitButton(Yii::t('app', 'Register & Get on the pitch'), ['class' => 'btn-auth', 'name' => 'register-button']) ?>

        <?php ActiveForm::end(); ?>

        <hr class="auth-divider">

        <div class="auth-footer">
            <?= Yii::t('app', 'Already have an account?') ?> <?= Html::a(Yii::t('app', 'Login'), ['/site/login']) ?>
        </div>

    </div>
</div>
