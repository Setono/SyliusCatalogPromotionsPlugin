<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface as BaseProductInterface;

interface ProductInterface extends BaseProductInterface
{
    /**
     * A list of catalog promotions codes that the product is pre-qualified for.
     * Pre-qualified means that the product matches the respective catalog promotions rules
     *
     * @return list<string>
     */
    public function getPreQualifiedCatalogPromotions(): array;

    /**
     * @param array<array-key, string>|null $preQualifiedCatalogPromotions
     */
    public function setPreQualifiedCatalogPromotions(?array $preQualifiedCatalogPromotions): void;

    public function hasPreQualifiedCatalogPromotions(): bool;
}
