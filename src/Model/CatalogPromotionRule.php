<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

class CatalogPromotionRule implements CatalogPromotionRuleInterface
{
    protected ?int $id = null;

    protected ?string $type = null;

    protected array $configuration = [];

    protected ?CatalogPromotionInterface $catalogPromotion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getCatalogPromotion(): ?CatalogPromotionInterface
    {
        return $this->catalogPromotion;
    }

    public function setCatalogPromotion(?CatalogPromotionInterface $catalogPromotion): void
    {
        $this->catalogPromotion = $catalogPromotion;
    }
}
