<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DependencyInjection\Compiler;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Webmozart\Assert\Assert;

final class RegisterRulesAndRuleCheckersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $registry = $container->getDefinition('setono_sylius_catalog_promotion.registry.rule_checker');
        $formRegistry = $container->getDefinition('setono_sylius_catalog_promotion.form_registry.rule');

        /** @var array<string, string> $formToLabelMap */
        $formToLabelMap = [];

        /**
         * @var string $id
         * @var array $tags
         */
        foreach ($container->findTaggedServiceIds('setono_sylius_catalog_promotion.rule_checker') as $id => $tags) {
            /** @var array $attributes */
            foreach ($tags as $attributes) {
                if (!isset($attributes['type'], $attributes['label'], $attributes['form_type'])) {
                    throw new InvalidArgumentException('Tagged rule checker `' . $id . '` needs to have `type`, `form_type` and `label` attributes.');
                }

                Assert::stringNotEmpty($attributes['type']);
                Assert::stringNotEmpty($attributes['label']);
                Assert::stringNotEmpty($attributes['form_type']);

                $formToLabelMap[$attributes['type']] = $attributes['label'];
                $registry->addMethodCall('register', [$attributes['type'], new Reference($id)]);
                $formRegistry->addMethodCall('add', [$attributes['type'], 'default', $attributes['form_type']]);
            }
        }

        $container->setParameter('setono_sylius_catalog_promotion.catalog_promotion_rules', $formToLabelMap);
    }
}
