<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class PromotionFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_catalog_promotion';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        /** @psalm-suppress MixedMethodCall,UndefinedInterfaceMethod,PossiblyNullReference */
        $resourceNode
            ->children()
                ->scalarNode('code')->cannotBeEmpty()->end()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('description')->cannotBeEmpty()->end()

                ->scalarNode('priority')->cannotBeEmpty()->end()
                ->booleanNode('exclusive')->end()

                ->scalarNode('starts_at')->cannotBeEmpty()->end()
                ->scalarNode('ends_at')->cannotBeEmpty()->end()
                ->booleanNode('enabled')->end()

                ->floatNode('discount')->end()

                ->scalarNode('created_at')->cannotBeEmpty()->end()
                ->scalarNode('updated_at')->cannotBeEmpty()->end()

                ->arrayNode('rules')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')->cannotBeEmpty()->end()
                            ->variableNode('configuration')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('channels')->scalarPrototype()->end()
        ;
    }
}
