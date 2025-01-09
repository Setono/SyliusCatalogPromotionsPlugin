<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification;

use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\RuleCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Webmozart\Assert\Assert;

final class RulesPreQualificationChecker implements PreQualificationCheckerInterface
{
    public function __construct(private readonly ServiceRegistryInterface $ruleRegistry)
    {
    }

    public function isPreQualified(ProductInterface $product, CatalogPromotionInterface $catalogPromotion): bool
    {
        if (!$catalogPromotion->hasRules()) {
            return true;
        }

        foreach ($catalogPromotion->getRules() as $rule) {
            if (!$this->isEligibleToRule($product, $rule)) {
                return false;
            }
        }

        return true;
    }

    private function isEligibleToRule(ProductInterface $product, CatalogPromotionRuleInterface $rule): bool
    {
        /** @var RuleCheckerInterface|object $checker */
        $checker = $this->ruleRegistry->get((string) $rule->getType());
        Assert::isInstanceOf($checker, RuleCheckerInterface::class);

        return $checker->isEligible($product, $rule->getConfiguration());
    }
}
