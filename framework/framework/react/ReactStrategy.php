<?php

namespace app\modules\ui\generator\framework\react;


class ReactStrategy implements FrameworkStrategy
{
    public function runCommand($viewName, $projectPath)
    {
        $npmCommand = 'npx create-react-app ' . escapeshellarg($viewName);
        $this->executeCommand($npmCommand, $projectPath);
    }

    private function executeCommand($npmCommand, $projectPath)
    {
        $command = "start cmd /k \"cd $projectPath && $npmCommand && pause\"";
        exec($command);
    }
}