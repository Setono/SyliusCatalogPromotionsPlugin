<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use DateTimeInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion\CreatePageInterface;
use Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion\IndexPageInterface;
use Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Page\Admin\Promotion\UpdatePageInterface;
use function sprintf;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Behat\Service\Resolver\CurrentPageResolverInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Webmozart\Assert\Assert;

final class ManagingPromotionsContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var IndexPageInterface */
    private $indexPage;

    /** @var CreatePageInterface */
    private $createPage;

    /** @var UpdatePageInterface */
    private $updatePage;

    /** @var CurrentPageResolverInterface */
    private $currentPageResolver;

    /** @var NotificationCheckerInterface */
    private $notificationChecker;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        IndexPageInterface $indexPage,
        CreatePageInterface $createPage,
        UpdatePageInterface $updatePage,
        CurrentPageResolverInterface $currentPageResolver,
        NotificationCheckerInterface $notificationChecker,
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->indexPage = $indexPage;
        $this->createPage = $createPage;
        $this->updatePage = $updatePage;
        $this->currentPageResolver = $currentPageResolver;
        $this->notificationChecker = $notificationChecker;
    }

    /**
     * @When I want to create a new catalog promotion
     */
    public function iWantToCreateANewDiscount(): void
    {
        $this->createPage->open();
    }

    /**
     * @Given I want to browse catalog promotions
     * @When I browse catalog promotions
     */
    public function iWantToBrowseDiscounts(): void
    {
        $this->indexPage->open();
    }

    /**
     * @When I specify :actionPercent% action percent
     * @When I do not specify its action percent
     */
    public function iSpecifyItsActionPercent(?float $actionPercent = null): void
    {
        $this->createPage->specifyDiscount($actionPercent);
    }

    /**
     * @When I specify :promotion% promotion
     * @When I do not specify promotion
     */
    public function iSpecifyItsDiscount(?string $promotion = null): void
    {
        $this->createPage->specifyDiscount((float) $promotion);
    }

    /**
     * @When I specify its code as :code
     * @When I do not specify its code
     */
    public function iSpecifyItsCodeAs(?string $code = null): void
    {
        $this->createPage->specifyCode($code ?? '');
    }

    /**
     * @When I name it :name
     * @When I do not name it
     * @When I remove its name
     */
    public function iNameIt(?string $name = null): void
    {
        $this->createPage->nameIt($name ?? '');
    }

    /**
     * @When I remove its priority
     */
    public function iRemoveItsPriority(): void
    {
        $this->updatePage->setPriority(null);
    }

    /**
     * @Then I should see the catalog promotion :promotionName in the list
     * @Then the :promotionName catalog promotion should appear in the registry
     * @Then the :promotionName catalog promotion should exist in the registry
     * @Then this catalog promotion should still be named :promotionName
     * @Then catalog promotion :promotionName should still exist in the registry
     */
    public function theCatalogPromotionShouldAppearInTheRegistry(string $promotionName): void
    {
        $this->indexPage->open();

        Assert::true($this->indexPage->isSingleResourceOnPage(['name' => $promotionName]));
    }

    /**
     * @When I add it
     * @When I try to add it
     */
    public function iAddIt(): void
    {
        $this->createPage->create();
    }

    /**
     * @When I add the "Product having one of taxons" rule configured with :firstTaxon
     * @When I add the "Product having one of taxons" rule configured with :firstTaxon or :secondTaxon
     */
    public function iAddTheHasTaxonRuleConfiguredWith(string ...$taxons): void
    {
        $this->createPage->addRule('Product having one of taxons');
        $this->createPage->selectAutocompleteRuleOption('Taxons', $taxons, true);
    }

    /**
     * @When I add the "Product is one of" rule configured with the :productName product
     * @When I add the "Product is one of" rule configured with the :firstProductName or :secondProductName product
     */
    public function iAddTheRuleConfiguredWithTheProducts(string ...$productNames): void
    {
        $this->createPage->addRule('Product is one of');
        $this->createPage->selectAutocompleteRuleOption('Products', $productNames, true);
    }

    /**
     * @When I add the "Product is" rule configured with the :productName product
     */
    public function iAddTheRuleConfiguredWithTheProduct(string $productName): void
    {
        $this->createPage->addRule('Product is');
        $this->createPage->selectAutocompleteRuleOption('Product', $productName);
    }

    /**
     * @When I check (also) the :promotionName catalog promotion
     */
    public function iCheckTheDiscount(string $promotionName): void
    {
        $this->indexPage->checkResourceOnPage(['name' => $promotionName]);
    }

    /**
     * @When I delete them
     */
    public function iDeleteThem(): void
    {
        $this->indexPage->bulkDelete();
    }

    /**
     * @Then I should see a single catalog promotion in the list
     * @Then there should be :amount catalog promotions
     */
    public function thereShouldBeCatalogPromotion(int $amount = 1): void
    {
        Assert::same($amount, $this->indexPage->countItems());
    }

    /**
     * @Then I should be notified that :element is required
     */
    public function iShouldBeNotifiedThatIsRequired(string $element): void
    {
        $this->assertFieldValidationMessage($element, sprintf('Please enter promotion %s.', $element));
    }

    /**
     * @Then I should be notified that a :element value should be a numeric value
     */
    public function iShouldBeNotifiedThatAMinimalValueShouldBeNumeric(string $element): void
    {
        $this->assertFieldValidationMessage($element, 'This value is not valid.');
    }

    /**
     * @Then I should be notified that catalog promotion with this code already exists
     */
    public function iShouldBeNotifiedThatDiscountWithThisCodeAlreadyExists(): void
    {
        Assert::same($this->createPage->getValidationMessage('code'), 'The promotion with given code already exists.');
    }

    /**
     * @Then catalog promotion with :element :name should not be added
     */
    public function promotionWithElementValueShouldNotBeAdded(string $element, string $name): void
    {
        $this->indexPage->open();

        Assert::false($this->indexPage->isSingleResourceOnPage([$element => $name]));
    }

    /**
     * @Then there should still be only one catalog promotion with :element :value
     */
    public function thereShouldStillBeOnlyOneDiscountWith(string $element, string $value): void
    {
        $this->indexPage->open();

        Assert::true($this->indexPage->isSingleResourceOnPage([$element => $value]));
    }

    /**
     * @When I make it exclusive
     */
    public function iMakeItExclusive(): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        $currentPage->makeExclusive();
    }

    /**
     * @Then the :catalogPromotion catalog promotion should be exclusive
     */
    public function theDiscountShouldBeExclusive(PromotionInterface $catalogPromotion): void
    {
        $this->assertIfFieldIsTrue($catalogPromotion, 'exclusive');
    }

    /**
     * @When I make it applicable for the :channelName channel
     */
    public function iMakeItApplicableForTheChannel(string $channelName): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        $currentPage->checkChannel($channelName);
    }

    /**
     * @Then the :catalogPromotion catalog promotion should be applicable for the :channelName channel
     */
    public function theDiscountShouldBeApplicableForTheChannel(PromotionInterface $catalogPromotion, string $channelName): void
    {
        $this->iWantToModifyADiscount($catalogPromotion);

        Assert::true($this->updatePage->checkChannelsState($channelName));
    }

    /**
     * @Given I want to modify a :catalogPromotion catalog promotion
     * @Given /^I want to modify (this catalog promotion)$/
     * @Then I should be able to modify a :catalogPromotion catalog promotion
     */
    public function iWantToModifyADiscount(PromotionInterface $catalogPromotion): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
    }

    /**
     * @Then the code field should be disabled
     */
    public function theCodeFieldShouldBeDisabled(): void
    {
        Assert::true($this->updatePage->isCodeDisabled());
    }

    /**
     * @When I save my changes
     * @When I try to save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I delete a ("([^"]+)" catalog promotion)$/
     * @When /^I try to delete a ("([^"]+)" catalog promotion)$/
     */
    public function iDeleteDiscount(PromotionInterface $promotion): void
    {
        $this->sharedStorage->set('catalog_promotion', $promotion);

        $this->indexPage->open();
        $this->indexPage->deleteResourceOnPage(['name' => $promotion->getName()]);
    }

    /**
     * @Then /^(this catalog promotion) should no longer exist in the catalog promotion registry$/
     */
    public function promotionShouldNotExistInTheRegistry(PromotionInterface $promotion): void
    {
        $this->indexPage->open();

        Assert::false($this->indexPage->isSingleResourceOnPage(['code' => $promotion->getCode()]));
    }

    /**
     * @Then I should be notified that it is in use and cannot be deleted
     */
    public function iShouldBeNotifiedOfFailure(): void
    {
        $this->notificationChecker->checkNotification(
            'Cannot delete, the promotion is in use.',
            NotificationType::failure(),
        );
    }

    /**
     * @When I make it available from :startsDate to :endsDate
     */
    public function iMakeItAvailableFromTo(DateTimeInterface $startsDate, DateTimeInterface $endsDate): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        $currentPage->setStartsAt($startsDate);
        $currentPage->setEndsAt($endsDate);
    }

    /**
     * @Then the :catalogPromotion catalog promotion should be available from :startsDate to :endsDate
     */
    public function theDiscountShouldBeAvailableFromTo(PromotionInterface $catalogPromotion, DateTimeInterface $startsDate, DateTimeInterface $endsDate): void
    {
        $this->iWantToModifyADiscount($catalogPromotion);

        Assert::true($this->updatePage->hasStartsAt($startsDate));

        Assert::true($this->updatePage->hasEndsAt($endsDate));
    }

    /**
     * @Then I should be notified that catalog promotion cannot end before it start
     */
    public function iShouldBeNotifiedThatDiscountCannotEndBeforeItsEvenStart(): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        Assert::same($currentPage->getValidationMessage('ends_at'), 'End date cannot be set prior start date.');
    }

    /**
     * @Then I should be notified that this value should not be blank
     */
    public function iShouldBeNotifiedThatThisValueShouldNotBeBlank(): void
    {
        Assert::same(
            $this->createPage->getValidationMessageForAction(),
            'This value should not be blank.',
        );
    }

    /**
     * @Then I should be notified that catalog promotion discount range is 0% to 100%
     */
    public function iShouldBeNotifiedThatTheMaximumValueOfDiscountIs100(): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        Assert::same($currentPage->getValidationMessage('discount'), 'Please enter value between 0% and 100%.');
    }

    /**
     * @Then I should see :count catalog promotions on the list
     */
    public function iShouldSeeDiscountsOnTheList(int $count): void
    {
        $actualCount = $this->indexPage->countItems();

        Assert::same(
            $count,
            $actualCount,
            'There should be %s promotion, but there\'s %2$s.',
        );
    }

    /**
     * @Then the first catalog promotion on the list should have :field :value
     */
    public function theFirstDiscountOnTheListShouldHave(string $field, string $value): void
    {
        $fields = $this->indexPage->getColumnFields($field);
        $actualValue = reset($fields);

        Assert::same(
            $actualValue,
            $value,
            sprintf('Expected first promotion\'s %s to be "%s", but it is "%s".', $field, $value, $actualValue),
        );
    }

    /**
     * @Then the last catalog promotion on the list should have :field :value
     */
    public function theLastDiscountOnTheListShouldHave(string $field, string $value): void
    {
        $fields = $this->indexPage->getColumnFields($field);
        $actualValue = end($fields);

        Assert::same(
            $actualValue,
            $value,
            sprintf('Expected last promotion\'s %s to be "%s", but it is "%s".', $field, $value, $actualValue),
        );
    }

    /**
     * @Given the :catalogPromotion catalog promotion should have priority :priority
     */
    public function theDiscountsShouldHavePriority(PromotionInterface $catalogPromotion, string $priority): void
    {
        $this->iWantToModifyADiscount($catalogPromotion);

        Assert::same($this->updatePage->getPriority(), $priority);
    }

    private function assertFieldValidationMessage(string $element, string $expectedMessage): void
    {
        /** @var CreatePageInterface|UpdatePageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        Assert::same($currentPage->getValidationMessage($element), $expectedMessage);
    }

    private function assertIfFieldIsTrue(PromotionInterface $promotion, string $field): void
    {
        $this->iWantToModifyADiscount($promotion);

        Assert::true($this->updatePage->hasResourceValues([$field => 1]));
    }
}
