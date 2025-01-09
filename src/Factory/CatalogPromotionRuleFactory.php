<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use InvalidArgumentException;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\ContainsProductRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\ContainsProductsRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\HasNotTaxonRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\HasTaxonRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class CatalogPromotionRuleFactory implements CatalogPromotionRuleFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decoratedFactory, private readonly array $rules)
    {
    }

    public function createNew(): CatalogPromotionRuleInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, CatalogPromotionRuleInterface::class);

        return $obj;
    }

    public function createByType(string $type, array $configuration, bool $strict = false): CatalogPromotionRuleInterface
    {
        switch ($type) {
            case HasTaxonRuleChecker::TYPE:
                Assert::keyExists($configuration, 'taxons');
                Assert::isArray($configuration['taxons']);

                return $this->createHasTaxon($configuration['taxons']);
            case HasNotTaxonRuleChecker::TYPE:
                Assert::keyExists($configuration, 'taxons');
                Assert::isArray($configuration['taxons']);

                return $this->createHasNotTaxon($configuration['taxons']);
            case ContainsProductRuleChecker::TYPE:
                Assert::keyExists($configuration, 'product');
                Assert::string($configuration['product']);

                return $this->createContainsProduct($configuration['product']);
            case ContainsProductsRuleChecker::TYPE:
                Assert::keyExists($configuration, 'products');
                Assert::isArray($configuration['products']);

                return $this->createContainsProducts($configuration['products']);
        }

        if ($strict) {
            throw new InvalidArgumentException(sprintf(
                'Type must be one of: %s',
                implode(', ', array_keys($this->rules)),
            ));
        }

        return $this->createPromotionRule($type, $configuration);
    }

    public function createHasTaxon(array $taxonCodes): CatalogPromotionRuleInterface
    {
        Assert::allString($taxonCodes);

        return $this->createPromotionRule(
            HasTaxonRuleChecker::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createHasNotTaxon(array $taxonCodes): CatalogPromotionRuleInterface
    {
        Assert::allString($taxonCodes);

        return $this->createPromotionRule(
            HasNotTaxonRuleChecker::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createContainsProduct(string $productCode): CatalogPromotionRuleInterface
    {
        return $this->createPromotionRule(
            ContainsProductRuleChecker::TYPE,
            ['product' => $productCode],
        );
    }

    public function createContainsProducts(array $productCodes): CatalogPromotionRuleInterface
    {
        Assert::allString($productCodes);

        return $this->createPromotionRule(
            ContainsProductsRuleChecker::TYPE,
            ['products' => $productCodes],
        );
    }

    private function createPromotionRule(string $type, array $configuration): CatalogPromotionRuleInterface
    {
        $rule = $this->createNew();
        $rule->setType($type);
        $rule->setConfiguration($configuration);

        return $rule;
    }
}
