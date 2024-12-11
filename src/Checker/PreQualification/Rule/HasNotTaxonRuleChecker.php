<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule;

use Sylius\Component\Core\Model\ProductInterface;
use Webmozart\Assert\Assert;

final class HasNotTaxonRuleChecker implements RuleCheckerInterface
{
    public const TYPE = 'has_not_taxon';

    public function isEligible(ProductInterface $product, array $configuration): bool
    {
        Assert::keyExists($configuration, 'taxons');
        Assert::isArray($configuration['taxons']);
        Assert::allStringNotEmpty($configuration['taxons']);

        foreach ($product->getTaxons() as $taxon) {
            if (in_array($taxon->getCode(), $configuration['taxons'], true)) {
                return false;
            }
        }

        return !in_array($product->getMainTaxon()?->getCode(), $configuration['taxons'], true);
    }
}
