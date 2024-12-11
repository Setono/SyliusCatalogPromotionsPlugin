<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule;

use Sylius\Component\Core\Model\ProductInterface;

interface RuleCheckerInterface
{
    public function isEligible(ProductInterface $product, array $configuration): bool;
}
