<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * This is the message you should dispatch when you want to update catalog promotions
 */
final class StartCatalogPromotionUpdate
{
    /**
     * A list of catalog promotion codes to process. If empty, all catalog promotions will be processed
     *
     * @var list<string>
     */
    public readonly array $catalogPromotions;

    /**
     * A list of product ids to process. If empty, all products will be processed
     *
     * @var list<int>
     */
    public readonly array $products;

    /**
     * @param list<string|PromotionInterface> $catalogPromotions
     * @param list<int|ProductInterface> $products
     */
    public function __construct(
        array $catalogPromotions = [],
        array $products = [],
        /**
         * If you want to give information about what started the update, you can provide a string here
         */
        public readonly ?string $triggeredBy = null,
    ) {
        $this->catalogPromotions = array_values(array_unique(array_map(
            static fn (string|PromotionInterface $catalogPromotion) => $catalogPromotion instanceof PromotionInterface ? (string) $catalogPromotion->getCode() : $catalogPromotion,
            $catalogPromotions,
        )));

        $this->products = array_values(array_unique(array_map(
            static fn (int|ProductInterface $product) => $product instanceof ProductInterface ? (int) $product->getId() : $product,
            $products,
        )));
    }
}
