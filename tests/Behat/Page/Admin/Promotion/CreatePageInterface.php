<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion;

use Sylius\Behat\Page\Admin\Crud\CreatePageInterface as BaseCreatePageInterface;

interface CreatePageInterface extends BaseCreatePageInterface
{
    public function specifyDiscount(?float $discount): void;

    public function specifyCode(string $code): void;

    public function nameIt(string $name): void;

    public function addRule(string $ruleName): void;

    public function selectRuleOption(string $option, string $value, bool $multiple = false): void;

    /**
     * @param string|string[] $value
     */
    public function selectAutocompleteRuleOption(string $option, $value, bool $multiple = false): void;

    public function fillRuleOption(string $option, string $value): void;

    public function makeExclusive(): void;

    public function checkChannel(string $name): void;

    public function setStartsAt(\DateTimeInterface $dateTime): void;

    public function setEndsAt(\DateTimeInterface $dateTime): void;

    public function getValidationMessageForAction(): string;
}
