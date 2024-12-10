<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface PromotionRepositoryInterface extends RepositoryInterface
{
    /**
     * @return list<PromotionInterface>
     */
    public function findForProcessing(): array;

    public function findOneByCode(string $code): ?PromotionInterface;
}
