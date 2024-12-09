<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Resource\Model\TimestampableTrait;

trait ChannelPricingTrait
{
    use TimestampableTrait;

    /** @ORM\Column(type="boolean", options={"default": 0}) */
    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    protected bool $manuallyDiscounted = false;

    public function isManuallyDiscounted(): bool
    {
        return $this->manuallyDiscounted;
    }

    public function setManuallyDiscounted(bool $manuallyDiscounted): void
    {
        $this->manuallyDiscounted = $manuallyDiscounted;
    }
}
