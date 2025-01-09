<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface CatalogPromotionRuleInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getType(): ?string;

    public function setType(string $type): void;

    public function getConfiguration(): array;

    public function setConfiguration(array $configuration): void;

    public function getCatalogPromotion(): ?CatalogPromotionInterface;

    public function setCatalogPromotion(?CatalogPromotionInterface $catalogPromotion): void;
}
