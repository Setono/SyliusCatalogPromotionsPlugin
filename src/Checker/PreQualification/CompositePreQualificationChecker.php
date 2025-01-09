<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;

/**
 * @extends CompositeService<PreQualificationCheckerInterface>
 */
final class CompositePreQualificationChecker extends CompositeService implements PreQualificationCheckerInterface
{
    public function isPreQualified(ProductInterface $product, CatalogPromotionInterface $catalogPromotion): bool
    {
        foreach ($this->services as $service) {
            if (!$service->isPreQualified($product, $catalogPromotion)) {
                return false;
            }
        }

        return true;
    }
}
