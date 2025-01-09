<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;

interface RuntimeCheckerInterface
{
    /**
     * Returns true if runtime checks are eligible for the given catalog promotion.
     * This is meant to be run each time a price is calculated on a product.
     * This implies that the runtime checks should be fast and not perform any heavy operations.
     */
    public function isEligible(CatalogPromotionInterface $catalogPromotion): bool;
}
