<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DataProvider;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

interface ProductDataProviderInterface
{
    /**
     * Returns an iterable of products that eligible for _any_ catalog promotion.
     * Exclude disabled products, gift cards, etc. here
     *
     * @return iterable<ProductInterface>
     */
    public function getProducts(): iterable;
}
