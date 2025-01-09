<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class CatalogPromotionDateRange extends Constraint
{
    public string $message = 'setono_sylius_catalog_promotion.catalog_promotion.end_date_cannot_be_set_prior_start_date';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
