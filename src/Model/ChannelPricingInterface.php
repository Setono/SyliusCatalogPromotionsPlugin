<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Core\Model\ChannelPricingInterface as BaseChannelPricingInterface;

interface ChannelPricingInterface extends BaseChannelPricingInterface
{
    public function isManuallyDiscounted(): bool;

    /**
     * todo we need to update this via the resource controller events
     */
    public function setManuallyDiscounted(bool $manuallyDiscounted): void;
}
