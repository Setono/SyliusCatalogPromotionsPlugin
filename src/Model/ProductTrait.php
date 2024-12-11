<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait ProductTrait
{
    /**
     * @var list<string>|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $preQualifiedCatalogPromotions = null;

    /**
     * @return list<string>
     */
    public function getPreQualifiedCatalogPromotions(): array
    {
        return $this->preQualifiedCatalogPromotions ?? [];
    }

    /**
     * @param list<array-key, string>|null $preQualifiedCatalogPromotions
     */
    public function setPreQualifiedCatalogPromotions(?array $preQualifiedCatalogPromotions): void
    {
        $preQualifiedCatalogPromotions = self::sanitizeCodes($preQualifiedCatalogPromotions ?? []);

        if ([] === $preQualifiedCatalogPromotions) {
            $preQualifiedCatalogPromotions = null;
        }

        $this->preQualifiedCatalogPromotions = $preQualifiedCatalogPromotions;
    }

    public function hasPreQualifiedCatalogPromotions(): bool
    {
        return null !== $this->preQualifiedCatalogPromotions && [] !== $this->preQualifiedCatalogPromotions;
    }

    /**
     * @param array<array-key, string> $codes
     *
     * @return list<string>
     */
    private static function sanitizeCodes(array $codes): array
    {
        sort($codes, \SORT_STRING);

        return array_values(array_unique($codes));
    }
}
