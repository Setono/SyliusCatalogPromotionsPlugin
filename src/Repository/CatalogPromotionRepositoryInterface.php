<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CatalogPromotionRepositoryInterface extends RepositoryInterface
{
    /**
     * @param list<string> $catalogPromotions a list of catalog promotion codes to filter by
     *
     * @return list<CatalogPromotionInterface>
     */
    public function findForProcessing(array $catalogPromotions = []): array;

    public function findOneForProcessing(string $code): ?CatalogPromotionInterface;

    public function findOneByCode(string $code): ?CatalogPromotionInterface;
}
