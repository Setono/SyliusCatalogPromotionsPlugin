<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Factory\CatalogPromotionUpdateFactoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class StartCatalogPromotionUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly CatalogPromotionRepositoryInterface $catalogPromotionRepository,
        private readonly CatalogPromotionUpdateFactoryInterface $catalogPromotionUpdateFactory,
        private readonly MessageBusInterface $commandBus,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(StartCatalogPromotionUpdate $message): void
    {
        $catalogPromotionUpdate = $this->catalogPromotionUpdateFactory->createFromMessage($message);

        $manager = $this->getManager($catalogPromotionUpdate);
        $manager->persist($catalogPromotionUpdate);
        $manager->flush();

        $this->commandBus->dispatch(new ProcessCatalogPromotionUpdate($catalogPromotionUpdate));
    }
}
