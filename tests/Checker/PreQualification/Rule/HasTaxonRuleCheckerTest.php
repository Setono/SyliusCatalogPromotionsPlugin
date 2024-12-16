<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Checker\PreQualification\Rule;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\HasTaxonRuleChecker;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Webmozart\Assert\InvalidArgumentException;

final class HasTaxonRuleCheckerTest extends TestCase
{
    use ProphecyTrait;

    private HasTaxonRuleChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new HasTaxonRuleChecker();
    }

    /**
     * @test
     */
    public function it_returns_true_if_product_has_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getCode()->willReturn('TAXON_CODE');

        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection([$taxon->reveal()]));
        $product->getMainTaxon()->willReturn(null);

        $result = $this->checker->isEligible($product->reveal(), ['taxons' => ['TAXON_CODE']]);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_true_if_product_has_main_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getCode()->willReturn('TAXON_CODE');

        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection());
        $product->getMainTaxon()->willReturn($taxon->reveal());

        $result = $this->checker->isEligible($product->reveal(), ['taxons' => ['TAXON_CODE']]);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_false_if_product_does_not_have_taxon(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection());
        $product->getMainTaxon()->willReturn(null);

        $result = $this->checker->isEligible($product->reveal(), ['taxons' => ['TAXON_CODE']]);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_configuration_does_not_have_taxons_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection());
        $product->getMainTaxon()->willReturn(null);

        $this->checker->isEligible($product->reveal(), ['invalid_key' => 'value']);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_taxons_key_is_not_an_array(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection());
        $product->getMainTaxon()->willReturn(null);

        $this->checker->isEligible($product->reveal(), ['taxons' => 'not_an_array']);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_taxons_array_contains_non_string_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getTaxons()->willReturn(new ArrayCollection());
        $product->getMainTaxon()->willReturn(null);

        $this->checker->isEligible($product->reveal(), ['taxons' => [123]]);
    }
}
