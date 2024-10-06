<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use RuntimeException;
use Setono\SyliusCatalogPromotionPlugin\Model\ChannelPricingInterface;
use function sprintf;

final class AddChannelPricingIndicesSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();

        if (!is_subclass_of($metadata->name, ChannelPricingInterface::class, true)) {
            return;
        }

        if (!$metadata->hasField('multiplier')) {
            throw new RuntimeException(sprintf('No "multiplier" property on class %s', $metadata->name));
        }

        $columnName = $metadata->getColumnName('multiplier');

        /** @psalm-suppress PropertyTypeCoercion */
        $metadata->table = array_merge_recursive([
            'indexes' => [
                [
                    'columns' => [$columnName],
                ],
            ],
        ], $metadata->table);
    }
}
