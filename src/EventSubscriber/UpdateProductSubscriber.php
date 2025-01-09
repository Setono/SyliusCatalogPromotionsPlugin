<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventSubscriber;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * NOTICE that we don't handle the Doctrine postUpdate event because products are updated when processing a catalog promotion
 * and hence this would lead to a lot of double work
 */
final class UpdateProductSubscriber implements EventSubscriberInterface
{
    /**
     * An array  of products to update index by id
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
