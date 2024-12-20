<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\VersionedInterface;

interface CatalogPromotionUpdateInterface extends ResourceInterface, TimestampableInterface, VersionedInterface
{
    public const STATE_PENDING = 'pending';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public function getId(): ?int;

    public function getState(): string;

    public function setState(string $state): void;

    /**
     * A list of catalog promotions codes that the update is for
     *
     * @return list<string>
     */
    public function getCatalogPromotions(): array;

    /**
     * @param list<string> $catalogPromotions
     */
    public function setCatalogPromotions(array $catalogPromotions): void;

    /**
     * The number of products that are eligible for update. This should be set when the update is created
     */
    public function getProductsEligibleForUpdate(): ?int;

    public function setProductsEligibleForUpdate(int $productsEligibleForUpdate): void;

    public function getProductsUpdated(): int;

    public function setProductsUpdated(int $productsUpdated): void;

    public function incrementProductsUpdated(int $increment = 1): void;
}
