<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;

/**
 * @extends CompositeService<RuntimeCheckerInterface>
 */
final class CompositeRuntimeChecker extends CompositeService implements RuntimeCheckerInterface
{
    public function isEligible(PromotionInterface $catalogPromotion): bool
    {
        foreach ($this->services as $service) {
            if (!$service->isEligible($catalogPromotion)) {
                return false;
            }
        }

        return true;
    }
}
