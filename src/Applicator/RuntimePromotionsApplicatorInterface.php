<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

interface RuntimePromotionsApplicatorInterface
{
    /**
     * @param ProductInterface $product The product to apply catalog promotions from
     *
     * @return int The price after the catalog promotions have been applied
     */
    public function apply(ProductInterface $product, int $price, int $originalPrice = null): int;
}
