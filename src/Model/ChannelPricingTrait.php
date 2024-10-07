<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Resource\Model\TimestampableTrait;

/**
 * @mixin ChannelPricing
 */
trait ChannelPricingTrait
{
    use TimestampableTrait;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     *
     * @var bool
     */
    protected $manuallyDiscounted = false;

    /** @ORM\Column(type="text", nullable=true) */
    protected ?string $manuallyDiscountedOrigin = null;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=4, options={"default": 1})
     *
     * @var float
     */
    protected $multiplier = 1;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    protected $bulkIdentifier;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var DateTimeInterface|null
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @var DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var array<array-key, string>
     */
    protected array $usedPromotions = [];

    public function hasDiscount(): bool
    {
        return null !== $this->getOriginalPrice()
            && null !== $this->getPrice()
            && $this->getOriginalPrice() > $this->getPrice()
            ;
    }

    public function getDiscountAmount(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return (int) $this->getOriginalPrice() - (int) $this->getPrice();
    }

    public function getDisplayableDiscount(bool $asInteger = false): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        $precision = $asInteger ? 0 : 2;

        return round(100 - ($this->getPrice() / $this->getOriginalPrice() * 100), $precision);
    }

    public function isManuallyDiscounted(): bool
    {
        return $this->manuallyDiscounted;
    }

    public function setManuallyDiscounted(bool $manuallyDiscounted): void
    {
        $this->manuallyDiscounted = $manuallyDiscounted;
    }

    public function getManuallyDiscountedOrigin(): ?string
    {
        return $this->manuallyDiscountedOrigin;
    }

    public function setManuallyDiscountedOrigin(?string $manuallyDiscountedOrigin): void
    {
        $this->manuallyDiscountedOrigin = $manuallyDiscountedOrigin;
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }

    public function getBulkIdentifier(): ?string
    {
        return $this->bulkIdentifier;
    }

    public function resetBulkIdentifier(): void
    {
        $this->bulkIdentifier = null;
    }


    public function getUsedPromotions(): array
    {
        return $this->usedPromotions;
    }

    public function addUsedPromotion(string $usedPromotion): void
    {
        $this->usedPromotions[] = $usedPromotion;
    }
}
