<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface PromotionRepositoryInterface extends RepositoryInterface
{
    /**
     * @param list<string> $catalogPromotions a list of catalog promotion codes to filter by
     *
     * @return list<PromotionInterface>
     */
    public function findForProcessing(array $catalogPromotions = []): array;

    public function findOneByCode(string $code): ?PromotionInterface;
}
