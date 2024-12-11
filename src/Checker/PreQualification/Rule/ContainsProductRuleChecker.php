<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule;

use Sylius\Component\Core\Model\ProductInterface;
use Webmozart\Assert\Assert;

final class ContainsProductRuleChecker implements RuleCheckerInterface
{
    public const TYPE = 'contains_product';

    public function isEligible(ProductInterface $product, array $configuration): bool
    {
        Assert::keyExists($configuration, 'product');
        Assert::stringNotEmpty($configuration['product']);

        return $configuration['product'] === $product->getCode();
    }
}
