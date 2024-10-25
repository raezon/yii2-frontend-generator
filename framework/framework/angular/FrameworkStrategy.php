<?php

namespace app\modules\ui\generator\framework\angular;

interface FrameworkStrategy
{
    public function runCommand($viewName, $projectPath);
}