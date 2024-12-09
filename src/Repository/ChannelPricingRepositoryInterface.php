<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use DateTimeInterface;

interface ChannelPricingRepositoryInterface extends HasAnyBeenUpdatedSinceRepositoryInterface
{
    /**
     * This resets the multiplier on all channel pricings
     */
    public function resetMultiplier(DateTimeInterface $dateTime, string $bulkIdentifier): void;

    /**
     * @param bool $exclusive If true this method will overwrite the multiplier instead of multiplying it
     */
    public function updateMultiplier(
        string $promotionCode,
        float $multiplier,
        array $productVariantIds,
        array $channelCodes,
        DateTimeInterface $dateTime,
        string $bulkIdentifier,
        bool $exclusive = false,
        bool $manuallyDiscountedProductsExcluded = true,
    ): void;

    /**
     * This method will update ALL channel prices in the given bulk
     */
    public function updatePrices(string $bulkIdentifier): void;
}
