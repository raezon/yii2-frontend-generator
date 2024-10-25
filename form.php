<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator app\modules\ui\generator\FrontendGenerator */

?>

<div class="frontend-generator-form">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($generator, 'viewName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($generator, 'projectPath')->textInput(['maxlength' => true])->label('Path of Project') ?>

    <?= $form->field($generator, 'framework')->dropDownList([
        'vue' => 'Vue.js',
        'react' => 'React.js',
        'angular' => 'Angular',
    ], ['prompt' => 'Select Framework']) ?>

    <?= $form->field($generator, 'model')->dropDownList([
        'product' => 'Product',
        'user' => 'User',
        'cart' => 'Cart',
        'other' => 'Other',
    ], ['prompt' => 'Select Model']) ?>

    <?= $form->field($generator, 'enableI18N')->checkbox() ?>

    <?= $form->field($generator, 'messageCategory')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Generate', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>