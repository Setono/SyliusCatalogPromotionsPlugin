<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

class Promotion implements PromotionInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $name = null;

    protected ?string $description = null;

    /**
     * When exclusive, promotion with top priority will be applied
     */
    protected int $priority = 0;

    /**
     * Cannot be applied together with other promotions
     */
    protected bool $exclusive = false;

    protected bool $manuallyDiscountedProductsExcluded = true;

    protected ?DateTimeInterface $startsAt = null;

    protected ?DateTimeInterface $endsAt = null;

    protected bool $enabled = true;

    /** @var Collection<array-key, PromotionRuleInterface> */
    protected Collection $rules;

    /**
     * The discount is saved as a decimal type in Doctrine to avoid floating point issues
     */
    protected string $discount = '0.0';

    /** @var Collection<array-key, BaseChannelInterface> */
    protected Collection $channels;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();

        $this->rules = new ArrayCollection();
        $this->channels = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) ($this->getName() ?? $this->getCode() ?? $this->getId());
    }

    public function getMultiplier(): float
    {
        return 1 - $this->getDiscount();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority ?? -1;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setExclusive(bool $exclusive): void
    {
        $this->exclusive = $exclusive;
    }

    public function isManuallyDiscountedProductsExcluded(): bool
    {
        return $this->manuallyDiscountedProductsExcluded;
    }

    public function setManuallyDiscountedProductsExcluded(bool $manuallyDiscountedProductsExcluded): void
    {
        $this->manuallyDiscountedProductsExcluded = $manuallyDiscountedProductsExcluded;
    }

    public function getStartsAt(): ?DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(?DateTimeInterface $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): ?DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?DateTimeInterface $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function hasRules(): bool
    {
        return !$this->rules->isEmpty();
    }

    public function hasRule(PromotionRuleInterface $rule): bool
    {
        return $this->rules->contains($rule);
    }

    public function addRule(PromotionRuleInterface $rule): void
    {
        if (!$this->hasRule($rule)) {
            $rule->setPromotion($this);
            $this->rules->add($rule);
        }
    }

    public function removeRule(PromotionRuleInterface $rule): void
    {
        $rule->setPromotion(null);
        $this->rules->removeElement($rule);
    }

    public function getDiscount(): float
    {
        return (float) $this->discount;
    }

    public function setDiscount(float $discount): void
    {
        $this->discount = (string) $discount;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(BaseChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(BaseChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(BaseChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }
}
