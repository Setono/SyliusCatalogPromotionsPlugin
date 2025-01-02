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
use Symfony\Contracts\Service\ResetInterface;
use Webmozart\Assert\Assert;

final class UpdateProductSubscriber implements EventSubscriberInterface, ResetInterface
{
    /**
     * A list of products to update
     *
     * @var list<ProductInterface>
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
        $product = $event->getSubject();
        Assert::isInstanceOf($product, ProductInterface::class);

        $this->products[] = $product;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ProductInterface) {
            return;
        }

        $this->products[] = $obj;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof ProductInterface) {
            return;
        }

        $this->products[] = $obj;
    }

    public function dispatch(): void
    {
        if ([] === $this->products) {
            return;
        }

        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(products: $this->products));
    }

    public function reset(): void
    {
        $this->products = [];
    }
}
