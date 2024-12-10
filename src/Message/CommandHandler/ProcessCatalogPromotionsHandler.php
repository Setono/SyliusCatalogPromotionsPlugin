<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\PreQualificationCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotions;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;

final class ProcessCatalogPromotionsHandler
{
    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly PreQualificationCheckerInterface $preQualificationChecker,
    ) {
    }

    public function __invoke(ProcessCatalogPromotions $message): void
    {
        $catalogPromotions = $this->promotionRepository->findForProcessing();

        foreach ($this->productDataProvider->getProducts() as $product) {
            $preQualifiedCatalogPromotions = [];

            foreach ($catalogPromotions as $catalogPromotion) {
                if ($this->preQualificationChecker->isPreQualified($product, $catalogPromotion)) {
                    $preQualifiedCatalogPromotions[] = (string) $catalogPromotion->getCode();
                }
            }

            $product->setPreQualifiedCatalogPromotions($preQualifiedCatalogPromotions);
        }
    }
}
