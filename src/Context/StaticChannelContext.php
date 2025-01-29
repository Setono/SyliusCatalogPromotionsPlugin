<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Context;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;

final class StaticChannelContext implements ChannelContextInterface
{
    private ?ChannelInterface $channel = null;

    public function __construct(private readonly ChannelRepositoryInterface $channelRepository)
    {
    }

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

    /**
     * @throws ChannelNotFoundException if the channel with the given code doesn't exist
     */
    public function setChannelCode(string $channelCode): void
    {
        $channel = $this->channelRepository->findOneByCode($channelCode);
        if (null === $channel) {
            throw new ChannelNotFoundException(sprintf('Channel with code "%s" does not exist', $channelCode));
        }

        $this->channel = $channel;
    }
}
