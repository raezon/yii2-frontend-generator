<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator app\modules\ui\generator\FrontendGenerator */

?>

<div class="frontend-generator-form">

    <?php $form = ActiveForm::begin(); ?>

    <!-- View Name Input -->
    <?= $form->field($generator, 'viewName')->textInput(['maxlength' => true]) ?>

    <!-- Project Path Input (Custom Path for file generation) -->
    <?= $form->field($generator, 'projectPath')->textInput(['maxlength' => true])->label('Path of Project') ?>

    <!-- Framework Selection Dropdown -->
    <?= $form->field($generator, 'framework')->dropDownList([
        'vue' => 'Vue.js',
        'react' => 'React.js',
        'angular' => 'Angular',
    ], ['prompt' => 'Select Framework']) ?>

    <!-- Enable I18N -->
    <?= $form->field($generator, 'enableI18N')->checkbox() ?>

    <!-- Message Category -->
    <?= $form->field($generator, 'messageCategory')->textInput(['maxlength' => true]) ?>

    <!-- Code Template -->
    <?= $form->field($generator, 'templateName')->dropDownList($generator->templates, ['prompt' => 'Select Code Template']) ?>


    <div class="form-group">
        <?= Html::submitButton('Generate', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>