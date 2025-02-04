<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\OnSale;

use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class OnSaleChecker implements OnSaleCheckerInterface
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly RuntimePromotionsApplicatorInterface $runtimePromotionsApplicator,
    ) {
    }

    public function onSale(ProductInterface|ProductVariantInterface $product, ChannelInterface $channel = null): bool
    {
        $channel = $channel ?? $this->channelContext->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        return match (true) {
            $product instanceof ProductInterface => $this->checkProduct($product, $channel),
            $product instanceof ProductVariantInterface => $this->checkVariant($product, $channel),
        };
    }

    private function checkProduct(ProductInterface $product, ChannelInterface $channel): bool
    {
        /** @var ProductVariantInterface $variant */
        foreach ($product->getEnabledVariants() as $variant) {
            if ($this->checkVariant($variant, $channel)) {
                return true;
            }
        }

        return false;
    }

    private function checkVariant(ProductVariantInterface $variant, ChannelInterface $channel): bool
    {
        $channelPricing = $variant->getChannelPricingForChannel($channel);
        if (null === $channelPricing) {
            return false;
        }

        if ($channelPricing->isPriceReduced()) {
            return true;
        }

        $product = $variant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);

        $price = (int) $channelPricing->getPrice();

        $appliedPrice = $this->runtimePromotionsApplicator->apply($product, $price, $channelPricing->getOriginalPrice());

        return $appliedPrice < $price;
    }
}
