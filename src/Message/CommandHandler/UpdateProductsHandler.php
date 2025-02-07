<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use EventSauce\BackOff\FibonacciBackOffStrategy;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\PreQualificationCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\UpdateProducts;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Workflow\CatalogPromotionUpdateWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Workflow\WorkflowInterface;

final class UpdateProductsHandler
{
    use ORMTrait;

    /**
     * A cache of catalog promotions where the index is the code
     *
     * @var array<string, CatalogPromotionInterface|null>
     */
    private array $catalogPromotions = [];

    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly CatalogPromotionRepositoryInterface $catalogPromotionRepository,
        private readonly PreQualificationCheckerInterface $preQualificationChecker,
        private readonly WorkflowInterface $catalogPromotionUpdateWorkflow,
        ManagerRegistry $managerRegistry,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(UpdateProducts $message): void
    {
        $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);
        if ($catalogPromotionUpdate->getState() !== CatalogPromotionUpdateInterface::STATE_PROCESSING) {
            return;
        }

        $productsUpdated = 0;
        $error = null;

        try {
            $initialProcessableCatalogPromotions = $this->catalogPromotionRepository->findForProcessing($message->catalogPromotions);

            foreach ($this->productDataProvider->getProducts($message->productIds) as $product) {
                $processableCatalogPromotions = $initialProcessableCatalogPromotions;

                /**
                 * This part of the code ensures that, while updating a specified set of catalog promotions,
                 * we also update the catalog promotions that are already applied to the product.
                 * While this behavior isn't strictly required as per the given task, it is implemented to achieve a
                 * higher level of data consistency.
                 */
                if ([] !== $message->catalogPromotions) {
                    $filteredPreQualifiedCatalogPromotions = array_filter(
                        $product->getPreQualifiedCatalogPromotions(),
                        static fn (string $code): bool => !in_array($code, $message->catalogPromotions, true),
                    );

                    foreach ($filteredPreQualifiedCatalogPromotions as $preQualifiedCatalogPromotion) {
                        $catalogPromotion = $this->getCatalogPromotion($preQualifiedCatalogPromotion);
                        if (null !== $catalogPromotion) {
                            $processableCatalogPromotions[] = $catalogPromotion;
                        }
                    }
                }

                $preQualifiedCatalogPromotions = [];
                foreach ($processableCatalogPromotions as $catalogPromotion) {
                    if ($this->preQualificationChecker->isPreQualified($product, $catalogPromotion)) {
                        $preQualifiedCatalogPromotions[] = (string) $catalogPromotion->getCode();
                    }
                }

                $product->setPreQualifiedCatalogPromotions($preQualifiedCatalogPromotions);
                ++$productsUpdated;
            }
        } catch (\Throwable $e) {
            $error = $e;
        } finally {
            $manager = $this->getManager($this->catalogPromotionUpdateClass);

            // If the entity manager isn't open it means we encountered some kind of ORM exception before
            if (!$manager->isOpen()) {
                if (null !== $error) {
                    throw $error;
                }

                throw EntityManagerClosed::create();
            }

            $tries = 0;

            start:
            $tries++;
            $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);
            $catalogPromotionUpdate->incrementProductsUpdated($productsUpdated);
            $catalogPromotionUpdate->addProcessedMessageId($message->messageId);

            if (null !== $error) {
                $this->catalogPromotionUpdateWorkflow->apply($catalogPromotionUpdate, CatalogPromotionUpdateWorkflow::TRANSITION_FAIL);
                $catalogPromotionUpdate->setError($error->getMessage());
            }

            try {
                $manager->flush();
            } catch (OptimisticLockException $e) {
                $backOff = new FibonacciBackOffStrategy(1_000_000, 10, 10_000_000);
                $backOff->backOff($tries, $e);
                goto start;
            }

            if (null !== $error) {
                throw $error;
            }
        }
    }

    private function getCatalogPromotion(string $code): ?CatalogPromotionInterface
    {
        if (!array_key_exists($code, $this->catalogPromotions)) {
            $this->catalogPromotions[$code] = $this->catalogPromotionRepository->findOneForProcessing($code);
        }

        return $this->catalogPromotions[$code];
    }

    private function getCatalogPromotionUpdate(int $id): CatalogPromotionUpdateInterface
    {
        $catalogPromotionUpdate = $this->getManager($this->catalogPromotionUpdateClass)->find($this->catalogPromotionUpdateClass, $id);

        if (null === $catalogPromotionUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %s not found', $id));
        }

        // Because the unit of work may have been cleared, we refresh the entity
        $this->getManager($this->catalogPromotionUpdateClass)->refresh($catalogPromotionUpdate);

        return $catalogPromotionUpdate;
    }
}
