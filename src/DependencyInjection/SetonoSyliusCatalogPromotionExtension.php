<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\DependencyInjection;

use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\RuleCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Workflow\CatalogPromotionUpdateWorkflow;
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
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'setono_sylius_catalog_promotion.command_bus' => [
                        'middleware' => null,
                    ],
                ],
            ],
            'workflows' => CatalogPromotionUpdateWorkflow::getConfig(),
        ]);

        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_catalog_promotion_admin_catalog_promotion_update' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_catalog_promotion.model.catalog_promotion_update.class%',
                        ],
                    ],
                    'sorting' => [
                        'createdAt' => 'desc',
                    ],
                    'limits' => [100, 200, 500],
                    'fields' => [
                        'state' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.state',
                            'sortable' => null,
                        ],
                        'triggeredBy' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_catalog_promotion.ui.triggered_by',
                            'sortable' => null,
                        ],
                        'catalogPromotions' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_catalog_promotion.ui.catalog_promotions',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/Admin/grid/field/catalog_promotion_list.html.twig',
                            ],
                        ],
                        'products' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.products',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/Admin/grid/field/product_list.html.twig',
                            ],
                        ],
                        'createdAt' => [
                            'type' => 'datetime',
                            'label' => 'sylius.ui.created_at',
                            'sortable' => null,
                        ],
                        'updatedAt' => [
                            'type' => 'datetime',
                            'label' => 'setono_sylius_catalog_promotion.ui.updated_at',
                            'sortable' => null,
                        ],
                    ],
// todo add show action
//                    'actions' => [
//                        'item' => [
//                            'show' => [
//                                'type' => 'show',
//                            ],
//                        ],
//                    ],
                ],
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
