<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\UpdateProducts;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Workflow\CatalogPromotionUpdateWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class ProcessCatalogPromotionUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly MessageBusInterface $commandBus,
        private readonly WorkflowInterface $catalogPromotionUpdateWorkflow,
        ManagerRegistry $managerRegistry,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessCatalogPromotionUpdate $message): void
    {
        $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);

        if ($catalogPromotionUpdate->getState() !== CatalogPromotionUpdateInterface::STATE_PENDING) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %s is not in the "%s" state', $message->catalogPromotionUpdate, CatalogPromotionUpdateInterface::STATE_PENDING));
        }

        $this->catalogPromotionUpdateWorkflow->apply($catalogPromotionUpdate, CatalogPromotionUpdateWorkflow::TRANSITION_PROCESS);

        $manager = $this->getManager($this->catalogPromotionUpdateClass);
        $manager->flush();

        try {
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             *
             * @var MessageBuffer<int> $buffer
             */
            $buffer = new MessageBuffer(
                100,
                fn (array $ids, string $callbackId) => $this->commandBus->dispatch(new UpdateProducts(
                    $catalogPromotionUpdate,
                    $ids,
                    $catalogPromotionUpdate->getCatalogPromotions(),
                )),
            );

            $i = 0;
            foreach ($this->productDataProvider->getIds() as $id) {
                $buffer->push($id);

                ++$i;
            }

            $buffer->flush();

            // We need to re-fetch the catalog promotion update because it might
            // have become detached from the UnitOfWork inside the data provider above
            $catalogPromotionUpdate = $this->getCatalogPromotionUpdate($message->catalogPromotionUpdate);
            $catalogPromotionUpdate->setProductsEligibleForUpdate($i);
            $catalogPromotionUpdate->setMessageIds($buffer->getCallbackIds());
        } catch (\Throwable $e) {
            $this->catalogPromotionUpdateWorkflow->apply($catalogPromotionUpdate, CatalogPromotionUpdateWorkflow::TRANSITION_FAIL);
            $catalogPromotionUpdate->setError($e->getMessage());

            throw $e;
        } finally {
            $manager->flush();
        }
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
