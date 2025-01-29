<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Applicator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicator;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Checker\Runtime\RuntimeCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotion;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepository;

final class RuntimePromotionsApplicatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CatalogPromotionRepository> */
    private ObjectProphecy $catalogPromotionRepository;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $manager;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    /** @var ObjectProphecy<RuntimeCheckerInterface> */
    private ObjectProphecy $runtimeChecker;

    private RuntimePromotionsApplicatorInterface $applicator;

    protected function setUp(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->catalogPromotionRepository = $this->prophesize(CatalogPromotionRepository::class);
        $this->manager = $this->prophesize(EntityManagerInterface::class);
        $this->manager->getRepository(CatalogPromotion::class)->willReturn($this->catalogPromotionRepository->reveal());
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->managerRegistry->getManagerForClass(CatalogPromotion::class)->willReturn($this->manager->reveal());
        $this->runtimeChecker = $this->prophesize(RuntimeCheckerInterface::class);
        $this->applicator = new RuntimePromotionsApplicator($this->managerRegistry->reveal(), $this->runtimeChecker->reveal(), $eventDispatcher->reveal(), CatalogPromotion::class);
    }

    /**
     * @test
     */
    public function it_does_not_apply_catalog_promotions(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn([]);

        $this->runtimeChecker->isEligible(Argument::any())->shouldNotBeCalled();
        $this->catalogPromotionRepository->findOneByCode(Argument::any())->shouldNotBeCalled();

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

        $catalogPromotion = $this->prophesize(CatalogPromotionInterface::class);
        $catalogPromotion->getMultiplier()->willReturn(0.9);
        $catalogPromotion->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $catalogPromotion->isExclusive()->willReturn(false);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion->reveal());
        $this->runtimeChecker->isEligible($catalogPromotion->reveal())->willReturn(true);

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

        $catalogPromotion1 = $this->prophesize(CatalogPromotionInterface::class);
        $catalogPromotion1->getMultiplier()->willReturn(0.8);
        $catalogPromotion1->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $catalogPromotion1->isExclusive()->willReturn(true);
        $catalogPromotion1->getPriority()->willReturn(1);

        $catalogPromotion2 = $this->prophesize(CatalogPromotionInterface::class);
        $catalogPromotion2->getMultiplier()->willReturn(0.85);
        $catalogPromotion2->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $catalogPromotion2->isExclusive()->willReturn(true);
        $catalogPromotion2->getPriority()->willReturn(2);

        $catalogPromotion3 = $this->prophesize(CatalogPromotionInterface::class);
        $catalogPromotion3->getMultiplier()->willReturn(0.9);
        $catalogPromotion3->isManuallyDiscountedProductsExcluded()->willReturn(false);
        $catalogPromotion3->isExclusive()->willReturn(false);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion1->reveal());
        $this->catalogPromotionRepository->findOneByCode('PROMO2')->willReturn($catalogPromotion2->reveal());
        $this->catalogPromotionRepository->findOneByCode('PROMO3')->willReturn($catalogPromotion3->reveal());
        $this->runtimeChecker->isEligible($catalogPromotion1->reveal())->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion2->reveal())->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion3->reveal())->willReturn(true);

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

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($promotion->reveal());
        $this->runtimeChecker->isEligible($promotion->reveal())->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, true);
        $this->assertSame($price, $result);
    }
}
