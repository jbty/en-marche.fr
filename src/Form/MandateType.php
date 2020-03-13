<?php

namespace AppBundle\Form;

use AppBundle\Entity\ElectedRepresentative\LaREMSupportEnum;
use AppBundle\Entity\ElectedRepresentative\Mandate;
use AppBundle\Entity\ElectedRepresentative\MandateTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MandateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'placeholder' => '--',
                'choices' => MandateTypeEnum::CHOICES,
            ])
            ->add('politicalAffiliation', TextType::class)
            ->add('isElected', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('laREMSupport', ChoiceType::class, [
                'required' => false,
                'label' => 'Soutien LaREM',
                'placeholder' => '--',
                'choices' => LaREMSupportEnum::toArray(),
                'choice_label' => function ($choice, $key) {
                    return 'elected_representative.mandate.larem_support.'.\mb_strtolower($key);
                },
            ])
            ->add('onGoing', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('beginAt', 'sonata_type_date_picker')
            ->add('finishAt', 'sonata_type_date_picker', [
                'required' => false,
            ])
            ->add('geographicalArea', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Mandate::class,
        ]);
    }
}