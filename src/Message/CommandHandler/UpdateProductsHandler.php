<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\PreQualificationCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\UpdateProducts;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class UpdateProductsHandler
{
    use ORMTrait;

    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly PreQualificationCheckerInterface $preQualificationChecker,
        ManagerRegistry $managerRegistry,
        /** @var class-string<ProductInterface> $productClass */
        private readonly string $productClass,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    // todo check state of the catalog promotion update
    // todo catch errors and transition the catalog promotion update to an error state
    public function __invoke(UpdateProducts $message): void
    {
        $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);

        $catalogPromotions = $this->promotionRepository->findForProcessing($message->catalogPromotions);

        $i = 0;
        foreach ($this->productDataProvider->getProducts($message->productIds) as $product) {
            // Reset the pre-qualified catalog promotions for the catalog promotions we are processing
            $preQualifiedCatalogPromotions = array_filter(
                $product->getPreQualifiedCatalogPromotions(),
                static fn (string $code) => !in_array($code, $message->catalogPromotions, true),
            );

            foreach ($catalogPromotions as $catalogPromotion) {
                if ($this->preQualificationChecker->isPreQualified($product, $catalogPromotion)) {
                    $preQualifiedCatalogPromotions[] = (string) $catalogPromotion->getCode();
                }
            }

            $product->setPreQualifiedCatalogPromotions($preQualifiedCatalogPromotions);
            ++$i;
        }

        $catalogPromotionUpdate->incrementProductsUpdated($i);

        $this->getManager($this->productClass)->flush();
    }

    private function getCatalogPromotionUpdate(int $id): CatalogPromotionUpdateInterface
    {
        $catalogPromotionUpdate = $this->getManager($this->catalogPromotionUpdateClass)->find($this->catalogPromotionUpdateClass, $id);
        if (null === $catalogPromotionUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %s not found', $id));
        }

        return $catalogPromotionUpdate;
    }
}
