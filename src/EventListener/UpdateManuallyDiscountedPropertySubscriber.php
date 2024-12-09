<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Setono\SyliusCatalogPromotionPlugin\Model\ChannelPricingInterface;

/**
 * This subscriber has the responsibility to mark a channel pricing as manually discounted if the user did so manually
 */
final class UpdateManuallyDiscountedPropertySubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        self::update($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        self::update($event);
    }

    private static function update(LifecycleEventArgs $event): void
    {
        $channelPricing = $event->getEntity();
        if (!$channelPricing instanceof ChannelPricingInterface) {
            return;
        }

        // here we check if the channel pricing is part of an already applied catalog promotion or
        // a job is running to add a catalog promotion to this channel pricing
        if ([] !== $channelPricing->getAppliedPromotions() || null !== $channelPricing->getBulkIdentifier()) {
            return;
        }

        if ($event instanceof PreUpdateEventArgs &&
            !$event->hasChangedField('price') &&
            !$event->hasChangedField('originalPrice')
        ) {
            return;
        }

        $channelPricing->setManuallyDiscounted(
            $channelPricing->hasDiscount(),
        );
    }
}
