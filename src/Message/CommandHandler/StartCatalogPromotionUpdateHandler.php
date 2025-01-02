<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Factory\CatalogPromotionUpdateFactoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\ProcessCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

final class StartCatalogPromotionUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly CatalogPromotionUpdateFactoryInterface $catalogPromotionUpdateFactory,
        private readonly MessageBusInterface $commandBus,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(StartCatalogPromotionUpdate $message): CatalogPromotionUpdateInterface
    {
        $catalogPromotions = $message->catalogPromotions;
        if ([] === $message->catalogPromotions) {
            $catalogPromotions = array_map(
                static fn (PromotionInterface $promotion): string => (string) $promotion->getCode(),
                $this->promotionRepository->findForProcessing($message->catalogPromotions),
            );
        }

        if ([] === $catalogPromotions) {
            throw new UnrecoverableMessageHandlingException('No catalog promotions found to process');
        }

        $catalogPromotionUpdate = $this->catalogPromotionUpdateFactory->createWithCatalogPromotions($catalogPromotions);
        if ([] !== $message->products) {
            $catalogPromotionUpdate->setProducts($message->products);
        }

        $manager = $this->getManager($catalogPromotionUpdate);
        $manager->persist($catalogPromotionUpdate);
        $manager->flush();

        $this->commandBus->dispatch(new ProcessCatalogPromotionUpdate($catalogPromotionUpdate));

        return $catalogPromotionUpdate;
    }
}
