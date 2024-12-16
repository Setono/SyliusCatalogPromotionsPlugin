<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;

final class ProcessCatalogPromotionUpdate implements AsyncCommandInterface
{
    public readonly int $catalogPromotion;

    public function __construct(CatalogPromotionUpdateInterface|int $catalogPromotionUpdate)
    {
        $this->catalogPromotion = $catalogPromotionUpdate instanceof CatalogPromotionUpdateInterface ? (int) $catalogPromotionUpdate->getId() : $catalogPromotionUpdate;
    }
}
