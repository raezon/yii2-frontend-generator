<?php

namespace app\modules\ui\generator;

use yii\gii\Generator;
use yii\helpers\Html;

class FrontendGenerator extends Generator
{
    public $viewName = 'hello-world';

    public function getName()
    {
        return 'Frontend Hello World Generator';
    }

    public function getDescription()
    {
        return 'This generator creates a simple Hello World view.';
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['viewName', 'filter', 'filter' => 'trim'],
            ['viewName', 'required'],
            ['viewName', 'match', 'pattern' => '/^\w+(?:-\w+)*$/', 'message' => 'Only word characters and dashes are allowed.'],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'viewName' => 'View Name',
        ]);
    }

    public function hints()
    {
        return array_merge(parent::hints(), [
            'viewName' => 'This is the name of the view file to be generated.',
        ]);
    }

    public function generate()
    {
        $files = [];
        $viewPath = \Yii::$app->getViewPath();
        $templatePath = $this->getTemplatePath() . '/view.php';
        $files[] = new \yii\gii\CodeFile(
            $viewPath . '/' . $this->viewName . '.php',
            $this->render($templatePath)
        );
        return $files;
    }

    public function getTemplatePath()
    {
        return \Yii::getAlias('@app/modules/ui/generator/default');
    }
}