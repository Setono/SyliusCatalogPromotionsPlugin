<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DataProvider;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

interface ProductDataProviderInterface
{
    /**
     * Returns an iterable of product ids that are eligible for _any_ catalog promotion.
     * Exclude disabled products, gift cards, etc. here
     *
     * @param array<int> $ids If provided, only return ids that are in this array
     *
     * @return iterable<int>
     */
    public function getIds(array $ids = []): iterable;

    /**
     * @return iterable<ProductInterface>
     */
    public function getProducts(array $ids): iterable;
}
