<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Event\CatalogPromotionAppliedEvent;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;

final class RuntimePromotionsApplicator implements RuntimePromotionsApplicatorInterface
{
    use ORMTrait;

    /** @var array<string, float> */
    private array $multiplierCache = [];

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

    public function apply(ProductInterface $product, int $price, bool $manuallyDiscounted): int
    {
        $catalogPromotions = $product->getPreQualifiedCatalogPromotions();

        if ([] === $catalogPromotions) {
            return $price;
        }

        $appliedPrice = (int) floor($this->getMultiplier($catalogPromotions, $manuallyDiscounted) * $price);
        if ($appliedPrice !== $price) {
            $this->eventDispatcher->dispatch(new CatalogPromotionAppliedEvent($product));
        }

        return $appliedPrice;
    }

    /**
     * @param list<string> $catalogPromotions
     */
    private function getMultiplier(array $catalogPromotions, bool $manuallyDiscounted): float
    {
        $cacheKey = sprintf('%s%d', implode($catalogPromotions), (int) $manuallyDiscounted);

        if (!isset($this->multiplierCache[$cacheKey])) {
            $multiplier = 1.0;

            foreach ($this->getEligibleCatalogPromotions($catalogPromotions, $manuallyDiscounted) as $catalogPromotion) {
                $multiplier *= $catalogPromotion->getMultiplier();
            }

            $this->multiplierCache[$cacheKey] = $multiplier;
        }

        return $this->multiplierCache[$cacheKey];
    }

    /**
     * @param list<string> $catalogPromotions
     *
     * @return \Generator<array-key, CatalogPromotionInterface>
     */
    private function getEligibleCatalogPromotions(array $catalogPromotions, bool $manuallyDiscounted): \Generator
    {
        $eligiblePromotions = [];
        $eligibleExclusivePromotions = [];

        foreach ($catalogPromotions as $catalogPromotion) {
            $this->ensureCatalogPromotionCache($catalogPromotion);

            if (null === $this->catalogPromotionCache[$catalogPromotion]) {
                continue;
            }

            if ($manuallyDiscounted && $this->catalogPromotionCache[$catalogPromotion]->isManuallyDiscountedProductsExcluded()) {
                continue;
            }

            if (!$this->runtimeChecker->isEligible($this->catalogPromotionCache[$catalogPromotion])) {
                continue;
            }

            $eligiblePromotions[] = $this->catalogPromotionCache[$catalogPromotion];

            if ($this->catalogPromotionCache[$catalogPromotion]->isExclusive()) {
                $eligibleExclusivePromotions[$this->catalogPromotionCache[$catalogPromotion]->getPriority()] = $this->catalogPromotionCache[$catalogPromotion];
            }
        }

        if ([] !== $eligibleExclusivePromotions) {
            krsort($eligibleExclusivePromotions, \SORT_NUMERIC);
            yield reset($eligibleExclusivePromotions);
        } else {
            yield from $eligiblePromotions;
        }
    }

    private function ensureCatalogPromotionCache(string $catalogPromotion): void
    {
        if (array_key_exists($catalogPromotion, $this->catalogPromotionCache)) {
            if (null === $this->catalogPromotionCache[$catalogPromotion]) {
                return;
            }

            // If the entity is still managed, we don't need to do anything
            if ($this->getManager($this->catalogPromotionClass)->contains($this->catalogPromotionCache[$catalogPromotion])) {
                return;
            }
        }

        $this->catalogPromotionCache[$catalogPromotion] = $this->getRepository($this->catalogPromotionClass, CatalogPromotionRepositoryInterface::class)->findOneByCode($catalogPromotion);
    }
}
