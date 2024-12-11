<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule;

use Sylius\Component\Core\Model\ProductInterface;
use Webmozart\Assert\Assert;

final class ContainsProductsRuleChecker implements RuleCheckerInterface
{
    public const TYPE = 'contains_products';

    public function isEligible(ProductInterface $product, array $configuration): bool
    {
        Assert::keyExists($configuration, 'products');
        Assert::isArray($configuration['products']);
        Assert::allStringNotEmpty($configuration['products']);

        return in_array($product->getCode(), $configuration['products'], true);
    }
}
