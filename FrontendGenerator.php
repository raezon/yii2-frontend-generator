<?php

namespace app\modules\ui\generator;

use yii\gii\Generator;
use yii\helpers\Html;
use yii\base\InvalidArgumentException;

class FrontendGenerator extends Generator
{
    public $viewName = 'hello-world';   // Name of the view file to be generated
    public $templateName = 'default';    // Name of the template to use

    public function getName()
    {
        return 'Frontend Hello World Generator';
    }

    public function getDescription()
    {
        return 'This generator creates a simple Hello World view with customizable templates.';
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['viewName', 'filter', 'filter' => 'trim'],
            ['viewName', 'required'],
            ['viewName', 'match', 'pattern' => '/^\w+(?:-\w+)*$/', 'message' => 'Only word characters and dashes are allowed.'],
            ['templateName', 'string'], // Rule for the template name
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'viewName' => 'View Name',
            'templateName' => 'Template Name', // Label for the new attribute
        ]);
    }

    public function hints()
    {
        return array_merge(parent::hints(), [
            'viewName' => 'Enter the name of the view file (without .php) to be generated. Only word characters and dashes are allowed.',
            'templateName' => 'Select the template to be used for generating the view.',
        ]);
    }

    public function generate()
    {
        $files = [];
        $viewPath = \Yii::$app->getViewPath();
        $templatePath = $this->getTemplatePath() . '/view.php';

        // Check if the template exists
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException("Template file does not exist: {$templatePath}");
        }

        $files[] = new \yii\gii\CodeFile(
            $viewPath . '/' . $this->viewName . '.php',
            $this->render($templatePath)
        );

        return $files;
    }

    public function getTemplatePath()
    {
        return \Yii::getAlias('@app/modules/ui/generator/default/' . $this->templateName);
    }
}
