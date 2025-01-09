<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface CatalogPromotionRuleFactoryInterface extends FactoryInterface
{
    public function createByType(string $type, array $configuration, bool $strict = false): CatalogPromotionRuleInterface;

    public function createHasTaxon(array $taxonCodes): CatalogPromotionRuleInterface;

    public function createHasNotTaxon(array $taxonCodes): CatalogPromotionRuleInterface;

    public function createContainsProduct(string $productCode): CatalogPromotionRuleInterface;

    public function createContainsProducts(array $productCodes): CatalogPromotionRuleInterface;
}
