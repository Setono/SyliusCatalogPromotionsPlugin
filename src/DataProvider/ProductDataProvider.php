<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Event\DataProviderQueryBuilderCreatedEvent;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

final class ProductDataProvider implements ProductDataProviderInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,

        /** @var class-string<ProductInterface> $productClass */
        private readonly string $productClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getProducts(): \Generator
    {
        $qb = $this
            ->getManager($this->productClass)
            ->createQueryBuilder()
            ->select('product')
            ->from($this->productClass, 'product')
        ;

        $this->eventDispatcher->dispatch(new DataProviderQueryBuilderCreatedEvent($qb));

        /** @var SimpleBatchIteratorAggregate<array-key, ProductInterface> $iterator */
        $iterator = SimpleBatchIteratorAggregate::fromQuery($qb->getQuery(), 100);

        yield from $iterator;
    }
}
