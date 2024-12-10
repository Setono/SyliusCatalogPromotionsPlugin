<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;

final class EnabledRuntimeChecker implements RuntimeCheckerInterface
{
    public function isEligible(PromotionInterface $catalogPromotion): bool
    {
        return $catalogPromotion->isEnabled();
    }
}
