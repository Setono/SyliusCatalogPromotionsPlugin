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
                            'type' => 'twig',
                            'label' => 'sylius.ui.state',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/state.html.twig',
                                'vars' => [
                                    'labels' => '@SetonoSyliusCatalogPromotionPlugin/admin/catalog_promotion_update/label/state',
                                ],
                            ],
                        ],
                        'triggeredBy' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_catalog_promotion.ui.triggered_by',
                            'sortable' => null,
                        ],
                        'productsUpdated' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_catalog_promotion.ui.products_updated',
                            'sortable' => 'productsUpdated',
                            'path' => '.',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/products_updated.html.twig',
                            ],
                        ],
                        'catalogPromotions' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_catalog_promotion.ui.catalog_promotions',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/catalog_promotion_list.html.twig',
                            ],
                        ],
                        'products' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.products',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/product_list.html.twig',
                            ],
                        ],
                        'error' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.error',
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/error.html.twig',
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
                    'actions' => [
                        'main' => [
                            'back_to_catalog_promotions' => [
                                'type' => 'default',
                                'label' => 'setono_sylius_catalog_promotion.ui.back_to_catalog_promotions',
                                'icon' => 'chevron left',
                                'options' => [
                                    'link' => [
                                        'route' => 'setono_sylius_catalog_promotion_admin_catalog_promotion_index',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'setono_sylius_catalog_promotion_admin_catalog_promotion' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => '%setono_sylius_catalog_promotion.model.catalog_promotion.class%',
                        ],
                    ],
                    'sorting' => [
                        'exclusive' => 'desc',
                        'priority' => 'desc',
                    ],
                    'limits' => [100, 200, 500],
                    'fields' => [
                        'name' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.name',
                            'path' => '.',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/nameAndDescription.html.twig',
                            ],
                        ],
                        'code' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.code',
                            'sortable' => null,
                        ],
                        'discount' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.discount',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/discount.html.twig',
                            ],
                        ],
                        'priority' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.priority',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/position.html.twig',
                            ],
                        ],
                        'exclusive' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.exclusive',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/yesNo.html.twig',
                            ],
                        ],
                        'manuallyDiscountedProductsExcluded' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_catalog_promotion.ui.manually_discounted_products_excluded',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/yesNo.html.twig',
                            ],
                        ],
                        'channels' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.channels',
                            'options' => [
                                'template' => '@SyliusAdmin/Grid/Field/_channels.html.twig',
                            ],
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.enabled',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/enabled.html.twig',
                            ],
                        ],
                        'startsAt' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.starts_at',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/starts_at.html.twig',
                            ],
                        ],
                        'endsAt' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.ends_at',
                            'sortable' => null,
                            'options' => [
                                'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/grid/field/ends_at.html.twig',
                            ],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'list_of_updates' => [
                                'type' => 'default',
                                'label' => 'setono_sylius_catalog_promotion.ui.list_of_updates',
                                'icon' => 'list',
                                'options' => [
                                    'link' => [
                                        'route' => 'setono_sylius_catalog_promotion_admin_catalog_promotion_update_index',
                                    ],
                                ],
                            ],
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

        $container->prependExtensionConfig('sylius_ui', [
            'events' => [
                'setono_sylius_catalog_promotion.admin.catalog_promotion_update.index.javascripts' => [
                    'blocks' => [
                        'javascripts' => [
                            'template' => '@SetonoSyliusCatalogPromotionPlugin/admin/catalog_promotion_update/_javascripts.html.twig',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
