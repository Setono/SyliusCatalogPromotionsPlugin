<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Core\Model\ChannelPricingInterface as BaseChannelPricingInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface ChannelPricingInterface extends BaseChannelPricingInterface, TimestampableInterface
{
    public function hasDiscount(): bool;

    public function getDiscountAmount(): ?int;

    /**
     * If $asInteger is true it returns the discount rounded to the nearest whole number
     */
    public function getDisplayableDiscount(bool $asInteger = false): ?float;

    /**
     * @return bool Returns true if this was discounted manually
     */
    public function isManuallyDiscounted(): bool;

    public function setManuallyDiscounted(bool $manuallyDiscounted): void;

    /**
     * Returns the origin of the setManuallyDiscounted() method call if isManuallyDiscounted() is true
     */
    public function getManuallyDiscountedOrigin(): ?string;

    public function setManuallyDiscountedOrigin(?string $manuallyDiscountedOrigin): void;

    public function getMultiplier(): float;

    public function getBulkIdentifier(): ?string;

    public function resetBulkIdentifier(): void;

    public function getUsedPromotions(): array;

    public function addUsedPromotions(string $promotionCode): void;
}
