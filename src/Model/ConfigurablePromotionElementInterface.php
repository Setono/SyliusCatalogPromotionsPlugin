<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface ConfigurablePromotionElementInterface extends ResourceInterface
{
    public function getType(): ?string;

    public function getConfiguration(): array;

    public function getPromotion(): ?PromotionInterface;
}
