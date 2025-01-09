<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Checker\Runtime;

use Psr\Clock\ClockInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;

final class DateRuntimeChecker implements RuntimeCheckerInterface
{
    public function __construct(private readonly ?ClockInterface $clock)
    {
    }

    public function isEligible(CatalogPromotionInterface $catalogPromotion): bool
    {
        $now = $this->clock?->now() ?? new \DateTimeImmutable();

        $startsAt = $catalogPromotion->getStartsAt();
        if (null !== $startsAt && $startsAt > $now) {
            return false;
        }

        $endsAt = $catalogPromotion->getEndsAt();
        if (null !== $endsAt && $endsAt < $now) {
            return false;
        }

        return true;
    }
}
