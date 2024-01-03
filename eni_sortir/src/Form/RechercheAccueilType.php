<?php

namespace App\Form;

use App\Data\SearchData;
use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RechercheAccueilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('q', TextType::class, [
                'label' => 'Le nom de la sortie contient : ',
                'required' => false,
                'attr' => [
                    'placeholder' => 'rechercher'
                ]
            ])
            ->add('campuses', EntityType::class, [
                'label' => 'Campus ',
                'class' => Campus::class,
                'choice_label' => 'nom',

            ])
            ->add('dateMin', DateType::class, [
                'label' => 'Entre : ',
                'required' => false,
                'html5' => true,
                'widget' => 'single_text',
            ])
            ->add('dateMax', DateType::class, [
                'label' => ' et ',
                'required' => false,
                'html5' => true,
                'widget' => 'single_text',
            ])
            ->add('criteres', ChoiceType::class, [
                'label' => false,
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => [
                    ' Sorties auxquelles je suis inscrit/e '=>'inscrit',
                    ' Sorties auxquelles je ne suis pas inscrit/e '=>'nonInscrit',
                    ' Sorties passées' => 'sortiesPassees',
                    ' Sorties dont je suis l\'organisateur/trice ' => 'organisateur'
                ]
            ])











            /*->add('nom', TextType::class, [
                'required' => false,
            ])
            ->add('dateHeureDebut', DateType::class, [
                'html5' => true,
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                //'multiple' => true,
            ])

            ->add('inscrits', ChoiceType::class, [
                'label' => false,
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => [
                    'Sorties auxquelles je suis inscrit/e'=>'i',
                    'Sorties auxquelles je ne suis pas inscrit/e'=>'ni',
                    'Sorties passées' => 'sp',
                    'Sorties dont je suis l\'organisateur/trice' => 'o'
                ]
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchData::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
    public function getBlockPrefix(): string
    {
        return '';
    }
}
