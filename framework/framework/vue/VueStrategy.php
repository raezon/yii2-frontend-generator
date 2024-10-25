<?php

namespace app\modules\ui\generator\framework\vue;

use app\modules\ui\generator\framework\FrameworkStrategy;

class VueStrategy implements FrameworkStrategy
{
    public function runCommand($viewName, $projectPath)
    {
        $npmCommand = 'vue create ' . escapeshellarg($viewName) . ' --default';
        $this->executeCommand($npmCommand, $projectPath);

        // Run npm install inside the created project directory
        $npmInstallCommand = 'npm install';
        $this->executeCommand($npmInstallCommand, $projectPath . DIRECTORY_SEPARATOR . $viewName);
    }

    private function executeCommand($npmCommand, $projectPath)
    {
        $command = "start cmd /k \"cd $projectPath && $npmCommand && pause\"";
        exec($command);
    }
}