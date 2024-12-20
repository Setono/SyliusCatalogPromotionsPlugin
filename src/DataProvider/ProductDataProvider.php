<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DataProvider;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineBatchUtils\BatchProcessing\SelectBatchIteratorAggregate;
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

    public function getIds(array $ids = []): \Generator|array
    {
        $qb = $this->createQueryBuilder($ids)->select('DISTINCT product.id');

        /** @var SelectBatchIteratorAggregate<array-key, int> $iterator */
        $iterator = SelectBatchIteratorAggregate::fromQuery($qb->getQuery(), 500);

        yield from $iterator;
    }

    public function getProducts(array $ids): array
    {
        /** @var list<ProductInterface> $products */
        $products = $this->createQueryBuilder($ids)->getQuery()->getResult();

        return $products;
    }

    private function createQueryBuilder(array $ids = []): QueryBuilder
    {
        $qb = $this
            ->getManager($this->productClass)
            ->createQueryBuilder()
            ->select('product')
            ->from($this->productClass, 'product')
        ;

        if ([] !== $ids) {
            $qb->andWhere('product.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $this->eventDispatcher->dispatch(new DataProviderQueryBuilderCreatedEvent($qb));

        return $qb;
    }
}
