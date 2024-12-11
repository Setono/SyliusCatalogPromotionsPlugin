<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DependencyInjection\Compiler;

use Setono\SyliusCatalogPromotionPlugin\Calculator\ProductVariantPricesCalculator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This plugin has its own implementation of the \Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface
 * and hence we will override the default service with our own implementation.
 */
final class OverrideProductVariantPricesCalculatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('sylius.calculator.product_variant_price')) {
            return;
        }

        $container->setAlias('sylius.calculator.product_variant_price', ProductVariantPricesCalculator::class);
    }
}
