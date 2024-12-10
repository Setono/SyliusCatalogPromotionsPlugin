<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Applicator;

use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;

final class RuntimePromotionsApplicator implements RuntimePromotionsApplicatorInterface
{
    /** @var array<string, float> */
    private array $multiplierCache = [];

    /** @var array<string, PromotionInterface|null> */
    private array $catalogPromotionCache = [];

    public function __construct(
        private readonly PromotionRepositoryInterface $promotionRepository,
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

            foreach ($this->getEligiblePromotions($catalogPromotions, $manuallyDiscounted) as $promotion) {
                $multiplier *= $promotion->getMultiplier();
            }

            $this->multiplierCache[$cacheKey] = $multiplier;
        }

        return $this->multiplierCache[$cacheKey];
    }

    /**
     * @param list<string> $catalogPromotions
     *
     * @return \Generator<array-key, PromotionInterface>
     */
    private function getEligiblePromotions(array $catalogPromotions, bool $manuallyDiscounted): \Generator
    {
        // todo check if any of the promotions are exclusive
        foreach ($catalogPromotions as $catalogPromotion) {
            if (!array_key_exists($catalogPromotion, $this->catalogPromotionCache)) {
                $this->catalogPromotionCache[$catalogPromotion] = $this->promotionRepository->findOneByCode($catalogPromotion);
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

            yield $this->catalogPromotionCache[$catalogPromotion];
        }
    }
}
