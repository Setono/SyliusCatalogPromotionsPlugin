<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Message\CommandHandler;

use Closure;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 *
 * @template T
 */
final class MessageBuffer
{
    private int $count = 0;

    /** @var list<T> */
    private array $buffer = [];

    /** @var list<string> */
    private array $callbackIds = [];

    public function __construct(
        private readonly int $bufferSize,
        /** @var Closure(list<T>, string):void $callback */
        private readonly Closure $callback,
    ) {
    }

    /**
     * @param T $item
     */
    public function push(mixed $item): void
    {
        $this->buffer[] = $item;
        ++$this->count;

        if ($this->count >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->count > 0) {
            $callbackId = (string) Uuid::v7();
            ($this->callback)($this->buffer, $callbackId);
            $this->buffer = [];
            $this->count = 0;
            $this->callbackIds[] = $callbackId;
        }
    }

    /**
     * A list of ids given to the callback when flushing
     *
     * @return list<string>
     */
    public function getCallbackIds(): array
    {
        return $this->callbackIds;
    }
}
