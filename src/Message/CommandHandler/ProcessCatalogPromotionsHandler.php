<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\PreQualificationCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotions;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;

// todo refactor this by dividing the processing into chunks of x products much like we do in the Meilisearch plugin
final class ProcessCatalogPromotionsHandler
{
    use ORMTrait;

    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly PreQualificationCheckerInterface $preQualificationChecker,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessCatalogPromotions $message): void
    {
        $catalogPromotions = $this->promotionRepository->findForProcessing($message->catalogPromotions);

        $manager = null;

        $i = 0;
        foreach ($this->productDataProvider->getProducts() as $product) {
            ++$i;

            // If we check all catalog promotions, we need to reset the pre-qualified catalog promotions on the product.
            // Otherwise, we only need to reset the pre-qualified catalog promotions for the catalog promotions we are processing
            $preQualifiedCatalogPromotions = [];
            if ([] !== $message->catalogPromotions) {
                $preQualifiedCatalogPromotions = array_filter(
                    $product->getPreQualifiedCatalogPromotions(),
                    static fn (string $code) => !in_array($code, $message->catalogPromotions, true),
                );
            }

            foreach ($catalogPromotions as $catalogPromotion) {
                if ($this->preQualificationChecker->isPreQualified($product, $catalogPromotion)) {
                    $preQualifiedCatalogPromotions[] = (string) $catalogPromotion->getCode();
                }
            }

            $product->setPreQualifiedCatalogPromotions($preQualifiedCatalogPromotions);

            if ($i % 100 === 0) {
                $manager = $this->getManager($product);
                $manager->flush();
                $manager->clear();
            }
        }

        $manager?->flush();
    }
}
