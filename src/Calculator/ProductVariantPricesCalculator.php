<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Calculator;

use Setono\SyliusCatalogPromotionPlugin\Applicator\RuntimePromotionsApplicatorInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ProductVariantPricesCalculator implements ProductVariantPricesCalculatorInterface
{
    /** @var array<string, array{price: int, originalPrice: int, minimumPrice: int}> */
    private array $persistedPricesCache = [];

    /** @var array<string, int> */
    private array $computedPriceCache = [];

    public function __construct(private readonly RuntimePromotionsApplicatorInterface $runtimePromotionsApplicator)
    {
    }

    public function calculate(ProductVariantInterface $productVariant, array $context): int
    {
        $hash = spl_object_hash($productVariant);
        if (!isset($this->computedPriceCache[$hash])) {
            $this->computedPriceCache[$hash] = $this->getPrice($productVariant, $context);
        }

        return $this->computedPriceCache[$hash];
    }

    public function calculateOriginal(ProductVariantInterface $productVariant, array $context): int
    {
        return $this->getPersistedPrices($productVariant, $context)['originalPrice'];
    }

    private function getPrice(ProductVariantInterface $productVariant, array $context): int
    {
        $prices = $this->getPersistedPrices($productVariant, $context);

        $product = $productVariant->getProduct();
        if (!$product instanceof ProductInterface || !$product->hasPreQualifiedCatalogPromotions()) {
            return $prices['price'];
        }

        return max($prices['minimumPrice'], $this->runtimePromotionsApplicator->apply(
            $product->getPreQualifiedCatalogPromotions(),
            $prices['price'],
            $prices['price'] < $prices['originalPrice'],
        ));
    }

    /**
     * @psalm-assert ChannelInterface $context['channel']
     *
     * @return array{price: int, originalPrice: int, minimumPrice: int}
     */
    private function getPersistedPrices(ProductVariantInterface $productVariant, array $context): array
    {
        $hash = spl_object_hash($productVariant);

        if (!isset($this->persistedPricesCache[$hash])) {
            Assert::keyExists($context, 'channel');
            Assert::isInstanceOf($context['channel'], ChannelInterface::class);

            $channelPricing = $productVariant->getChannelPricingForChannel($context['channel']);

            if (null === $channelPricing) {
                throw MissingChannelConfigurationException::createForProductVariantChannelPricing($productVariant, $context['channel']);
            }

            $price = $channelPricing->getPrice();
            if (null === $price) {
                throw MissingChannelConfigurationException::createForProductVariantChannelPricing($productVariant, $context['channel']);
            }

            $this->persistedPricesCache[$hash] = [
                'price' => $price,
                'originalPrice' => $channelPricing->getOriginalPrice() ?? $price,
                'minimumPrice' => self::getMinimumPrice($channelPricing),
            ];
        }

        return $this->persistedPricesCache[$hash];
    }

    private static function getMinimumPrice(ChannelPricingInterface $channelPricing): int
    {
        if (method_exists($channelPricing, 'getMinimumPrice')) {
            return $channelPricing->getMinimumPrice();
        }

        return 0;
    }
}
