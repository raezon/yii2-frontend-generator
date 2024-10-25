<?php

namespace app\modules\ui\generator\framework\angular;



class AngularStrategy implements FrameworkStrategy
{
    public function runCommand($viewName, $projectPath)
    {
        $npmCommand = 'npx @angular/cli new ' . escapeshellarg($viewName) . ' --defaults';
        $this->executeCommand($npmCommand, $projectPath);
    }

    private function executeCommand($npmCommand, $projectPath)
    {
        $command = "start cmd /k \"cd $projectPath && $npmCommand && pause\"";
        exec($command);
    }
}