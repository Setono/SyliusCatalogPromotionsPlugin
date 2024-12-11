<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\CompositePreQualificationChecker;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\CompositeRuntimeChecker;
use Setono\SyliusCatalogPromotionPlugin\DependencyInjection\Compiler\RegisterRulesAndRuleCheckersPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusCatalogPromotionPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterRulesAndRuleCheckersPass());
        $container->addCompilerPass(new CompositeCompilerPass(CompositePreQualificationChecker::class, 'setono_sylius_catalog_promotion.pre_qualification_checker'));
        $container->addCompilerPass(new CompositeCompilerPass(CompositeRuntimeChecker::class, 'setono_sylius_catalog_promotion.runtime_checker'));
    }
}
