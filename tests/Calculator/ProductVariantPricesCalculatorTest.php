<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Calculator;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Calculator\ProductVariantPricesCalculator;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductVariantPricesCalculatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_delegates_to_decorated(): void
    {
        $channel = new Channel();

        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getPrice()->willReturn(800);
        $channelPricing->getOriginalPrice()->willReturn(1000);
        $channelPricing->getMinimumPrice()->willReturn(0);

        $product = $this->prophesize(ProductInterface::class);
        $product->hasPreQualifiedCatalogPromotions()->willReturn(false);

        $productVariant = $this->prophesize(ProductVariantInterface::class);
        $productVariant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());
        $productVariant->getProduct()->willReturn($product->reveal());

        $runtimePromotionsApplicator = $this->prophesize(RuntimePromotionsApplicatorInterface::class);
        $calculator = new ProductVariantPricesCalculator($runtimePromotionsApplicator->reveal());

        $this->assertSame(800, $calculator->calculate($productVariant->reveal(), [
            'channel' => $channel,
        ]));

        $this->assertSame(1000, $calculator->calculateOriginal($productVariant->reveal(), [
            'channel' => $channel,
        ]));
    }

    /**
     * @test
     */
    public function it_calculates_catalog_promotion(): void
    {
        $channel = new Channel();

        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getPrice()->willReturn(800);
        $channelPricing->getOriginalPrice()->willReturn(1000);
        $channelPricing->getMinimumPrice()->willReturn(0);

        $product = $this->prophesize(ProductInterface::class);
        $product->hasPreQualifiedCatalogPromotions()->willReturn(true);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['promo1', 'promo2']);

        $productVariant = $this->prophesize(ProductVariantInterface::class);
        $productVariant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());
        $productVariant->getProduct()->willReturn($product->reveal());

        $runtimePromotionsApplicator = $this->prophesize(RuntimePromotionsApplicatorInterface::class);
        $runtimePromotionsApplicator->apply($product->reveal(), 800, 1000)->willReturn(600);

        $calculator = new ProductVariantPricesCalculator($runtimePromotionsApplicator->reveal());

        $this->assertSame(600, $calculator->calculate($productVariant->reveal(), [
            'channel' => $channel,
        ]));

        $this->assertSame(1000, $calculator->calculateOriginal($productVariant->reveal(), [
            'channel' => $channel,
        ]));
    }

    /**
     * @test
     */
    public function it_respects_minimum_price(): void
    {
        $channel = new Channel();

        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getPrice()->willReturn(800);
        $channelPricing->getOriginalPrice()->willReturn(800);
        $channelPricing->getMinimumPrice()->willReturn(700);

        $product = $this->prophesize(ProductInterface::class);
        $product->hasPreQualifiedCatalogPromotions()->willReturn(true);
        $product->getPreQualifiedCatalogPromotions()->willReturn(['promo1']);

        $productVariant = $this->prophesize(ProductVariantInterface::class);
        $productVariant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());
        $productVariant->getProduct()->willReturn($product->reveal());

        $runtimePromotionsApplicator = $this->prophesize(RuntimePromotionsApplicatorInterface::class);
        $runtimePromotionsApplicator->apply($product->reveal(), 800, 800)->willReturn(600);

        $calculator = new ProductVariantPricesCalculator($runtimePromotionsApplicator->reveal());

        $this->assertSame(700, $calculator->calculate($productVariant->reveal(), [
            'channel' => $channel,
        ]));
    }
}
