<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Form\Type;

use Sylius\Bundle\PromotionBundle\Form\Type\Core\AbstractConfigurationCollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CatalogPromotionRuleCollectionType extends AbstractConfigurationCollectionType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('entry_type', CatalogPromotionRuleType::class);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_catalog_promotion__catalog_promotion_rule_collection';
    }
}
