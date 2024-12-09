<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusCatalogPromotionPlugin\Behat\Page\Admin\Promotion;

use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Behaviour\ChecksCodeImmutability;
use Sylius\Behat\Behaviour\NamesIt;
use Sylius\Behat\Page\Admin\Crud\UpdatePage as BaseUpdatePage;
use Tests\Setono\SyliusCatalogPromotionPlugin\Behat\Behaviour\SpecifiesItsDiscount;
use Webmozart\Assert\Assert;

class UpdatePage extends BaseUpdatePage implements UpdatePageInterface
{
    use NamesIt;
    use SpecifiesItsDiscount;
    use ChecksCodeImmutability;
    use PageDefinedElements;

    public function setPriority(?string $priority): void
    {
        $this->getDocument()->fillField('Priority', $priority ?? '');
    }

    public function getPriority(): string
    {
        /** @var string $value */
        $value = $this->getElement('priority')->getValue();

        return $value;
    }

    public function checkChannelsState(string $channelName): bool
    {
        $field = $this->getDocument()->findField($channelName);
        Assert::notNull($field);

        return (bool) $field->getValue();
    }

    public function makeExclusive(): void
    {
        $this->getDocument()->checkField('Exclusive');
    }

    public function checkChannel(string $name): void
    {
        $this->getDocument()->checkField($name);
    }

    public function setStartsAt(\DateTimeInterface $dateTime): void
    {
        $timestamp = $dateTime->getTimestamp();

        $this->getDocument()->fillField('setono_sylius_catalog_promotion_promotion_startsAt_date', date('Y-m-d', $timestamp));
        $this->getDocument()->fillField('setono_sylius_catalog_promotion_promotion_startsAt_time', date('H:i', $timestamp));
    }

    public function setEndsAt(\DateTimeInterface $dateTime): void
    {
        $timestamp = $dateTime->getTimestamp();

        $this->getDocument()->fillField('setono_sylius_catalog_promotion_promotion_endsAt_date', date('Y-m-d', $timestamp));
        $this->getDocument()->fillField('setono_sylius_catalog_promotion_promotion_endsAt_time', date('H:i', $timestamp));
    }

    public function hasStartsAt(\DateTimeInterface $dateTime): bool
    {
        $timestamp = $dateTime->getTimestamp();

        return $this->getElement('starts_at_date')->getValue() === date('Y-m-d', $timestamp) &&
            $this->getElement('starts_at_time')->getValue() === date('H:i', $timestamp);
    }

    public function hasEndsAt(\DateTimeInterface $dateTime): bool
    {
        $timestamp = $dateTime->getTimestamp();

        return $this->getElement('ends_at_date')->getValue() === date('Y-m-d', $timestamp) &&
            $this->getElement('ends_at_time')->getValue() === date('H:i', $timestamp);
    }

    protected function getCodeElement(): NodeElement
    {
        return $this->getElement('code');
    }
}
