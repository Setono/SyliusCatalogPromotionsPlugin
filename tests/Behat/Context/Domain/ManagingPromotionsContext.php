<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Context\Domain;

use Behat\Behat\Context\Context;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Webmozart\Assert\Assert;

final class ManagingPromotionsContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var PromotionRepositoryInterface */
    private $promotionRepository;

    public function __construct(SharedStorageInterface $sharedStorage, PromotionRepositoryInterface $promotionRepository)
    {
        $this->sharedStorage = $sharedStorage;
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * @When /^I delete a ("([^"]+)" catalog promotion)$/
     */
    public function iDeleteCatalogPromotion(PromotionInterface $promotion): void
    {
        $this->promotionRepository->remove($promotion);
    }

    /**
     * @When /^I try to delete a ("([^"]+)" catalog promotion)$/
     */
    public function iTryToDeleteCatalogPromotion(PromotionInterface $promotion): void
    {
        try {
            $this->promotionRepository->remove($promotion);
        } catch (ForeignKeyConstraintViolationException $exception) {
            $this->sharedStorage->set('last_exception', $exception);
        }
    }

    /**
     * @Then /^(this catalog promotion) should no longer exist in the catalog promotion registry$/
     */
    public function specialShouldNotExistInTheRegistry(PromotionInterface $promotion): void
    {
        Assert::null($this->promotionRepository->findOneBy(['code' => $promotion->getCode()]));
    }

    /**
     * @Then catalog promotion :special should still exist in the registry
     */
    public function catalogPromotionShouldStillExistInTheRegistry(PromotionInterface $promotion): void
    {
        Assert::notNull($this->promotionRepository->find($promotion->getId()));
    }

    /**
     * @Then I should be notified that it is in use and cannot be deleted
     */
    public function iShouldBeNotifiedOfFailure(): void
    {
        Assert::isInstanceOf($this->sharedStorage->get('last_exception'), ForeignKeyConstraintViolationException::class);
    }
}
