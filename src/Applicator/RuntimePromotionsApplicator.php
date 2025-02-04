<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Event\CatalogPromotionsAppliedEvent;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;

final class RuntimePromotionsApplicator implements RuntimePromotionsApplicatorInterface
{
    use ORMTrait;

    /** @var array<string, CatalogPromotionInterface|null> */
    private array $catalogPromotionCache = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly RuntimeCheckerInterface $runtimeChecker,
        // todo make this optional to speed up the application process
        private readonly EventDispatcherInterface $eventDispatcher,
        /** @var class-string<CatalogPromotionInterface> $catalogPromotionClass */
        private readonly string $catalogPromotionClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function apply(ProductInterface $product, int $price, int $originalPrice = null): int
    {
        $originalPrice = $originalPrice ?? $price;
        $manuallyDiscounted = $price < $originalPrice;

        $catalogPromotions = $product->getPreQualifiedCatalogPromotions();

        if ([] === $catalogPromotions) {
            return $price;
        }

        $catalogPromotions = $this->provideEligibleCatalogPromotions($catalogPromotions, $manuallyDiscounted);
        foreach ($catalogPromotions as $catalogPromotion) {
            if (!$catalogPromotion->isManuallyDiscountedProductsExcluded() && $catalogPromotion->isUsingOriginalPriceAsBase()) {
                $price = $originalPrice;

                break;
            }
        }

        $multiplier = 1.0;

        $appliedCatalogPromotions = [];
        foreach ($catalogPromotions as $catalogPromotion) {
            $multiplier *= 1 - $catalogPromotion->getDiscount();

            $appliedCatalogPromotions[] = $catalogPromotion;
        }

        if ([] !== $appliedCatalogPromotions) {
            $this->eventDispatcher->dispatch(new CatalogPromotionsAppliedEvent($product, $appliedCatalogPromotions));
        }

        return (int) floor($price * $multiplier);
    }

    /**
     * @param list<string> $catalogPromotions
     *
     * @return list<CatalogPromotionInterface>
     */
    private function provideEligibleCatalogPromotions(array $catalogPromotions, bool $manuallyDiscounted): array
    {
        $eligiblePromotions = [];
        $eligibleExclusivePromotions = [];

        foreach ($catalogPromotions as $catalogPromotion) {
            $catalogPromotion = $this->cacheCatalogPromotion($catalogPromotion);
            if (null === $catalogPromotion) {
                continue;
            }

            if ($manuallyDiscounted && $catalogPromotion->isManuallyDiscountedProductsExcluded()) {
                continue;
            }

            if (!$this->runtimeChecker->isEligible($catalogPromotion)) {
                continue;
            }

            $eligiblePromotions[] = $catalogPromotion;

            if ($catalogPromotion->isExclusive()) {
                $eligibleExclusivePromotions[$catalogPromotion->getPriority()] = $catalogPromotion;
            }
        }

        if ([] !== $eligibleExclusivePromotions) {
            krsort($eligibleExclusivePromotions, \SORT_NUMERIC);

            return [reset($eligibleExclusivePromotions)];
        }

        return $eligiblePromotions;
    }

    private function cacheCatalogPromotion(string $catalogPromotion): ?CatalogPromotionInterface
    {
        if (!array_key_exists($catalogPromotion, $this->catalogPromotionCache) || (null !== $this->catalogPromotionCache[$catalogPromotion] && !$this->getManager($this->catalogPromotionClass)->contains($this->catalogPromotionCache[$catalogPromotion]))) {
            $this->catalogPromotionCache[$catalogPromotion] = $this->getRepository($this->catalogPromotionClass, CatalogPromotionRepositoryInterface::class)->findOneByCode($catalogPromotion);
        }

        return $this->catalogPromotionCache[$catalogPromotion];
    }
}
