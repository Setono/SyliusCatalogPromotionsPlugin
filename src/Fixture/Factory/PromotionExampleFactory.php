<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Fixture\Factory;

use DateTime;
use DateTimeInterface;
use Faker\Generator;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionRuleInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Bundle\CoreBundle\Fixture\OptionsResolver\LazyOption;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

class PromotionExampleFactory extends AbstractExampleFactory
{
    protected ChannelRepositoryInterface $channelRepository;

    protected PromotionRepositoryInterface $promotionRepository;

    protected Factory $promotionFactory;

    protected PromotionRuleExampleFactory $promotionRuleExampleFactory;

    protected Generator $faker;

    protected OptionsResolver $optionsResolver;

    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        PromotionRepositoryInterface $promotionRepository,
        Factory $promotionFactory,
        PromotionRuleExampleFactory $promotionRuleExampleFactory,
    ) {
        $this->channelRepository = $channelRepository;
        $this->promotionRepository = $promotionRepository;
        $this->promotionFactory = $promotionFactory;
        $this->promotionRuleExampleFactory = $promotionRuleExampleFactory;

        $this->faker = \Faker\Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): PromotionInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var PromotionInterface|null $promotion */
        $promotion = $this->promotionRepository->findOneBy(['code' => $options['code']]);
        if (null === $promotion) {
            /** @var PromotionInterface $promotion */
            $promotion = $this->promotionFactory->createNew();
        }

        $promotion->setCode($options['code']);
        $promotion->setName($options['name']);
        $promotion->setDescription($options['description']);

        $promotion->setPriority((int) $options['priority']);
        $promotion->setExclusive($options['exclusive']);

        if (isset($options['starts_at'])) {
            $promotion->setStartsAt(new DateTime($options['starts_at']));
        }

        if (isset($options['ends_at'])) {
            $promotion->setEndsAt(new DateTime($options['ends_at']));
        }
        $promotion->setEnabled($options['enabled']);

        foreach ($options['channels'] as $channel) {
            $promotion->addChannel($channel);
        }

        foreach ($options['rules'] as $ruleOptions) {
            /** @var PromotionRuleInterface $promotionRule */
            $promotionRule = $this->promotionRuleExampleFactory->create($ruleOptions);
            $promotion->addRule($promotionRule);
        }

        $promotion->setDiscount($options['discount']);

        $promotion->setCreatedAt($options['created_at']);
        $promotion->setUpdatedAt($options['updated_at']);

        return $promotion;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('code', static function (Options $options): string {
                return StringInflector::nameToCode($options['name']);
            })
            ->setDefault('name', function (Options $options): string {
                /** @var string $text */
                $text = $this->faker->words(3, true);

                return $text;
            })
            ->setDefault('description', function (Options $options): string {
                return $this->faker->sentence();
            })

            ->setDefault('priority', 0)
            ->setAllowedTypes('priority', 'int')

            ->setDefault('exclusive', function (Options $options): bool {
                return $this->faker->boolean(25);
            })

            ->setDefault('starts_at', null)
            ->setAllowedTypes('starts_at', ['null', 'string'])
            ->setDefault('ends_at', null)
            ->setAllowedTypes('ends_at', ['null', 'string'])

            ->setDefault('enabled', function (Options $options): bool {
                return $this->faker->boolean(90);
            })

            ->setDefault('discount', function (Options $options): float {
                return $this->faker->randomFloat(3, 0, 100);
            })
            ->setNormalizer('discount', static function (Options $options, $value): float {
                if ($value >= 0 && $value <= 100) {
                    $value = $value / 100;
                }

                Assert::range($value, 0, 1, 'Discount can be set in 0..100 range');

                return $value;
            })
            ->setAllowedTypes('discount', ['int', 'float'])

            ->setDefault('created_at', null)
            ->setAllowedTypes('created_at', ['null', DateTimeInterface::class])
            ->setDefault('updated_at', null)
            ->setAllowedTypes('updated_at', ['null', DateTimeInterface::class])

            ->setDefined('rules')
            ->setNormalizer('rules', static function (Options $options, array $rules): array {
                if (count($rules) === 0) {
                    return [[]];
                }

                return $rules;
            })

            ->setDefault('channels', LazyOption::all($this->channelRepository))
            ->setAllowedTypes('channels', 'array')
            ->setNormalizer('channels', LazyOption::findBy($this->channelRepository, 'code'))
        ;
    }
}
