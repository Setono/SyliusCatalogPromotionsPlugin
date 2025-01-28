<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use EventSauce\BackOff\FibonacciBackOffStrategy;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\PreQualificationCheckerInterface;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\UpdateProducts;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Workflow\CatalogPromotionUpdateWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Workflow\WorkflowInterface;

final class UpdateProductsHandler
{
    use ORMTrait;

    /**
     * A cache of catalog promotions telling if the code (the key of the array) exists (the value)
     *
     * @var array<string, bool>
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
            $catalogPromotions = $this->catalogPromotionRepository->findForProcessing($message->catalogPromotions);

            foreach ($this->productDataProvider->getProducts($message->productIds) as $product) {
                // Remove the catalog promotions
                // - we are processing and
                // - the ones that doesn't exist anymore
                // from the pre-qualified catalog promotions before we start the actual processing
                $preQualifiedCatalogPromotions = array_filter(
                    $product->getPreQualifiedCatalogPromotions(),
                    fn (string $code) => !in_array($code, $message->catalogPromotions, true) && $this->catalogPromotionExists($code),
                );

                foreach ($catalogPromotions as $catalogPromotion) {
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
            $tries = 0;

            $manager = $this->getManager($this->catalogPromotionUpdateClass);
            if (null !== $error && !$manager->isOpen()) {
                throw $error;
            }

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

    private function getCatalogPromotionUpdate(int $id): CatalogPromotionUpdateInterface
    {
        $catalogPromotionUpdate = $this->getManager($this->catalogPromotionUpdateClass)->find($this->catalogPromotionUpdateClass, $id);

        if (null === $catalogPromotionUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %s not found', $id));
        }

        $this->getManager($this->catalogPromotionUpdateClass)->refresh($catalogPromotionUpdate);

        return $catalogPromotionUpdate;
    }

    private function catalogPromotionExists(string $code): bool
    {
        if (!array_key_exists($code, $this->catalogPromotions)) {
            $this->catalogPromotions[$code] = null !== $this->catalogPromotionRepository->findOneBy(['code' => $code]);
        }

        return $this->catalogPromotions[$code];
    }
}
