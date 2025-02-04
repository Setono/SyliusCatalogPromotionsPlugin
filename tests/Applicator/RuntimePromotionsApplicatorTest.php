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
        $result = $this->applicator->apply($product->reveal(), $price);
        $this->assertSame($price, $result);
    }

    /**
     * @test
     */
    public function it_applies_catalog_promotions(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1']);

        $catalogPromotion = self::createCatalogPromotion(0.1, false);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion);
        $this->runtimeChecker->isEligible($catalogPromotion)->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price);
        $this->assertSame(900, $result);
    }

    /**
     * @test
     */
    public function it_applies_with_exclusive_catalog_promotion(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1', 'PROMO2', 'PROMO3']);

        $catalogPromotion1 = self::createCatalogPromotion(0.2, false, true, 1);
        $catalogPromotion2 = self::createCatalogPromotion(0.15, false, true, 2);
        $catalogPromotion3 = self::createCatalogPromotion(0.1, false, true);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion1);
        $this->catalogPromotionRepository->findOneByCode('PROMO2')->willReturn($catalogPromotion2);
        $this->catalogPromotionRepository->findOneByCode('PROMO3')->willReturn($catalogPromotion3);
        $this->runtimeChecker->isEligible($catalogPromotion1)->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion2)->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion3)->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price);
        $this->assertSame(850, $result);
    }

    /**
     * @test
     */
    public function it_applies_when_using_original_price_as_base_price(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1', 'PROMO2', 'PROMO3']);

        $catalogPromotion1 = self::createCatalogPromotion(0.2, false);
        $catalogPromotion2 = self::createCatalogPromotion(0.15, false, false, null, true);
        $catalogPromotion3 = self::createCatalogPromotion(0.1, false);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion1);
        $this->catalogPromotionRepository->findOneByCode('PROMO2')->willReturn($catalogPromotion2);
        $this->catalogPromotionRepository->findOneByCode('PROMO3')->willReturn($catalogPromotion3);
        $this->runtimeChecker->isEligible($catalogPromotion1)->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion2)->willReturn(true);
        $this->runtimeChecker->isEligible($catalogPromotion3)->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, 2000);
        $this->assertSame(1224, $result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_with_manually_discounted_exclusion(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['PROMO1']);

        $catalogPromotion = self::createCatalogPromotion(0.1, true);

        $this->catalogPromotionRepository->findOneByCode('PROMO1')->willReturn($catalogPromotion);
        $this->runtimeChecker->isEligible($catalogPromotion)->willReturn(true);

        $price = 1000;
        $result = $this->applicator->apply($product->reveal(), $price, 1100);
        $this->assertSame($price, $result);
    }

    private static function createCatalogPromotion(
        float $discount,
        bool $manuallyDiscountedProductsExcluded,
        bool $exclusive = false,
        ?int $priority = null,
        bool $usingOriginalPriceAsBasePrice = false,
    ): CatalogPromotionInterface {
        $catalogPromotion = new CatalogPromotion();
        $catalogPromotion->setDiscount($discount);
        $catalogPromotion->setManuallyDiscountedProductsExcluded($manuallyDiscountedProductsExcluded);
        $catalogPromotion->setExclusive($exclusive);

        if (null !== $priority) {
            $catalogPromotion->setPriority($priority);
        }

        $catalogPromotion->setUsingOriginalPriceAsBase($usingOriginalPriceAsBasePrice);

        return $catalogPromotion;
    }
}
