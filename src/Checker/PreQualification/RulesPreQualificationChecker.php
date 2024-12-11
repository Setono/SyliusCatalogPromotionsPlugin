<?php
declare(strict_types=1);


namespace Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification;


use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\RuleCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionRuleInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;

final class RulesPreQualificationChecker implements PreQualificationCheckerInterface
{
    public function __construct(private readonly ServiceRegistryInterface $ruleRegistry)
    {
    }

    public function isPreQualified(ProductInterface $product, PromotionInterface $catalogPromotion): bool
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

    private function isEligibleToRule(ProductInterface $product, PromotionRuleInterface $rule): bool
    {
        /** @var RuleCheckerInterface $checker */
        $checker = $this->ruleRegistry->get($rule->getType());

        return $checker->isEligible($product, $rule->getConfiguration());
    }
}
