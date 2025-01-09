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

    public function setError(?string $error): void;

    public function getError(): ?string;

    /**
     * A list of catalog promotions codes that the update is for
     *
     * @return list<string>
     */
    public function getCatalogPromotions(): array;

    /**
     * @param list<string>|null $catalogPromotions
     */
    public function setCatalogPromotions(?array $catalogPromotions): void;

    /**
     * A list of product ids that the update is for. If empty, all products will be updated
     *
     * @return list<int>
     */
    public function getProducts(): array;

    /**
     * @param list<int>|null $products
     */
    public function setProducts(?array $products): void;

    /**
     * Information about what started the update
     */
    public function getTriggeredBy(): ?string;

    public function setTriggeredBy(?string $triggeredBy): void;

    /**
     * todo do we need this?
     * The number of products that are eligible for update. This should be set when the update is created
     */
    public function getProductsEligibleForUpdate(): ?int;

    public function setProductsEligibleForUpdate(int $productsEligibleForUpdate): void;

    public function getProductsUpdated(): int;

    public function setProductsUpdated(int $productsUpdated): void;

    public function incrementProductsUpdated(int $increment = 1): void;

    /**
     * Holds a list of ids that represent the messages responsible for updating the products.
     * This way we can track when the processing of a catalog promotion update is done.
     *
     * @return list<string>
     */
    public function getMessageIds(): array;

    public function addMessageId(string $messageId): void;

    /**
     * @param list<string> $messageIds
     */
    public function setMessageIds(array $messageIds): void;

    /**
     * Adds a message id to the list of processed message ids. Should only be added when a message was successfully processed
     */
    public function addProcessedMessageId(string $messageId): void;

    /**
     * Returns true if the processed list of message ids is equal to the list of message ids
     */
    public function hasAllMessagesBeenProcessed(): bool;
}
