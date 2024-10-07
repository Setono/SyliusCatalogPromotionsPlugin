<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Doctrine\ORM;

use DateTimeInterface;
use Doctrine\DBAL\TransactionIsolationLevel;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @mixin EntityRepository
 */
trait ChannelPricingRepositoryTrait
{
    use HasAnyBeenUpdatedSinceTrait;

    public function resetMultiplier(DateTimeInterface $dateTime, string $bulkIdentifier): void
    {
        \assert($this instanceof EntityRepository);

        $connection = $this->_em->getConnection();
        $oldTransactionIsolation = (int) $connection->getTransactionIsolation();
        $connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);

        do {
            $connection->beginTransaction();

            try {
                $qb = $this->createQueryBuilder('o');
                $ids = $qb
                    ->select('o.id')
                    // this ensures that the loop we are in doesn't turn into an infinite loop
                    ->andWhere(
                        $qb->expr()->orX(
                            'o.bulkIdentifier != :bulkIdentifier',
                            'o.bulkIdentifier is null'
                        )
                    )
                    ->andWhere(
                        $qb->expr()->orX(
                        // if the multiplier is different from 1 we know that it was discounted before, and we reset it
                            'o.multiplier != 1',

                            // if the previous job timed out, the bulk identifier will be different from the
                            // bulk identifier for this run. This will ensure that they will also be handled in this run
                            'o.bulkIdentifier is not null',

                            // if the applied promotions is not null we know that it was discounted before, and we reset it
                            'o.usedPromotions is not null'
                        )
                    )
                    ->setParameter('bulkIdentifier', $bulkIdentifier)
                    ->setMaxResults(100)
                    ->getQuery()
                    ->getResult()
                ;

                $res = (int) $this
                    ->createQueryBuilder('o')
                    ->update()
                    ->set('o.multiplier', 1)
                    ->set('o.updatedAt', ':updatedAt')
                    ->set('o.bulkIdentifier', ':bulkIdentifier')
                    ->set('o.usedPromotions', ':null')
                    ->andWhere('o.id IN (:ids)')
                    ->setParameter('updatedAt', $dateTime)
                    ->setParameter('bulkIdentifier', $bulkIdentifier)
                    ->setParameter('null', null)
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute()
                ;

                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();

                throw $e;
            }
        } while ($res > 0);

        $connection->setTransactionIsolation($oldTransactionIsolation);
    }

    public function updateMultiplier(
        string $promotionCode,
        float $multiplier,
        array $productVariantIds,
        array $channelCodes,
        DateTimeInterface $dateTime,
        string $bulkIdentifier,
        bool $exclusive = false,
        bool $manuallyDiscountedProductsExcluded = true
    ): void {
        \assert($this instanceof EntityRepository);

        if (count($channelCodes) === 0 || count($productVariantIds) === 0) {
            return;
        }

        $qb = $this->createQueryBuilder('channelPricing');

        $qb->update()
            ->andWhere('channelPricing.productVariant IN (:productVariantIds)')
            ->andWhere('channelPricing.channelCode IN (:channelCodes)')
            // this 'or' is a safety check. If the previous run timed out, but managed to update some multipliers
            // this will end up in discounts being compounded. With this check we ensure we only operate on pricings
            // from this run or pricings that haven't been touched
            ->andWhere($qb->expr()->orX(
                'channelPricing.bulkIdentifier is null',
                'channelPricing.bulkIdentifier = :bulkIdentifier',
            ))
            // here is another safety check. If the promotion code is already applied,
            // do not select this pricing for a discount
            ->andWhere($qb->expr()->orX(
                'channelPricing.usedPromotions IS NULL',
                $qb->expr()->andX(
                    'channelPricing.usedPromotions NOT LIKE :promotionEnding',
                    'channelPricing.usedPromotions NOT LIKE :promotionMiddle',
                )
            ))
            ->set('channelPricing.updatedAt', ':date')
            ->set('channelPricing.bulkIdentifier', ':bulkIdentifier')
            ->set('channelPricing.usedPromotions', "CONCAT(COALESCE(channelPricing.usedPromotions, ''), CONCAT(',', :promotion))")
            ->setParameter('productVariantIds', $productVariantIds)
            ->setParameter('channelCodes', $channelCodes)
            ->setParameter('date', $dateTime)
            ->setParameter('bulkIdentifier', $bulkIdentifier)
            ->setParameter('promotion', $promotionCode)
            // if you are checking for the promo code 'all_10_percent' there are two options for the applied_promotions column:
            // 1. ,single_tshirt,all_10_percent
            // 2. ,all_10_percent,single_tshirt
            // and these two wildcards selections will handle those two options
            ->setParameter('promotionEnding', '%,' . $promotionCode)
            ->setParameter('promotionMiddle', '%,' . $promotionCode . ',%')
        ;

        if ($manuallyDiscountedProductsExcluded) {
            $qb->andWhere('channelPricing.manuallyDiscounted = false');
        }

        if ($exclusive) {
            $qb->set('channelPricing.multiplier', ':multiplier');
        } else {
            $qb->set('channelPricing.multiplier', 'channelPricing.multiplier * :multiplier');
        }

        $qb->setParameter('multiplier', $multiplier);

        $qb->getQuery()->execute();
    }

    public function updatePrices(string $bulkIdentifier): void
    {
        \assert($this instanceof EntityRepository);

        $connection = $this->_em->getConnection();
        $oldTransactionIsolation = (int) $connection->getTransactionIsolation();
        $connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);

        do {
            $res = 0;
            $connection->beginTransaction();

            try {
                // get an array of ids to work on
                $ids = $this->createQueryBuilder('o')
                    ->select('o.id')
                    ->andWhere('o.bulkIdentifier = :bulkIdentifier')
                    ->setParameter('bulkIdentifier', $bulkIdentifier)
                    ->setMaxResults(100)
                    ->getQuery()
                    ->getResult();

                // this query handles the case where an original price is set
                // i.e. we have made discounts on this product before
                $this->createQueryBuilder('o')
                    ->update()
                    ->set('o.price', 'ROUND(o.originalPrice * o.multiplier)')
                    ->andWhere('o.originalPrice is not null')
                    ->andWhere('o.id in (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();

                // this query handles the case where a discount hasn't been applied before
                // so we want to move the current price to the original price before changing the price
                $this->createQueryBuilder('o')
                    ->update()
                    ->set('o.originalPrice', 'o.price')
                    ->set('o.price', 'ROUND(o.price * o.multiplier)')
                    ->andWhere('o.originalPrice is null')
                    ->andWhere('o.multiplier != 1')
                    ->andWhere('o.id in (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();

                // this query sets the original price to null where the original price equals the price
                $this->createQueryBuilder('o')
                    ->update()
                    ->set('o.originalPrice', ':originalPrice')
                    ->andWhere('o.price = o.originalPrice')
                    ->andWhere('o.id in (:ids)')
                    ->setParameter('ids', $ids)
                    ->setParameter('originalPrice', null)
                    ->getQuery()
                    ->execute();

                // set the bulk identifier to null to ensure the loop will come to an end ;)
                $res = (int) $this
                    ->createQueryBuilder('o')
                    ->update()
                    ->set('o.bulkIdentifier', ':null')
                    ->andWhere('o.id IN (:ids)')
                    ->setParameter('null', null)
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();

                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
            }
        } while ($res > 0);

        $connection->setTransactionIsolation($oldTransactionIsolation);
    }
}
