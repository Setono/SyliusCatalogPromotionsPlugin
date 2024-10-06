<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use RuntimeException;
use function sprintf;
use Sylius\Component\Resource\Model\TimestampableInterface;

final class AddTimestampableIndicesSubscriber implements EventSubscriber
{
    /** @var array<array-key, class-string> */
    private array $classes;

    /**
     * @param array<array-key, class-string> $classes
     */
    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();

        foreach ($this->classes as $class) {
            if (!is_a($metadata->name, $class, true)) {
                continue;
            }

            self::addIndices($metadata);
        }
    }

    private static function addIndices(ClassMetadata $metadata): void
    {
        $indices = [];

        if (!is_subclass_of($metadata->name, TimestampableInterface::class, true)) {
            throw new RuntimeException(sprintf(
                'The class %s must implement the interface, %s',
                $metadata->name,
                TimestampableInterface::class
            ));
        }

        $fields = ['createdAt', 'updatedAt'];
        foreach ($fields as $field) {
            if (!$metadata->hasField($field)) {
                throw new RuntimeException(sprintf('The class %s does not have a "%s" field', $metadata->name, $field));
            }

            $column = $metadata->getColumnName($field);

            $indices[] = [
                'columns' => [$column],
            ];
        }

        /** @psalm-suppress PropertyTypeCoercion */
        $metadata->table = array_merge_recursive([
            'indexes' => $indices,
        ], $metadata->table);
    }
}
