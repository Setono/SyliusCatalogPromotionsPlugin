<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface CatalogPromotionUpdateFactoryInterface extends FactoryInterface
{
    public function createNew(): CatalogPromotionUpdateInterface;

    public function createFromMessage(StartCatalogPromotionUpdate $message): CatalogPromotionUpdateInterface;
}
