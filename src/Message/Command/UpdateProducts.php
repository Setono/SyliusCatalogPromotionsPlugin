<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;

/**
 * @internal Use the \Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate to update products
 */
final class UpdateProducts implements AsyncCommandInterface
{
    public readonly int $catalogPromotionUpdate;

    public function __construct(
        int|CatalogPromotionUpdateInterface $catalogPromotionUpdate,

        /** @var list<int> $ids */
        public readonly array $ids,

        /** @var list<string> $catalogPromotions */
        public readonly array $catalogPromotions = [],
    ) {
        $this->catalogPromotionUpdate = $catalogPromotionUpdate instanceof CatalogPromotionUpdateInterface ? (int) $catalogPromotionUpdate->getId() : $catalogPromotionUpdate;
    }
}
