<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ProductPricingContext implements Context
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Then /^the price of (product "[^"]+") should be ("[^"]+") and the original price should be ("[^"]+")$/
     */
    public function thePriceShouldBe(ProductInterface $product, int $price, int $originalPrice): void
    {
        $channelPricing = $this->getChannelPricing($product);

        Assert::same($channelPricing->getPrice(), $price);
        Assert::same($channelPricing->getOriginalPrice(), $originalPrice);
    }

    private function getChannelPricing(ProductInterface $product): ChannelPricingInterface
    {
        $variants = $product->getVariants();
        Assert::count($variants, 1);

        /** @var ProductVariantInterface $variant */
        $variant = $variants->first();

        $channelPricings = $variant->getChannelPricings();
        Assert::count($channelPricings, 1);

        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $channelPricings->first();

        $this->entityManager->refresh($channelPricing);

        return $channelPricing;
    }
}
