<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

interface RuntimePromotionsApplicatorInterface
{
    /**
     * @param list<string> $catalogPromotions The codes of the catalog promotions to apply
     * @param int $price The price before the catalog promotions have been applied
     * @param bool $manuallyDiscounted Whether the price has been manually discounted
     *
     * @return int The price after the catalog promotions have been applied
     */
    public function apply(array $catalogPromotions, int $price, bool $manuallyDiscounted): int;
}
