<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\OnSale;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface OnSaleCheckerInterface
{
    /**
     * @param ChannelInterface|null $channel if null the channel context will be used
     */
    public function onSale(ProductInterface|ProductVariantInterface $product, ChannelInterface $channel = null): bool;
}
