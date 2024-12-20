<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('fakerphp/faker', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('psr/clock-implementation', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/clock', [ErrorType::UNUSED_DEPENDENCY])
;
