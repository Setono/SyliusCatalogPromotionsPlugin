<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('fakerphp/faker', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('twig/extra-bundle', [ErrorType::UNUSED_DEPENDENCY]) // We use this to register the StringExtension
    ->ignoreErrorsOnPackage('twig/string-extra', [ErrorType::UNUSED_DEPENDENCY]) // This is the StringExtension that we use for the 'u' filter
    ->ignoreErrorsOnPackage('twig/twig', [ErrorType::UNUSED_DEPENDENCY]) // Obviously we use this
;
