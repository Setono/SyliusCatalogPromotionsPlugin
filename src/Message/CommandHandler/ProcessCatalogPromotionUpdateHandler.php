<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\DataProvider\ProductDataProviderInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\UpdateProducts;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProcessCatalogPromotionUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly ProductDataProviderInterface $productDataProvider,
        private readonly MessageBusInterface $commandBus,
        ManagerRegistry $managerRegistry,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    // todo check for state of the catalog promotion update
    // todo catch errors and transition the catalog promotion update to an error state
    public function __invoke(ProcessCatalogPromotionUpdate $message): void
    {
        $manager = $this->getManager($this->catalogPromotionUpdateClass);

        /** @var CatalogPromotionUpdateInterface|null $catalogPromotionUpdate */
        $catalogPromotionUpdate = $manager->find($this->catalogPromotionUpdateClass, $message->catalogPromotionUpdate);
        if (null === $catalogPromotionUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Catalog promotion update with id %s not found', $message->catalogPromotionUpdate));
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         *
         * @var MessageBuffer<int> $buffer
         */
        $buffer = new MessageBuffer(100, fn (array $ids) => $this->commandBus->dispatch(new UpdateProducts($catalogPromotionUpdate, $ids, $catalogPromotionUpdate->getCatalogPromotions())));

        $i = 0;
        foreach ($this->productDataProvider->getIds() as $id) {
            $buffer->push($id);

            ++$i;
        }

        $buffer->flush();

        $catalogPromotionUpdate->setProductsEligibleForUpdate($i);

        $manager->flush();
    }
}
