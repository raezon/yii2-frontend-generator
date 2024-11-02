<?php

namespace app\modules\ui\generator;

use Yii;
use yii\gii\Generator;
use yii\helpers\Html;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use app\modules\ui\generator\framework\FrameworkFactory;
use app\modules\ui\generator\config\ModelSchema;

class FrontendGenerator extends Generator
{
    public $viewName = 'hello-world';
    public $templateName = 'default';
    public $projectPath;
    public $framework;
    public $model;
    public $title = 'Hello World';

    public $templates = [
        'default' => '@app/modules/ui/generator/templates/default',
    ];

    public function getName()
    {
        return 'Frontend Vue 3 Generator';
    }

    public function getDescription()
    {
        return 'This generator creates Vue 3 components with customizable templates and routing options.';
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['viewName', 'filter', 'filter' => 'trim'],
            ['viewName', 'required'],
            ['viewName', 'match', 'pattern' => '/^\w+(?:-\w+)*$/', 'message' => 'Only word characters and dashes are allowed.'],
            ['templateName', 'string'],
            ['projectPath', 'string'],
            ['framework', 'string'],
            ['model', 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'viewName' => 'View Name',
            'templateName' => 'Template Name',
            'projectPath' => 'Path of Project',
            'framework' => 'Choose Framework',
            'model' => 'Select Model',
        ]);
    }

    public function hints()
    {
        return array_merge(parent::hints(), [
            'viewName' => 'Enter the name of the view file (without .vue) to be generated. Only word characters and dashes are allowed.',
            'templateName' => 'Select the template to be used for generating the view.',
            'projectPath' => 'Specify the custom path where you want the generated files to be saved.',
            'framework' => 'Choose which framework (Vue) to use.',
            'model' => 'Select the model type (Product, User, Cart, Other).',
        ]);
    }

    public function generate()
    {
        $files = [];
        $viewPath = $this->projectPath ? \Yii::getAlias($this->projectPath) : \Yii::$app->getViewPath();

        if (!is_writable($viewPath)) {
            throw new InvalidArgumentException("View path is not writable: {$viewPath}");
        }

        $frameworkStrategy = FrameworkFactory::create($this->framework);

        // Check if all required methods exist
        $requiredMethods = ['runCommand', 'generateListComponent', 'generateCreateComponent', 'generateUpdateComponent', 'generateDeleteComponent', 'generateMainComponent', 'getFileExtension', 'updateRouting'];
        foreach ($requiredMethods as $method) {
            if (!method_exists($frameworkStrategy, $method)) {
                Yii::error("Required method $method does not exist in " . get_class($frameworkStrategy), __METHOD__);
                throw new \RuntimeException("Framework strategy is missing required method: $method");
            }
        }

        $projectPath = $viewPath . '/' . $this->viewName;

        // Check if the project already exists
        if (!is_dir($projectPath)) {
            // Generate the framework project
            $frameworkStrategy->runCommand($this->viewName, $viewPath);
            Yii::info("Framework project generated at: " . $projectPath, __METHOD__);
        } else {
            Yii::info("Project already exists at: " . $projectPath . ". Skipping project creation.", __METHOD__);
        }

        // Generate CRUD components
        $componentPath = $projectPath . '/src/components/' . $this->model;
        if (!is_dir($componentPath)) {
            FileHelper::createDirectory($componentPath, 0777, true);
        }

        $schema = ModelSchema::getSchema($this->model);

        $crudComponents = [
            'List' => $frameworkStrategy->generateListComponent($this->model, $schema),
            'Create' => $frameworkStrategy->generateCreateComponent($this->model, $schema),
            'Update' => $frameworkStrategy->generateUpdateComponent($this->model, $schema),
            'Delete' => $frameworkStrategy->generateDeleteComponent($this->model, $schema),
            'Main' => $frameworkStrategy->generateMainComponent($this->model),
        ];

        foreach ($crudComponents as $name => $content) {
            $fileName = $componentPath . '/' . $this->model . $name . '.' . $frameworkStrategy->getFileExtension();
            $files[] = new \yii\gii\CodeFile($fileName, $content);

            // Write the file immediately and log the progress
            if (file_put_contents($fileName, $content) !== false) {
                Yii::info("Component {$this->model}{$name} created successfully at {$fileName}", __METHOD__);
                echo "Component {$this->model}{$name} created successfully.\n";
            } else {
                Yii::error("Failed to create component {$this->model}{$name} at {$fileName}", __METHOD__);
                echo "Failed to create component {$this->model}{$name}.\n";
            }
        }

        // Update routing
        $routeFiles = $frameworkStrategy->updateRouting($projectPath, $this->model);
        if (is_array($routeFiles)) {
            $files = array_merge($files, $routeFiles);
        } elseif ($routeFiles instanceof \yii\gii\CodeFile) {
            $files[] = $routeFiles;
        }

        Yii::info("Total files generated or updated: " . count($files), __METHOD__);
        echo "Total files generated or updated: " . count($files) . "\n";

        return $files;
    }
}
