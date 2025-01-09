<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Form\Type\Rule;

use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

final class ContainsProductsConfigurationType extends AbstractType
{
    private DataTransformerInterface $productsToCodesTransformer;

    public function __construct(DataTransformerInterface $productsToCodesTransformer)
    {
        $this->productsToCodesTransformer = $productsToCodesTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('products', ProductAutocompleteChoiceType::class, [
                'label' => 'setono_sylius_catalog_promotion.form.catalog_promotion_rule.contains_products_configuration.products',
                'multiple' => true,
            ])
        ;

        $builder->get('products')->addModelTransformer($this->productsToCodesTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_catalog_promotion__catalog_promotion_rule_contains_products_configuration';
    }
}
