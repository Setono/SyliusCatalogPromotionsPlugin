<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;

/**
 * @internal
 */
final class CheckCatalogPromotionUpdate implements AsyncCommandInterface
{
    public int $tries = 0;

    public readonly int $catalogPromotionUpdate;

    public function __construct(CatalogPromotionUpdateInterface $catalogPromotionUpdate)
    {
        $this->catalogPromotionUpdate = (int) $catalogPromotionUpdate->getId();
    }
}
