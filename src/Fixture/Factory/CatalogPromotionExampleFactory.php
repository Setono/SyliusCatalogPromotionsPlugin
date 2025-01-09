<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Fixture\Factory;

use DateTime;
use DateTimeInterface;
use Faker\Generator;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\CatalogPromotionRepositoryInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Bundle\CoreBundle\Fixture\OptionsResolver\LazyOption;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

class CatalogPromotionExampleFactory extends AbstractExampleFactory
{
    protected Generator $faker;

    protected OptionsResolver $optionsResolver;

    public function __construct(
        protected readonly ChannelRepositoryInterface $channelRepository,
        protected readonly CatalogPromotionRepositoryInterface $catalogPromotionRepository,
        protected readonly Factory $catalogPromotionFactory,
        protected readonly CatalogPromotionRuleExampleFactory $catalogPromotionRuleExampleFactory,
    ) {
        $this->faker = \Faker\Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): CatalogPromotionInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var CatalogPromotionInterface|null $catalogPromotion */
        $catalogPromotion = $this->catalogPromotionRepository->findOneBy(['code' => $options['code']]);
        if (null === $catalogPromotion) {
            /** @var CatalogPromotionInterface $catalogPromotion */
            $catalogPromotion = $this->catalogPromotionFactory->createNew();
        }

        $catalogPromotion->setCode($options['code']);
        $catalogPromotion->setName($options['name']);
        $catalogPromotion->setDescription($options['description']);

        $catalogPromotion->setPriority((int) $options['priority']);
        $catalogPromotion->setExclusive($options['exclusive']);

        if (isset($options['starts_at'])) {
            $catalogPromotion->setStartsAt(new DateTime($options['starts_at']));
        }

        if (isset($options['ends_at'])) {
            $catalogPromotion->setEndsAt(new DateTime($options['ends_at']));
        }
        $catalogPromotion->setEnabled($options['enabled']);

        foreach ($options['channels'] as $channel) {
            $catalogPromotion->addChannel($channel);
        }

        foreach ($options['rules'] as $ruleOptions) {
            /** @var CatalogPromotionRuleInterface $catalogPromotionRule */
            $catalogPromotionRule = $this->catalogPromotionRuleExampleFactory->create($ruleOptions);
            $catalogPromotion->addRule($catalogPromotionRule);
        }

        $catalogPromotion->setDiscount($options['discount']);

        $catalogPromotion->setCreatedAt($options['created_at']);
        $catalogPromotion->setUpdatedAt($options['updated_at']);

        return $catalogPromotion;
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
