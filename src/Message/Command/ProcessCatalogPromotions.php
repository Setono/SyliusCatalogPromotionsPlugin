<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\Command;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;

final class ProcessCatalogPromotions implements CommandInterface
{
    /**
     * A list of catalog promotion codes to process. If empty, all catalog promotions will be processed
     *
     * @var list<string>
     */
    public readonly array $catalogPromotions;

    /**
     * @param list<string|PromotionInterface> $catalogPromotions
     */
    public function __construct(
        array $catalogPromotions = [],
    ) {
        $this->catalogPromotions = array_map(
            static fn (string|PromotionInterface $catalogPromotion) => $catalogPromotion instanceof PromotionInterface ? (string) $catalogPromotion->getCode() : $catalogPromotion,
            $catalogPromotions,
        );
    }
}
