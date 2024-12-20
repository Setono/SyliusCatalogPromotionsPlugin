<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;

class CatalogPromotionUpdate implements CatalogPromotionUpdateInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected int $version = 1;

    protected string $state = self::STATE_PENDING;

    protected ?string $error = null;

    /** @var list<string>|null */
    protected ?array $catalogPromotions = null;

    protected ?int $productsEligibleForUpdate = null;

    protected int $productsUpdated = 0;

    /** @var list<string> */
    protected ?array $messageIds = null;

    /** @var list<string> */
    protected ?array $processedMessageIds = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): void
    {
        $this->version = (int) $version;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getCatalogPromotions(): array
    {
        return $this->catalogPromotions ?? [];
    }

    public function setCatalogPromotions(array $catalogPromotions): void
    {
        $this->catalogPromotions = $catalogPromotions;
    }

    public function getProductsEligibleForUpdate(): ?int
    {
        return $this->productsEligibleForUpdate;
    }

    public function setProductsEligibleForUpdate(int $productsEligibleForUpdate): void
    {
        $this->productsEligibleForUpdate = $productsEligibleForUpdate;
    }

    public function getProductsUpdated(): int
    {
        return $this->productsUpdated;
    }

    public function setProductsUpdated(int $productsUpdated): void
    {
        $this->productsUpdated = $productsUpdated;
    }

    public function incrementProductsUpdated(int $increment = 1): void
    {
        $this->productsUpdated += $increment;
    }

    public function getMessageIds(): array
    {
        return $this->messageIds ?? [];
    }

    public function addMessageId(string $messageId): void
    {
        if (null === $this->messageIds) {
            $this->messageIds = [];
        }

        $this->messageIds[] = $messageId;
    }

    public function setMessageIds(array $messageIds): void
    {
        if ([] === $messageIds) {
            $messageIds = null;
        }

        $this->messageIds = $messageIds;
    }

    public function addProcessedMessageId(string $messageId): void
    {
        if (null === $this->processedMessageIds) {
            $this->processedMessageIds = [];
        }

        $this->processedMessageIds[] = $messageId;
    }

    public function hasAllMessagesBeenProcessed(): bool
    {
        if (null === $this->messageIds || [] === $this->messageIds) {
            return true;
        }

        if (null === $this->processedMessageIds || [] === $this->processedMessageIds) {
            return false;
        }

        return [] === array_diff($this->messageIds, $this->processedMessageIds);
    }
}
