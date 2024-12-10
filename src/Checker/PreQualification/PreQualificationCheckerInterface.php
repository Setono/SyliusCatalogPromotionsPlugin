<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;

interface PreQualificationCheckerInterface
{
    /**
     * Checks the pre-qualification criteria for the product and returns true if the product is pre-qualified for the promotion
     */
    public function isPreQualified(ProductInterface $product, PromotionInterface $catalogPromotion): bool;
}
