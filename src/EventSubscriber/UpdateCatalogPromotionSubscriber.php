<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventSubscriber;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
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
     * @var list<PromotionInterface>
     */
    private array $catalogPromotions = [];

    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'setono_sylius_catalog_promotion.promotion.post_create' => 'update',
            'setono_sylius_catalog_promotion.promotion.post_update' => 'update',
            KernelEvents::TERMINATE => 'dispatch',
            ConsoleEvents::TERMINATE => 'dispatch',
        ];
    }

    public function update(ResourceControllerEvent $event): void
    {
        $obj = $event->getSubject();
        Assert::isInstanceOf($obj, PromotionInterface::class);

        $this->catalogPromotions[] = $obj;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof PromotionInterface) {
            return;
        }

        $this->catalogPromotions[] = $obj;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof PromotionInterface) {
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
                implode('", "', array_map(static fn (PromotionInterface $promotion): string => (string) ($promotion->getName() ?? $promotion->getCode()), $this->catalogPromotions)),
            ),
        ));

        $this->catalogPromotions = [];
    }
}
