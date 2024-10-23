<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator app\modules\ui\generator\FrontendGenerator */
?>

<div class="frontend-generator-form">
    <?php
    echo $form->field($generator, 'viewName');
    echo $form->field($generator, 'enableI18N')->checkbox();
    echo $form->field($generator, 'messageCategory');
    ?>
</div>