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

    /** @var list<string> */
    protected array $catalogPromotions = [];

    protected ?int $productsEligibleForUpdate = null;

    protected int $productsUpdated = 0;

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

    public function getCatalogPromotions(): array
    {
        return $this->catalogPromotions;
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
}
