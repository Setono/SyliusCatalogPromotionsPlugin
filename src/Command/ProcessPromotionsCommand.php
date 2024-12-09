<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Command;

use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use Setono\JobStatusBundle\Entity\JobInterface;
use Setono\JobStatusBundle\Entity\Spec\LastJobWithType;
use Setono\JobStatusBundle\Factory\JobFactoryInterface;
use Setono\JobStatusBundle\Manager\JobManagerInterface;
use Setono\JobStatusBundle\Repository\JobRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\ChannelPricingRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\ProductRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\ProductVariantRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Setono\SyliusCatalogPromotionPlugin\Rule\ManuallyDiscountedProductsExcludedRule;
use Setono\SyliusCatalogPromotionPlugin\Rule\RuleInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class ProcessPromotionsCommand extends Command
{
    use LockableTrait;

    private const JOB_TYPE = 'sscp_process_promotions';

    protected static $defaultName = 'setono:sylius-catalog-promotion:process';

    private JobRepositoryInterface $jobRepository;

    private JobFactoryInterface $jobFactory;

    private JobManagerInterface $jobManager;

    private ChannelPricingRepositoryInterface $channelPricingRepository;

    private ProductRepositoryInterface $productRepository;

    private ProductVariantRepositoryInterface $productVariantRepository;

    private PromotionRepositoryInterface $promotionRepository;

    private ServiceRegistryInterface $ruleRegistry;

    private int $jobTtl;

    /**
     * @param int $jobTtl the ttl we set for the job (in seconds)
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        JobFactoryInterface $jobFactory,
        JobManagerInterface $jobManager,
        ChannelPricingRepositoryInterface $channelPricingRepository,
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        PromotionRepositoryInterface $promotionRepository,
        ServiceRegistryInterface $ruleRegistry,
        int $jobTtl,
    ) {
        parent::__construct();

        $this->jobRepository = $jobRepository;
        $this->jobFactory = $jobFactory;
        $this->jobManager = $jobManager;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->promotionRepository = $promotionRepository;
        $this->ruleRegistry = $ruleRegistry;
        $this->jobTtl = $jobTtl;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Processes all promotions')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the computation of promotions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Assert::isInstanceOf($this->productVariantRepository, EntityRepository::class);

        /** @var JobInterface|mixed|null $lastJob */
        $lastJob = $this->jobRepository->matchOneOrNullResult(new LastJobWithType(self::JOB_TYPE));
        Assert::nullOrIsInstanceOf($lastJob, JobInterface::class);

        if (null !== $lastJob && $lastJob->isRunning()) {
            $output->writeln('The job is already running');

            return 0;
        }

        $force = true === $input->getOption('force');

        $promotions = $this->promotionRepository->findForProcessing();
        $promotionIds = array_map(static function (PromotionInterface $promotion): int {
            return (int) $promotion->getId();
        }, $promotions);

        if (!$force && !$this->isProcessingAllowed($promotionIds, $lastJob)) {
            $output->writeln(
                'Nothing to process at the moment. Run command with --force option to force process',
                OutputInterface::VERBOSITY_VERBOSE,
            );

            return 0;
        }

        $job = $this->jobFactory->createNew();
        $job->setExclusive(true);
        $job->setType(self::JOB_TYPE);
        $job->setName('Sylius Catalog Promotion plugin: Process promotions');
        $job->setTtl($this->jobTtl);
        $job->setMetadataEntry('promotions', $promotionIds);

        $this->jobManager->start($job, 3);

        $startTime = $job->getStartedAt();
        Assert::notNull($startTime);

        $bulkIdentifier = uniqid('bulk-', true);

        $this->channelPricingRepository->resetMultiplier($startTime, $bulkIdentifier);
        $this->jobManager->advance($job);

        foreach ($promotions as $promotion) {
            $qb = $this->productVariantRepository->createQueryBuilder('o');
            $qb->select('o.id');
            $qb->distinct();

            if ($promotion->isManuallyDiscountedProductsExcluded()) {
                (new ManuallyDiscountedProductsExcludedRule())->filter($qb, []);
            }

            foreach ($promotion->getRules() as $rule) {
                if ($rule->getType() === null) {
                    continue;
                }

                if (!$this->ruleRegistry->has((string) $rule->getType())) {
                    // todo should this throw an exception or give an error somewhere?
                    continue;
                }

                /** @var RuleInterface $ruleQueryBuilder */
                $ruleQueryBuilder = $this->ruleRegistry->get((string) $rule->getType());

                $ruleQueryBuilder->filter($qb, $rule->getConfiguration());
            }

            $bulkSize = 100;
            $qb->setMaxResults($bulkSize);
            $i = 0;

            do {
                $qb->setFirstResult($i * $bulkSize);

                /** @var array<array-key, int> $productVariantIds */
                $productVariantIds = $qb->getQuery()->getResult();

                $this->channelPricingRepository->updateMultiplier(
                    (string) $promotion->getCode(),
                    $promotion->getMultiplier(),
                    $productVariantIds,
                    $promotion->getChannelCodes(),
                    $startTime,
                    $bulkIdentifier,
                    $promotion->isExclusive(),
                    $promotion->isManuallyDiscountedProductsExcluded(),
                );

                ++$i;
            } while (count($productVariantIds) !== 0);
        }

        $this->jobManager->advance($job);

        $this->channelPricingRepository->updatePrices($bulkIdentifier);

        $this->jobManager->advance($job);
        $this->jobManager->finish($job);

        return 0;
    }

    private function isProcessingAllowed(array $promotionIds, ?JobInterface $lastJob): bool
    {
        // if there isn't no last job we can just process
        if (null === $lastJob) {
            return true;
        }

        $lastPromotionIds = $lastJob->getMetadataEntry('promotions');
        Assert::isArray($lastPromotionIds);

        // If last execution promotions not exact same as found for processing
        // or not at the same order - we can process
        if ($lastPromotionIds !== $promotionIds) {
            return true;
        }

        $lastJobStartedAt = $lastJob->getStartedAt();
        Assert::notNull($lastJobStartedAt);

        // If any relevant entities were updated - we can process
        if ($this->hasAnyBeenUpdatedSince($lastJobStartedAt)) {
            return true;
        }

        return false;
    }

    // As far as updatedAt field updating even when some fields not impacting promotion changed,
    // @todo Set catalog_promotions_process flag at memory storage / cache at subscribers once particular product/variant/taxon fields (that can impact promotion) changed
    private function hasAnyBeenUpdatedSince(DateTimeInterface $dateTime): bool
    {
        return $this->productRepository->hasAnyBeenUpdatedSince($dateTime) ||
            $this->productVariantRepository->hasAnyBeenUpdatedSince($dateTime) ||
            $this->channelPricingRepository->hasAnyBeenUpdatedSince($dateTime) ||
            $this->promotionRepository->hasAnyBeenUpdatedSince($dateTime)
        ;
    }
}
