<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Behaviour\SpecifiesItsDiscount;
use function sprintf;
use Sylius\Behat\Behaviour\NamesIt;
use Sylius\Behat\Behaviour\SpecifiesItsCode;
use Sylius\Behat\Page\Admin\Crud\CreatePage as BaseCreatePage;
use Sylius\Behat\Service\AutocompleteHelper;
use Webmozart\Assert\Assert;

class CreatePage extends BaseCreatePage implements CreatePageInterface
{
    use NamesIt;
    use SpecifiesItsCode;
    use SpecifiesItsDiscount;
    use PageDefinedElements;

    public function addRule(string $ruleName): void
    {
        $count = count($this->getCollectionItems('rules'));

        $this->getDocument()->clickLink('Add rule');

        $this->getDocument()->waitFor(5, function () use ($count): bool {
            return $count + 1 === count($this->getCollectionItems('rules'));
        });

        $this->selectRuleOption('Type', $ruleName);
    }

    public function selectRuleOption(string $option, string $value, bool $multiple = false): void
    {
        $select = $this->getLastCollectionItem('rules')->find('named', ['select', $option]);

        Assert::notNull($select);

        $select->selectOption($value, $multiple);
    }

    public function selectAutocompleteRuleOption(string $option, $value, bool $multiple = false): void
    {
        $option = mb_strtolower(str_replace(' ', '_', $option));

        $element = $this
            ->getLastCollectionItem('rules')
            ->find('css', sprintf('input[type="hidden"][name*="[%s]"]', $option))
        ;
        Assert::notNull($element);

        $ruleAutocomplete = $element->getParent();

        if ($multiple && is_array($value)) {
            AutocompleteHelper::chooseValues($this->getSession(), $ruleAutocomplete, $value);

            return;
        }

        Assert::string($value);
        AutocompleteHelper::chooseValue($this->getSession(), $ruleAutocomplete, $value);
    }

    public function fillRuleOption(string $option, string $value): void
    {
        $this->getLastCollectionItem('rules')->fillField($option, $value);
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

    private function getLastCollectionItem(string $collection): NodeElement
    {
        $items = $this->getCollectionItems($collection);

        Assert::notEmpty($items);

        $item = end($items);
        Assert::notFalse($item);

        return $item;
    }

    /**
     * @return NodeElement[]|array
     */
    private function getCollectionItems(string $collection): array
    {
        return $this->getElement($collection)->findAll('css', 'div[data-form-collection="item"]');
    }

    public function getValidationMessageForAction(): string
    {
        $actionForm = $this->getLastCollectionItem('actions');

        $foundElement = $actionForm->find('css', '.sylius-validation-error');
        if (null === $foundElement) {
            throw new ElementNotFoundException($this->getSession(), 'Tag', 'css', '.sylius-validation-error');
        }

        return $foundElement->getText();
    }
}
