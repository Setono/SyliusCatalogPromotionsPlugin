<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-catalog-promotion:prune-catalog-promotion-updates',
    description: 'Will remove catalog promotion updates older than the defined threshold',
)]
final class PruneCatalogPromotionUpdatesCommand extends Command
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        /** @var class-string<CatalogPromotionUpdateInterface> $catalogPromotionUpdateClass */
        private readonly string $catalogPromotionUpdateClass,
        private readonly string $threshold = '-2 days',
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this
            ->getManager($this->catalogPromotionUpdateClass)
            ->createQueryBuilder()
            ->delete($this->catalogPromotionUpdateClass, 'o')
            ->andWhere('o.createdAt < :threshold')
            ->setParameter('threshold', new \DateTimeImmutable($this->threshold))
            ->getQuery()
            ->execute()
        ;

        return 0;
    }
}
