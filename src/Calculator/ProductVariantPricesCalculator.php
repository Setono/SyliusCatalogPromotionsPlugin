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
        $cacheKey = self::generateCacheKey($productVariant, $context);
        if (!array_key_exists($cacheKey, $this->computedPriceCache)) {
            $this->computedPriceCache[$cacheKey] = $this->getPrice($productVariant, $context);
        }

        return $this->computedPriceCache[$cacheKey];
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
            $product,
            $prices['price'],
            $prices['originalPrice'],
        ));
    }

    /**
     * @return array{price: int, originalPrice: int, minimumPrice: int}
     */
    private function getPersistedPrices(ProductVariantInterface $productVariant, array $context): array
    {
        $cacheKey = self::generateCacheKey($productVariant, $context);

        if (!isset($this->persistedPricesCache[$cacheKey])) {
            // todo remove these assertions when this issue is fixed: https://github.com/vimeo/psalm/issues/11248
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

            $this->persistedPricesCache[$cacheKey] = [
                'price' => $price,
                'originalPrice' => $channelPricing->getOriginalPrice() ?? $price,
                'minimumPrice' => self::getMinimumPrice($channelPricing),
            ];
        }

        return $this->persistedPricesCache[$cacheKey];
    }

    private static function getMinimumPrice(ChannelPricingInterface $channelPricing): int
    {
        $minimumPrice = 0;

        if (method_exists($channelPricing, 'getMinimumPrice')) {
            /** @var mixed $minimumPrice */
            $minimumPrice = $channelPricing->getMinimumPrice();
        }

        /** @psalm-suppress RedundantCondition */
        Assert::integer($minimumPrice);

        return $minimumPrice;
    }

    /**
     * @psalm-assert ChannelInterface $context['channel']
     */
    private static function generateCacheKey(ProductVariantInterface $productVariant, array $context): string
    {
        Assert::keyExists($context, 'channel');
        Assert::isInstanceOf($context['channel'], ChannelInterface::class);

        return sprintf('%s%s', (string) $context['channel']->getCode(), (string) $productVariant->getCode());
    }
}
