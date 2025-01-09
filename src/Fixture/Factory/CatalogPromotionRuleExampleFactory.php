<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Fixture\Factory;

use Setono\SyliusCatalogPromotionPlugin\Factory\CatalogPromotionRuleFactoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatalogPromotionRuleExampleFactory extends AbstractExampleFactory
{
    protected OptionsResolver $optionsResolver;

    public function __construct(
        protected readonly CatalogPromotionRuleFactoryInterface $catalogPromotionRuleFactory,
        protected readonly array $catalogPromotionRules,
    ) {
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): CatalogPromotionRuleInterface
    {
        $options = $this->optionsResolver->resolve($options);

        return $this->catalogPromotionRuleFactory->createByType(
            $options['type'],
            $options['configuration'],
        );
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('type', function (): string {
                $codes = array_keys($this->catalogPromotionRules);

                return $codes[array_rand($codes)];
            })
            ->setDefined('configuration')
            ->setAllowedTypes('configuration', ['string', 'array'])
        ;
    }
}
