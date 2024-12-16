<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Applicator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicator;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;

final class RuntimePromotionsApplicatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PromotionRepositoryInterface> */
    private ObjectProphecy $promotionRepository;

    /** @var ObjectProphecy<RuntimeCheckerInterface> */
    private ObjectProphecy $runtimeChecker;

    private RuntimePromotionsApplicatorInterface $applicator;

    protected function setUp(): void
    {
        $this->promotionRepository = $this->prophesize(PromotionRepositoryInterface::class);
        $this->runtimeChecker = $this->prophesize(RuntimeCheckerInterface::class);
        $this->applicator = new RuntimePromotionsApplicator($this->promotionRepository->reveal(), $this->runtimeChecker->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_apply_promotions(): void
    {
        $this->runtimeChecker->isEligible(Argument::any())->shouldNotBeCalled();
        $this->promotionRepository->findOneByCode(Argument::any())->shouldNotBeCalled();

        $price = 1000;
        $result = $this->applicator->apply([], $price, false);
        $this->assertSame($price, $result);
    }

    /**
     * @test
     */
    public function it_applies_promotions(): void
    {
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->getMultiplier()->willReturn(0.9);
        $promotion->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion->isExclusive()->willReturn(false);

        $this->promotionRepository->findOneByCode('PROMO1')->willReturn($promotion->reveal());
        $this->runtimeChecker->isEligible($promotion->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply(['PROMO1'], $price, false);
        $this->assertSame(900, $result);
    }

    /**
     * @test
     */
    public function it_applies_with_exclusive_promotion(): void
    {
        $promotion1 = $this->prophesize(PromotionInterface::class);
        $promotion1->getMultiplier()->willReturn(0.8);
        $promotion1->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion1->isExclusive()->willReturn(true);
        $promotion1->getPriority()->willReturn(1);

        $promotion2 = $this->prophesize(PromotionInterface::class);
        $promotion2->getMultiplier()->willReturn(0.85);
        $promotion2->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion2->isExclusive()->willReturn(true);
        $promotion2->getPriority()->willReturn(2);

        $promotion3 = $this->prophesize(PromotionInterface::class);
        $promotion3->getMultiplier()->willReturn(0.9);
        $promotion3->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion3->isExclusive()->willReturn(false);

        $this->promotionRepository->findOneByCode('PROMO1')->willReturn($promotion1->reveal());
        $this->promotionRepository->findOneByCode('PROMO2')->willReturn($promotion2->reveal());
        $this->promotionRepository->findOneByCode('PROMO3')->willReturn($promotion3->reveal());
        $this->runtimeChecker->isEligible($promotion1->reveal())->willReturn(true);
        $this->runtimeChecker->isEligible($promotion2->reveal())->willReturn(true);
        $this->runtimeChecker->isEligible($promotion3->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply(['PROMO1', 'PROMO2', 'PROMO3'], $price, false);
        $this->assertSame(850, $result);
    }

    /**
     * @test
     */
    public function it_applies_with_manually_discounted_exclusion(): void
    {
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->getMultiplier()->willReturn(0.9);
        $promotion->isManuallyDiscountedProductsExcluded()->willReturn(true);

        $this->promotionRepository->findOneByCode('PROMO1')->willReturn($promotion->reveal());
        $this->runtimeChecker->isEligible($promotion->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply(['PROMO1'], $price, true);
        $this->assertSame($price, $result);
    }
}
