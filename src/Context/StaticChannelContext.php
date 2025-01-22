<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Context;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;

final class StaticChannelContext implements ChannelContextInterface
{
    private ?ChannelInterface $channel = null;

    public function getChannel(): ChannelInterface
    {
        if (null === $this->channel) {
            throw new ChannelNotFoundException('Static channel is not set');
        }

        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }
}
