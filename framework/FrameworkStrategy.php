<?php

namespace app\modules\ui\generator\framework;

interface FrameworkStrategy
{
    public function runCommand($viewName, $projectPath);
    public function generateListComponent($model, $schema);
    public function generateCreateComponent($model, $schema);
    public function generateUpdateComponent($model, $schema);
    public function generateDeleteComponent($model, $schema);
    public function generateMainComponent($model);
    public function getFileExtension();
}
