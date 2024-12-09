<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use InvalidArgumentException;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionRuleInterface;
use Setono\SyliusCatalogPromotionPlugin\Rule\ContainsProductRule;
use Setono\SyliusCatalogPromotionPlugin\Rule\ContainsProductsRule;
use Setono\SyliusCatalogPromotionPlugin\Rule\HasNotTaxonRule;
use Setono\SyliusCatalogPromotionPlugin\Rule\HasTaxonRule;
use function sprintf;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class PromotionRuleFactory implements PromotionRuleFactoryInterface
{
    private FactoryInterface $decoratedFactory;

    private array $rules;

    public function __construct(
        FactoryInterface $decoratedFactory,
        array $rules,
    ) {
        $this->decoratedFactory = $decoratedFactory;
        $this->rules = $rules;
    }

    public function createNew(): PromotionRuleInterface
    {
        /** @var PromotionRuleInterface $obj */
        $obj = $this->decoratedFactory->createNew();

        return $obj;
    }

    public function createByType(string $type, array $configuration, bool $strict = false): PromotionRuleInterface
    {
        switch ($type) {
            case HasTaxonRule::TYPE:
                Assert::keyExists($configuration, 'taxons');
                Assert::isArray($configuration['taxons']);

                return $this->createHasTaxon($configuration['taxons']);
            case HasNotTaxonRule::TYPE:
                Assert::keyExists($configuration, 'taxons');
                Assert::isArray($configuration['taxons']);

                return $this->createHasNotTaxon($configuration['taxons']);
            case ContainsProductRule::TYPE:
                Assert::keyExists($configuration, 'product');
                Assert::string($configuration['product']);

                return $this->createContainsProduct($configuration['product']);
            case ContainsProductsRule::TYPE:
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
            HasTaxonRule::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createHasNotTaxon(array $taxonCodes): PromotionRuleInterface
    {
        Assert::allString($taxonCodes);

        return $this->createPromotionRule(
            HasNotTaxonRule::TYPE,
            ['taxons' => $taxonCodes],
        );
    }

    public function createContainsProduct(string $productCode): PromotionRuleInterface
    {
        return $this->createPromotionRule(
            ContainsProductRule::TYPE,
            ['product' => $productCode],
        );
    }

    public function createContainsProducts(array $productCodes): PromotionRuleInterface
    {
        Assert::allString($productCodes);

        return $this->createPromotionRule(
            ContainsProductsRule::TYPE,
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
