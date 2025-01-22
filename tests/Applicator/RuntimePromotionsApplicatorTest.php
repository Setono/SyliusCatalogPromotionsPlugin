<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Applicator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicator;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;

final class RuntimePromotionsApplicatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CatalogPromotionRepositoryInterface> */
    private ObjectProphecy $promotionRepository;

    /** @var ObjectProphecy<RuntimeCheckerInterface> */
    private ObjectProphecy $runtimeChecker;

    private RuntimePromotionsApplicatorInterface $applicator;

    protected function setUp(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->promotionRepository = $this->prophesize(CatalogPromotionRepositoryInterface::class);
        $this->runtimeChecker = $this->prophesize(RuntimeCheckerInterface::class);
        $this->applicator = new RuntimePromotionsApplicator($this->promotionRepository->reveal(), $this->runtimeChecker->reveal(), $eventDispatcher->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_apply_catalog_promotions(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn([]);

        $this->runtimeChecker->isEligible(Argument::any())->shouldNotBeCalled();
        $this->promotionRepository->findOneByCode(Argument::any())->shouldNotBeCalled();

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, false);
        $this->assertSame($price, $result);
    }

    /**
     * @test
     */
    public function it_applies_catalog_promotions(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1']);

        $promotion = $this->prophesize(CatalogPromotionInterface::class);
        $promotion->getMultiplier()->willReturn(0.9);
        $promotion->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion->isExclusive()->willReturn(false);

        $this->promotionRepository->findOneByCode('PROMO1')->willReturn($promotion->reveal());
        $this->runtimeChecker->isEligible($promotion->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, false);
        $this->assertSame(900, $result);
    }

    /**
     * @test
     */
    public function it_applies_with_exclusive_catalog_promotion(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1', 'PROMO2', 'PROMO3']);

        $promotion1 = $this->prophesize(CatalogPromotionInterface::class);
        $promotion1->getMultiplier()->willReturn(0.8);
        $promotion1->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion1->isExclusive()->willReturn(true);
        $promotion1->getPriority()->willReturn(1);

        $promotion2 = $this->prophesize(CatalogPromotionInterface::class);
        $promotion2->getMultiplier()->willReturn(0.85);
        $promotion2->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $promotion2->isExclusive()->willReturn(true);
        $promotion2->getPriority()->willReturn(2);

        $promotion3 = $this->prophesize(CatalogPromotionInterface::class);
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
        $result = $this->applicator->apply($product->reveal(), $price, false);
        $this->assertSame(850, $result);
    }

    /**
     * @test
     */
    public function it_applies_with_manually_discounted_exclusion(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1']);

        $promotion = $this->prophesize(CatalogPromotionInterface::class);
        $promotion->getMultiplier()->willReturn(0.9);
        $promotion->isManuallyDiscountedProductsExcluded()->willReturn(true);

        $this->promotionRepository->findOneByCode('PROMO1')->willReturn($promotion->reveal());
        $this->runtimeChecker->isEligible($promotion->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, true);
        $this->assertSame($price, $result);
    }
}
