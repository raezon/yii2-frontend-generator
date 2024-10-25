<?php

namespace app\modules\ui\generator\framework;

interface FrameworkStrategy
{
    public function runCommand($viewName, $projectPath);
}