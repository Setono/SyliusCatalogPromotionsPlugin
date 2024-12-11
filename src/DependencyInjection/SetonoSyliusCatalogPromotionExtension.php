<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DependencyInjection;

use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\RuleCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusCatalogPromotionExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{resources: array<string, mixed>} $config
         *
         * @psalm-suppress PossiblyNullArgument
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->registerForAutoconfiguration(RuntimeCheckerInterface::class)->addTag('setono_sylius_catalog_promotion.runtime_checker');
        $container->registerForAutoconfiguration(RuleCheckerInterface::class)->addTag('setono_sylius_catalog_promotion.rule_checker');

        $this->registerResources('setono_sylius_catalog_promotion', SyliusResourceBundle::DRIVER_DOCTRINE_ORM, $config['resources'], $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_catalog_promotion_admin_promotion' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_catalog_promotion.model.promotion.class%',
                        ],
                    ],
                    'sorting' => [
                        'priority' => 'desc',
                    ],
                    'limits' => [100, 200, 500],
                    'fields' => [
                        'priority' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.priority',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/position.html.twig',
                            ],
                        ],
                        'code' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.code',
                            'sortable' => null,
                        ],
                        'name' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.name',
                            'path' => '.',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/nameAndDescription.html.twig',
                            ],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                            'update' => [
                                'type' => 'update',
                            ],
                        ],
                        'bulk' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
