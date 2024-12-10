<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Tests\Application\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductInterface as CatalogPromotionProductInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\ProductTrait as CatalogPromotionProductTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

/**
 * @ORM\Table(name="sylius_product")
 *
 * @ORM\Entity()
 */
class Product extends BaseProduct implements CatalogPromotionProductInterface
{
    use CatalogPromotionProductTrait;
}
