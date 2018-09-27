<?php

declare(strict_types=1);

namespace Setono\SyliusBulkSpecialsPlugin\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Interface SpecialSubjectInterface
 */
interface SpecialSubjectInterface
{
    /**
     * @return bool
     */
    public function hasExclusiveSpecials(): bool;

    /**
     * @return SpecialInterface|null
     */
    public function getFirstExclusiveSpecial(): ?SpecialInterface;

    /**
     * @return Collection|SpecialInterface[]
     */
    public function getExclusiveSpecials(): Collection;

    /**
     * @return Collection|SpecialInterface[]
     */
    public function getActiveSpecials(): Collection;

    /**
     * @param string $channelCode
     *
     * @return bool
     */
    public function hasExclusiveSpecialsForChannelCode(string $channelCode): bool;

    /**
     * @param string $channelCode
     *
     * @return SpecialInterface|null
     */
    public function getFirstExclusiveSpecialForChannelCode(string $channelCode): ?SpecialInterface;

    /**
     * @param string $channelCode
     *
     * @return Collection
     */
    public function getExclusiveSpecialsForChannelCode(string $channelCode): Collection;

    /**
     * @param string $channelCode
     *
     * @return Collection|SpecialInterface[]
     */
    public function getActiveSpecialsForChannelCode(string $channelCode): Collection;

    /**
     * @return Collection|SpecialInterface[]
     */
    public function getSpecials(): Collection;

    /**
     * @param SpecialInterface $special
     *
     * @return bool
     */
    public function hasSpecial(SpecialInterface $special): bool;

    /**
     * @param SpecialInterface $special
     */
    public function addSpecial(SpecialInterface $special): void;

    /**
     * @param SpecialInterface $special
     */
    public function removeSpecial(SpecialInterface $special): void;
}