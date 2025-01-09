<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;

final class EnabledRuntimeChecker implements RuntimeCheckerInterface
{
    public function isEligible(CatalogPromotionInterface $catalogPromotion): bool
    {
        return $catalogPromotion->isEnabled();
    }
}
