<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion;

trait PageDefinedElements
{
    protected function getDefinedElements(): array
    {
        return [
            'code' => '#setono_sylius_catalog_promotion__catalog_promotion_code',
            'name' => '#setono_sylius_catalog_promotion__catalog_promotion_name',

            'exclusive' => '#setono_sylius_catalog_promotion__catalog_promotion_exclusive',
            'priority' => '#setono_sylius_catalog_promotion__catalog_promotion_priority',

            'starts_at' => '#setono_sylius_catalog_promotion__catalog_promotion_startsAt',
            'starts_at_date' => '#setono_sylius_catalog_promotion__catalog_promotion_startsAt_date',
            'starts_at_time' => '#setono_sylius_catalog_promotion__catalog_promotion_startsAt_time',

            'ends_at' => '#setono_sylius_catalog_promotion__catalog_promotion_endsAt',
            'ends_at_date' => '#setono_sylius_catalog_promotion__catalog_promotion_endsAt_date',
            'ends_at_time' => '#setono_sylius_catalog_promotion__catalog_promotion_endsAt_time',

            'rules' => '#setono_sylius_catalog_promotion__catalog_promotion_rules',

            'discount' => '#setono_sylius_catalog_promotion__catalog_promotion_discount',
        ];
    }
}
