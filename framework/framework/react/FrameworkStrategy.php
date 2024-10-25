<?php

namespace app\modules\ui\generator\framework\react;

interface FrameworkStrategy
{
    public function runCommand($viewName, $projectPath);
}