<?php

namespace app\modules\ui\generator\framework;

use yii\base\InvalidArgumentException;

// Ensure you include the correct namespaces for the strategy classes
use app\modules\ui\generator\framework\vue\VueStrategy;
use app\modules\ui\generator\framework\react\ReactStrategy;
use app\modules\ui\generator\framework\angular\AngularStrategy;

class FrameworkFactory
{
    public static function create($framework)
    {
        switch ($framework) {
            case 'vue':
                return new VueStrategy();
            case 'react':
                return new ReactStrategy();
            case 'angular':
                return new AngularStrategy();
            default:
                throw new InvalidArgumentException('Invalid framework selected.');
        }
    }
}