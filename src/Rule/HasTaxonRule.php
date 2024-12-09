<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Rule;

use Doctrine\ORM\QueryBuilder;
use function sprintf;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Webmozart\Assert\Assert;

final class HasTaxonRule extends Rule
{
    public const TYPE = 'has_taxon';

    private TaxonRepositoryInterface $taxonRepository;

    public function __construct(TaxonRepositoryInterface $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
    }

    public function filter(QueryBuilder $queryBuilder, array $configuration): void
    {
        $value = self::getConfigurationValue('taxons', $configuration);
        Assert::isArray($value);

        $rootAlias = $this->getRootAlias($queryBuilder);
        $productVariantAlias = self::generateAlias('pv');
        $productTaxonAlias = self::generateAlias('pt');
        $parameter = self::generateParameter('include_taxons');

        $taxons = array_map(function (string $taxonCode): TaxonInterface {
            /** @var TaxonInterface|null $taxon */
            $taxon = $this->taxonRepository->findOneBy(['code' => $taxonCode]);
            Assert::notNull($taxon);

            return $taxon;
        }, $value);

        $em = $queryBuilder->getEntityManager();
        $subQueryBuilder = $em->createQuery(
            sprintf('SELECT %s.id ', $productVariantAlias) .
            sprintf('FROM %s AS %s ', ProductVariant::class, $productVariantAlias) .
            sprintf('LEFT JOIN %s AS %s WITH %s.product=%s.product ', ProductTaxon::class, $productTaxonAlias, $productTaxonAlias, $productVariantAlias) .
            sprintf('WHERE %s.taxon IN (:%s)', $productTaxonAlias, $parameter),
        );

        /** @psalm-suppress PossiblyNullArgument */
        $queryBuilder
            ->andWhere(sprintf('%s.id IN (%s)', $rootAlias, $subQueryBuilder->getDQL()))
            ->setParameter($parameter, $taxons)
        ;
    }
}
