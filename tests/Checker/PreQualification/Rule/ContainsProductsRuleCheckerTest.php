<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Checker\PreQualification\Rule;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCatalogPromotionPlugin\Checker\PreQualification\Rule\ContainsProductsRuleChecker;
use Sylius\Component\Core\Model\ProductInterface;
use Webmozart\Assert\InvalidArgumentException;

final class ContainsProductsRuleCheckerTest extends TestCase
{
    use ProphecyTrait;

    private ContainsProductsRuleChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ContainsProductsRuleChecker();
    }

    /**
     * @test
     */
    public function it_returns_true_if_product_is_eligible(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PRODUCT_CODE');

        $result = $this->checker->isEligible($product->reveal(), ['products' => ['PRODUCT_CODE']]);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_false_if_product_is_not_eligible(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PRODUCT_CODE');

        $result = $this->checker->isEligible($product->reveal(), ['products' => ['ANOTHER_PRODUCT_CODE']]);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_configuration_does_not_have_products_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PRODUCT_CODE');

        $this->checker->isEligible($product->reveal(), ['invalid_key' => 'value']);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_products_key_is_not_an_array(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PRODUCT_CODE');

        $this->checker->isEligible($product->reveal(), ['products' => 'not_an_array']);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_products_array_contains_non_string_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PRODUCT_CODE');

        $this->checker->isEligible($product->reveal(), ['products' => [123]]);
    }
}
