<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Event;

use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

/**
 * Event dispatched when a catalog promotion has been applied to a product.
 * You don't know which catalog promotion was applied or any other context, just that one or more were applied.
 */
final class CatalogPromotionAppliedEvent
{
    public function __construct(public readonly ProductInterface $product)
    {
    }
}
