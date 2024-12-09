<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface PromotionInterface extends ChannelsAwareInterface, CodeAwareInterface, TimestampableInterface, ResourceInterface
{
    public function getId(): ?int;

    public function __toString(): string;

    public function getMultiplier(): float;

    /**
     * @return string[]
     */
    public function getChannelCodes(): array;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    public function getPriority(): int;

    public function setPriority(?int $priority): void;

    public function isExclusive(): bool;

    public function setExclusive(bool $exclusive): void;

    /**
     * @return bool If true products which are already discounted will not be further discounted
     */
    public function isManuallyDiscountedProductsExcluded(): bool;

    public function setManuallyDiscountedProductsExcluded(bool $manuallyDiscountedProductsExcluded): void;

    public function getStartsAt(): ?DateTimeInterface;

    public function setStartsAt(?DateTimeInterface $startsAt): void;

    public function getEndsAt(): ?DateTimeInterface;

    public function setEndsAt(?DateTimeInterface $endsAt): void;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    /**
     * @return Collection|PromotionRuleInterface[]
     * @psalm-return Collection<array-key, PromotionRuleInterface>
     */
    public function getRules(): Collection;

    public function hasRules(): bool;

    public function hasRule(PromotionRuleInterface $rule): bool;

    public function addRule(PromotionRuleInterface $rule): void;

    public function removeRule(PromotionRuleInterface $rule): void;

    public function getDiscount(): float;

    public function getDisplayableDiscount(): float;

    public function setDiscount(float $discount): void;
}
