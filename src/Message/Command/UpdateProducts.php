<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @internal Use the \Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate to update products
 */
final class UpdateProducts implements AsyncCommandInterface
{
    public readonly int $catalogPromotionUpdate;

    public readonly string $messageId;

    /** @var non-empty-list<string> */
    public readonly array $catalogPromotions;

    /**
     * @param list<string> $catalogPromotions
     */
    public function __construct(
        CatalogPromotionUpdateInterface $catalogPromotionUpdate,
        /** @var list<int> $productIds */
        public readonly array $productIds,
        array $catalogPromotions,
    ) {
        $this->catalogPromotionUpdate = (int) $catalogPromotionUpdate->getId();
        $this->messageId = (string) Uuid::v7();
        $catalogPromotionUpdate->addMessageId($this->messageId);

        if ([] === $catalogPromotions) {
            throw new \InvalidArgumentException('The catalog promotions array must not be empty');
        }

        $this->catalogPromotions = $catalogPromotions;
    }
}
