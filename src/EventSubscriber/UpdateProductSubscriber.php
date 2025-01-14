<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventSubscriber;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;

final class UpdateProductSubscriber implements EventSubscriberInterface
{
    /** @var array<int, ProductInterface> */
    private array $preUpdateCandidates = [];

    /**
     * An array  of products to update indexed by id
     *
     * @var array<int, ProductInterface>
     */
    private array $products = [];

    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.post_create' => 'updateProduct',
            'sylius.product.post_update' => 'updateProduct',
            KernelEvents::TERMINATE => 'dispatch',
            ConsoleEvents::TERMINATE => 'dispatch',
        ];
    }

    public function updateProduct(ResourceControllerEvent $event): void
    {
        $this->addProduct($event->getSubject());
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $this->addProduct($eventArgs->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ProductInterface) {
            return;
        }

        $changeSet = $eventArgs->getEntityChangeSet();

        // We don't want to start a catalog promotion update if the only change are these two fields because they are
        // most likely changed during an update of all or some catalog promotions in the first place
        if (count($changeSet) === 2 && isset($changeSet['updatedAt'], $changeSet['preQualifiedCatalogPromotions'])) {
            return;
        }

        $this->preUpdateCandidates[(int) $obj->getId()] = $obj;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ProductInterface) {
            return;
        }

        if (!isset($this->preUpdateCandidates[(int) $obj->getId()])) {
            return;
        }

        $this->addProduct($obj);
    }

    public function dispatch(): void
    {
        if ([] === $this->products) {
            return;
        }

        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(
            products: $this->products,
            triggeredBy: sprintf(
                'The update/creation of the following products: "%s"',
                implode('", "', array_map(static fn (ProductInterface $product): string => (string) ($product->getName() ?? $product->getCode()), $this->products)),
            ),
        ));

        $this->products = [];
    }

    private function addProduct(mixed $product): void
    {
        if (!$product instanceof ProductInterface) {
            return;
        }

        $this->products[(int) $product->getId()] = $product;
    }
}
