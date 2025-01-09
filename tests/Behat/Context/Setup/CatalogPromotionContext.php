<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusCatalogPromotionPlugin\Factory\CatalogPromotionRuleFactoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Test\Factory\TestPromotionFactoryInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Webmozart\Assert\Assert;

final class CatalogPromotionContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var CatalogPromotionRuleFactoryInterface */
    private $promotionRuleFactory;

    /** @var TestPromotionFactoryInterface */
    private $testDiscountFactory;

    /** @var CatalogPromotionRepositoryInterface */
    private $promotionRepository;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        CatalogPromotionRuleFactoryInterface $promotionRuleFactory,
        TestPromotionFactoryInterface $testDiscountFactory,
        CatalogPromotionRepositoryInterface $promotionRepository,
        ObjectManager $objectManager,
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->promotionRuleFactory = $promotionRuleFactory;
        $this->testDiscountFactory = $testDiscountFactory;
        $this->promotionRepository = $promotionRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * @Given there is a disabled catalog promotion for all products with a :percentage% discount
     */
    public function thereIsADisabledCatalogPromotionForAllProductsWithADiscount(string $percentage): void
    {
        $promotion = $this->testDiscountFactory->createForChannel(uniqid('catalog-promotion-', true), $this->sharedStorage->get('channel'));
        $promotion->setEnabled(false);
        $promotion->setDiscount((float) $percentage);
        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('catalog_promotion', $promotion);
    }

    /**
     * @Given there is a catalog promotion for all products with a :percentage% discount
     * @Given there is a catalog promotion for taxon :taxon with a :percentage% discount
     */
    public function thereIsACatalogPromotionWithADiscount(TaxonInterface $taxon = null, float $percentage): void
    {
        Assert::greaterThanEq($percentage, 0);
        Assert::lessThanEq($percentage, 100);

        $promotion = $this->testDiscountFactory->createForChannel(uniqid('catalog-promotion-', true), $this->sharedStorage->get('channel'));
        $promotion->setDiscount($percentage / 100);

        if (null !== $taxon) {
            $promotion->addRule($this->promotionRuleFactory->createHasTaxon([$taxon->getCode()]));
        }

        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('catalog_promotion', $promotion);
    }

    /**
     * @Given there is (also) a catalog promotion :promotionName
     * @Given there is (also) a catalog promotion :promotionName applicable for :channel channel
     * @Given there is a catalog promotion :promotionName identified by :promotionCode code
     */
    public function thereIsACatalogPromotion(string $promotionName, ?string $promotionCode = null, ?ChannelInterface $channel = null): void
    {
        if (null === $channel) {
            $channel = $this->sharedStorage->get('channel');
        }

        $promotion = $this->testDiscountFactory
            ->createForChannel($promotionName, $channel)
        ;

        if (null !== $promotionCode) {
            $promotion->setCode($promotionCode);
        }

        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('catalog_promotion', $promotion);
    }

    /**
     * @Given /^there is a catalog promotion "([^"]+)" with priority ([^"]+)$/
     */
    public function thereIsACatalogPromotionWithPriority(string $promotionName, int $priority): void
    {
        $promotion = $this->testDiscountFactory
            ->createForChannel($promotionName, $this->sharedStorage->get('channel'))
        ;

        $promotion->setPriority($priority);
        $promotion->setDiscount(1); // todo should be moved to another method

        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('catalog_promotion', $promotion);
    }

    /**
     * @Given /^there is an exclusive catalog promotion "([^"]+)"(?:| with priority ([^"]+))$/
     */
    public function thereIsAnExclusiveCatalogPromotionWithPriority(string $promotionName, int $priority = 0): void
    {
        $promotion = $this->testDiscountFactory
            ->createForChannel($promotionName, $this->sharedStorage->get('channel'))
        ;

        $promotion->setExclusive(true);
        $promotion->setPriority($priority);

        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('catalog_promotion', $promotion);
    }

    /**
     * @Given /^(this catalog promotion) was disabled$/
     */
    public function thisDiscountDisabled(CatalogPromotionInterface $promotion): void
    {
        $promotion->setEnabled(false);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this catalog promotion) was enabled$/
     */
    public function thisDiscountEnabled(CatalogPromotionInterface $promotion): void
    {
        $promotion->setEnabled(true);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this catalog promotion) has already expired$/
     */
    public function thisDiscountHasExpired(CatalogPromotionInterface $promotion): void
    {
        $promotion->setEndsAt(new DateTime('1 day ago'));

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this catalog promotion) expires tomorrow$/
     */
    public function thisDiscountExpiresTomorrow(CatalogPromotionInterface $promotion): void
    {
        $promotion->setEndsAt(new DateTime('tomorrow'));

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this catalog promotion) has started yesterday$/
     */
    public function thisDiscountHasStartedYesterday(CatalogPromotionInterface $promotion): void
    {
        $promotion->setStartsAt(new DateTime('1 day ago'));

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this catalog promotion) starts tomorrow$/
     */
    public function thisDiscountStartsTomorrow(CatalogPromotionInterface $promotion): void
    {
        $promotion->setStartsAt(new DateTime('tomorrow'));

        $this->objectManager->flush();
    }

    /**
     * @Given /^([^"]+) gives ("[^"]+%") discount/
     */
    public function itGivesPercentageDiscount(CatalogPromotionInterface $promotion, float $percentage): void
    {
        $this->persistDiscount(
            $this->setPercentageDiscount($promotion, $percentage),
        );
    }

    /**
     * @Given /^([^"]+) gives(?:| another) ("[^"]+%") off on every product (classified as "[^"]+")$/
     */
    public function itGivesPercentageOffEveryProductClassifiedAs(
        CatalogPromotionInterface $promotion,
        float $percentage,
        TaxonInterface $taxon,
    ): void {
        $this->createPercentageDiscount(
            $promotion,
            $percentage,
            $this->promotionRuleFactory->createHasTaxon([
                $taxon->getCode(),
            ]),
        );
    }

    /**
     * @Given /^([^"]+) gives ("[^"]+%") off on every product (classified as "[^"]+" or "[^"]+")$/
     */
    public function itGivesOffOnEveryProductClassifiedAs(
        CatalogPromotionInterface $promotion,
        float $percentage,
        array $promotionTaxons,
    ): void {
        $promotionTaxonsCodes = [$promotionTaxons[0]->getCode(), $promotionTaxons[1]->getCode()];
        $this->createPercentageDiscount(
            $promotion,
            $percentage,
            $this->promotionRuleFactory->createHasTaxon($promotionTaxonsCodes),
        );
    }

    /**
     * @Given /^([^"]+) gives ("[^"]+%") off on a ("[^"]*" product)$/
     * @Given /^([^"]+) gives ("[^"]+%") off on that product$/
     */
    public function itGivesPercentageDiscountOffOnAProduct(
        CatalogPromotionInterface $promotion,
        float $percentage,
        ?ProductInterface $product = null,
    ): void {
        if (null == $product) {
            $product = $this->sharedStorage->get('product');
        }

        $this->createPercentageDiscount(
            $promotion,
            $percentage,
            $this->promotionRuleFactory->createContainsProduct($product->getCode()),
        );
    }

    /**
     * @Given /^([^"]+) gives ("[^"]+%") off on a ("[^"]+" or "[^"]+" product)$/
     */
    public function itGivesPercentageDiscountOffOnAProducts(
        CatalogPromotionInterface $promotion,
        float $percentage,
        array $products,
    ): void {
        $productCodes = [$products[0]->getCode(), $products[1]->getCode()];
        $this->createPercentageDiscount(
            $promotion,
            $percentage,
            $this->promotionRuleFactory->createContainsProducts($productCodes),
        );
    }

    /**
     * @Given /^(this promotion) applicable for (all channels)$/
     * @Given /^promotion :promotion applicable for (all channels)$/
     */
    public function promotionApplicableForAllChannels(CatalogPromotionInterface $promotion, array $channels): void
    {
        foreach ($channels as $channel) {
            $promotion->addChannel($channel);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^(the catalog promotion) was disabled for the (channel "[^"]+")$/
     */
    public function theDiscountWasDisabledForTheChannel(CatalogPromotionInterface $promotion, ChannelInterface $channel): void
    {
        $promotion->removeChannel($channel);

        $this->objectManager->flush();
    }

    private function createPercentageDiscount(
        CatalogPromotionInterface $promotion,
        float $percentage,
        CatalogPromotionRuleInterface $rule = null,
    ): void {
        $this->persistDiscount(
            $this->setPercentageDiscount($promotion, $percentage),
            $rule,
        );
    }

    private function persistDiscount(CatalogPromotionInterface $promotion, CatalogPromotionRuleInterface $rule = null): void
    {
        if (null !== $rule) {
            $promotion->addRule($rule);
        }

        $this->objectManager->flush();
    }

    private function setPercentageDiscount(CatalogPromotionInterface $promotion, float $percentage): CatalogPromotionInterface
    {
        $promotion->setDiscount($percentage * 100);

        return $promotion;
    }
}
