<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;

final class RuntimePromotionsApplicator implements RuntimePromotionsApplicatorInterface
{
    /** @var array<string, float> */
    private array $multiplierCache = [];

    /** @var array<string, CatalogPromotionInterface|null> */
    private array $catalogPromotionCache = [];

    public function __construct(
        private readonly CatalogPromotionRepositoryInterface $catalogPromotionRepository,
        private readonly RuntimeCheckerInterface $runtimeChecker,
    ) {
    }

    public function apply(array $catalogPromotions, int $price, bool $manuallyDiscounted): int
    {
        if ([] === $catalogPromotions) {
            return $price;
        }

        return (int) floor($this->getMultiplier($catalogPromotions, $manuallyDiscounted) * $price);
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
            if (!array_key_exists($catalogPromotion, $this->catalogPromotionCache)) {
                $this->catalogPromotionCache[$catalogPromotion] = $this->catalogPromotionRepository->findOneByCode($catalogPromotion);
            }

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
}
