<?php

namespace app\modules\ui\generator\framework\vue;

interface FrameworkStrategy
{
    public function runCommand($viewName, $projectPath);
}