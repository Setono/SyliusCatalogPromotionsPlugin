<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Setono\SyliusCatalogPromotionPlugin\Model\ChannelPricingInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This subscriber has the responsibility to mark a channel pricing as manually discounted if the user did so manually
 */
final class UpdateManuallyDiscountedPropertySubscriber implements EventSubscriber
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $channelPricing = $event->getEntity();
        if (!$channelPricing instanceof ChannelPricingInterface) {
            return;
        }

        $channelPricing->setManuallyDiscounted($channelPricing->hasDiscount());
        $this->setOrigin($channelPricing);
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $channelPricing = $event->getEntity();
        if (!$channelPricing instanceof ChannelPricingInterface) {
            return;
        }

        if (!$event->hasChangedField('price') && !$event->hasChangedField('originalPrice')) {
            return;
        }

        $channelPricing->setManuallyDiscounted($channelPricing->hasDiscount());
        $this->setOrigin($channelPricing);
    }

    private function setOrigin(ChannelPricingInterface $channelPricing): void
    {
        if (!$channelPricing->isManuallyDiscounted()) {
            return;
        }

        $origin = '';

        $request = $this->requestStack->getMasterRequest();
        if (null !== $request) {
            $origin .= $request->getUri() . "\n\n";
        }

        $origin .= print_r(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS), true);

        $channelPricing->setManuallyDiscountedOrigin($origin);
    }
}
