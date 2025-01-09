<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class ChannelContextRuntimeChecker implements RuntimeCheckerInterface
{
    public function __construct(private readonly ChannelContextInterface $channelContext)
    {
    }

    public function isEligible(CatalogPromotionInterface $catalogPromotion): bool
    {
        $channel = $this->channelContext->getChannel();

        $collection = $catalogPromotion->getChannels();
        if ($collection instanceof Selectable) {
            return $collection->matching(Criteria::create()->andWhere(Criteria::expr()->eq('code', $channel->getCode())))->count() > 0;
        }

        return $collection->contains($channel);
    }
}
