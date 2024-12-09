<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion;

use Sylius\Behat\Page\Admin\Crud\UpdatePageInterface as BaseUpdatePageInterface;

interface UpdatePageInterface extends BaseUpdatePageInterface
{
    public function setPriority(?string $priority): void;

    public function getPriority(): ?string;

    public function nameIt(string $name): void;

    public function checkChannelsState(string $channelName): bool;

    public function isCodeDisabled(): bool;

    public function makeExclusive(): void;

    public function checkChannel(string $name): void;

    public function setStartsAt(\DateTimeInterface $dateTime): void;

    public function setEndsAt(\DateTimeInterface $dateTime): void;

    public function hasStartsAt(\DateTimeInterface $dateTime): bool;

    public function hasEndsAt(\DateTimeInterface $dateTime): bool;
}
