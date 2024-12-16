<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Checker\PreQualification;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\RuleCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\RulesPreQualificationChecker;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionRuleInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Webmozart\Assert\InvalidArgumentException;

final class RulesPreQualificationCheckerTest extends TestCase
{
    use ProphecyTrait;

    private RulesPreQualificationChecker $checker;

    /** @var ObjectProphecy<ServiceRegistryInterface> */
    private ObjectProphecy $ruleRegistry;

    protected function setUp(): void
    {
        $this->ruleRegistry = $this->prophesize(ServiceRegistryInterface::class);
        $this->checker = new RulesPreQualificationChecker($this->ruleRegistry->reveal());
    }

    /**
     * @test
     */
    public function it_returns_true_if_promotion_has_no_rules(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->hasRules()->willReturn(false);

        self::assertTrue($this->checker->isPreQualified($product->reveal(), $promotion->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_true_if_all_rules_are_eligible(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $rule = $this->prophesize(PromotionRuleInterface::class);
        $checker = $this->prophesize(RuleCheckerInterface::class);

        $promotion->hasRules()->willReturn(true);
        $promotion->getRules()->willReturn(new ArrayCollection([$rule->reveal()]));
        $rule->getType()->willReturn('rule_type');
        $rule->getConfiguration()->willReturn([]);
        $this->ruleRegistry->get('rule_type')->willReturn($checker->reveal());
        $checker->isEligible($product->reveal(), [])->willReturn(true);

        self::assertTrue($this->checker->isPreQualified($product->reveal(), $promotion->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_false_if_any_rule_is_not_eligible(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $rule = $this->prophesize(PromotionRuleInterface::class);
        $checker = $this->prophesize(RuleCheckerInterface::class);

        $promotion->hasRules()->willReturn(true);
        $promotion->getRules()->willReturn(new ArrayCollection([$rule->reveal()]));
        $rule->getType()->willReturn('rule_type');
        $rule->getConfiguration()->willReturn([]);
        $this->ruleRegistry->get('rule_type')->willReturn($checker->reveal());
        $checker->isEligible($product->reveal(), [])->willReturn(false);

        self::assertFalse($this->checker->isPreQualified($product->reveal(), $promotion->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_checker_is_not_instance_of_rule_checker_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $promotion = $this->prophesize(PromotionInterface::class);
        $rule = $this->prophesize(PromotionRuleInterface::class);

        $promotion->hasRules()->willReturn(true);
        $promotion->getRules()->willReturn(new ArrayCollection([$rule->reveal()]));
        $rule->getType()->willReturn('rule_type');
        $this->ruleRegistry->get('rule_type')->willReturn(new \stdClass());

        $this->checker->isPreQualified($product->reveal(), $promotion->reveal());
    }
}
