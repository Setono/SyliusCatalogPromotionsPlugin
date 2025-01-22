<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

interface RuntimePromotionsApplicatorInterface
{
    /**
     * @param ProductInterface $product The product to apply catalog promotions from
     * @param int $price The price to apply catalog promotions to
     * @param bool $manuallyDiscounted Whether the price has been manually discounted
     *
     * @return int The price after the catalog promotions have been applied
     */
    public function apply(ProductInterface $product, int $price, bool $manuallyDiscounted): int;
}
