<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Event;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

/**
 * Event dispatched when catalog promotions was applied to a product.
 */
final class CatalogPromotionsAppliedEvent
{
    public function __construct(
        public readonly ProductInterface $product,
        /** @var non-empty-list<CatalogPromotionInterface> $catalogPromotions */
        public readonly array $catalogPromotions,
    ) {
    }
}
