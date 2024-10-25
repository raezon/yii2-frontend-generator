<?php

namespace app\modules\ui\generator\factory;

use app\modules\ui\generator\generator\VueCrudGenerator;
use app\modules\ui\generator\generator\ReactCrudGenerator;
use app\modules\ui\generator\generator\AngularCrudGenerator;

class CrudGeneratorFactory
{
    public static function create($framework)
    {
        switch ($framework) {
            case 'vue':
                return new VueCrudGenerator();
            case 'react':
                return new ReactCrudGenerator();
            case 'angular':
                return new AngularCrudGenerator();
            default:
                throw new \InvalidArgumentException("Unsupported framework: $framework");
        }
    }
}
