<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Behaviour;

use Sylius\Behat\Behaviour\DocumentAccessor;

trait SpecifiesItsDiscount
{
    use DocumentAccessor;

    public function specifyDiscount(?float $discount): void
    {
        $this->getDocument()->fillField('Discount', (string) $discount ?? '');
    }
}
