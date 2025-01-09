<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Form\Type;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionRuleInterface;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

final class CatalogPromotionRuleType extends AbstractResourceType
{
    /**
     * @param array<array-key, string> $validationGroups
     */
    public function __construct(private readonly FormTypeRegistryInterface $formTypeRegistry, string $dataClass, array $validationGroups = [])
    {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', CatalogPromotionRuleChoiceType::class, [
                'label' => 'setono_sylius_catalog_promotion.form.catalog_promotion_rule.type',
                'attr' => [
                    'data-form-collection' => 'update',
                ],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $type = $this->getRegistryIdentifier($event->getForm(), $event->getData());
                if (null === $type) {
                    return;
                }

                $this->addConfigurationFields($event->getForm(), (string) $this->formTypeRegistry->get($type, 'default'));
            })
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
                $type = $this->getRegistryIdentifier($event->getForm(), $event->getData());
                if (null === $type) {
                    return;
                }

                $event->getForm()->get('type')->setData($type);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
                /** @var mixed $data */
                $data = $event->getData();
                Assert::isArray($data);

                if (!isset($data['type'])) {
                    return;
                }

                Assert::string($data['type']);

                $this->addConfigurationFields($event->getForm(), (string) $this->formTypeRegistry->get($data['type'], 'default'));
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('configuration_type', null)
            ->setAllowedTypes('configuration_type', ['string', 'null'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_catalog_promotion__catalog_promotion_rule';
    }

    protected function addConfigurationFields(FormInterface $form, string $configurationType): void
    {
        $form->add('configuration', $configurationType, [
            'label' => false,
        ]);
    }

    /**
     * @param mixed $data
     */
    protected function getRegistryIdentifier(FormInterface $form, $data = null): ?string
    {
        if ($data instanceof CatalogPromotionRuleInterface && null !== $data->getType()) {
            return $data->getType();
        }

        if ($form->getConfig()->hasOption('configuration_type')) {
            $res = $form->getConfig()->getOption('configuration_type');
            Assert::nullOrString($res);

            return $res;
        }

        return null;
    }
}
