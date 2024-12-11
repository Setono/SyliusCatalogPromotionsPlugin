<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Event;

use Doctrine\ORM\QueryBuilder;

final class DataProviderQueryBuilderCreatedEvent
{
    public function __construct(public readonly QueryBuilder $queryBuilder)
    {
    }
}
