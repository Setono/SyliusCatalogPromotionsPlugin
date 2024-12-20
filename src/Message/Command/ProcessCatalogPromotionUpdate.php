<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;

/**
 * @internal
 */
final class ProcessCatalogPromotionUpdate implements AsyncCommandInterface
{
    public readonly int $catalogPromotionUpdate;

    public function __construct(CatalogPromotionUpdateInterface|int $catalogPromotionUpdate)
    {
        $this->catalogPromotionUpdate = $catalogPromotionUpdate instanceof CatalogPromotionUpdateInterface ? (int) $catalogPromotionUpdate->getId() : $catalogPromotionUpdate;
    }
}
