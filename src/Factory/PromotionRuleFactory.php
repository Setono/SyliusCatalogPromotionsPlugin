<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use InvalidArgumentException;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\ContainsProductRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\ContainsProductsRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\HasNotTaxonRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\HasTaxonRuleChecker;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionRuleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class PromotionRuleFactory implements PromotionRuleFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decoratedFactory, private readonly array $rules)
    {
    }

    public function createNew(): PromotionRuleInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, PromotionRuleInterface::class);

        return $obj;
    }

    public function createByType(string $type, array $configuration, bool $strict = false): PromotionRuleInterface
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

    public function createHasTaxon(array $taxonCodes): PromotionRuleInterface
    {
        Assert::allString($taxonCodes);

        return $this->createPromotionRule(
            HasTaxonRuleChecker::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createHasNotTaxon(array $taxonCodes): PromotionRuleInterface
    {
        Assert::allString($taxonCodes);

        return $this->createPromotionRule(
            HasNotTaxonRuleChecker::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createContainsProduct(string $productCode): PromotionRuleInterface
    {
        return $this->createPromotionRule(
            ContainsProductRuleChecker::TYPE,
            ['product' => $productCode],
        );
    }

    public function createContainsProducts(array $productCodes): PromotionRuleInterface
    {
        Assert::allString($productCodes);

        return $this->createPromotionRule(
            ContainsProductsRuleChecker::TYPE,
            ['products' => $productCodes],
        );
    }

    private function createPromotionRule(string $type, array $configuration): PromotionRuleInterface
    {
        $rule = $this->createNew();
        $rule->setType($type);
        $rule->setConfiguration($configuration);

        return $rule;
    }
}
