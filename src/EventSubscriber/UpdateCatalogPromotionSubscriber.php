<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventSubscriber;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

/**
 * Notice that we don't need to handle the removal of catalog promotions because although the catalog promotion
 * might be pre-qualified, it will not be applied to any products (because it doesn't exist anymore)
 */
final class UpdateCatalogPromotionSubscriber implements EventSubscriberInterface
{
    /**
     * A list of catalog promotions to update
     *
     * @var list<CatalogPromotionInterface>
     */
    private array $catalogPromotions = [];

    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'setono_sylius_catalog_promotion.catalog_promotion.post_create' => 'update',
            'setono_sylius_catalog_promotion.catalog_promotion.post_update' => 'update',
            KernelEvents::TERMINATE => 'dispatch',
            ConsoleEvents::TERMINATE => 'dispatch',
        ];
    }

    public function update(ResourceControllerEvent $event): void
    {
        $obj = $event->getSubject();
        Assert::isInstanceOf($obj, CatalogPromotionInterface::class);

        $this->catalogPromotions[] = $obj;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof CatalogPromotionInterface) {
            return;
        }

        $this->catalogPromotions[] = $obj;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof CatalogPromotionInterface) {
            return;
        }

        $this->catalogPromotions[] = $obj;
    }

    public function dispatch(): void
    {
        if ([] === $this->catalogPromotions) {
            return;
        }

        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(
            catalogPromotions: $this->catalogPromotions,
            triggeredBy: sprintf(
                'The update/creation of the following catalog promotions: "%s"',
                implode('", "', array_map(static fn (CatalogPromotionInterface $catalogPromotion): string => (string) ($catalogPromotion->getName() ?? $catalogPromotion->getCode()), $this->catalogPromotions)),
            ),
        ));

        $this->catalogPromotions = [];
    }
}
