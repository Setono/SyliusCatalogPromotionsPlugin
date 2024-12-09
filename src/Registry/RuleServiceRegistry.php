<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Registry;

use Setono\SyliusCatalogPromotionPlugin\Rule\RuleInterface;
use Sylius\Component\Registry\ServiceRegistry;

class RuleServiceRegistry extends ServiceRegistry
{
    public function __construct()
    {
        parent::__construct(
            RuleInterface::class,
            'rule',
        );
    }
}
