<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class ChannelContextRuntimeChecker implements RuntimeCheckerInterface
{
    public function __construct(private readonly ChannelContextInterface $channelContext)
    {
    }

    public function isEligible(PromotionInterface $catalogPromotion): bool
    {
        return $catalogPromotion->getChannels()->contains($this->channelContext->getChannel());
    }
}
