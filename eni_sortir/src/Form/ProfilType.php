<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email : ',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom : '
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom : '
            ])
            ;

        if ($options['nonAdministrateur']) {
          $builder
              ->add('pseudo', TextType::class, [
                  'label' => 'Pseudo : '
              ])
              ->add('motDePasse', RepeatedType::class, [
                  'type' => PasswordType::class,
                  'first_options' => ['label' => 'Mot de passe : '],
                  'second_options' => ['label' => 'Confirmation : '],
              ])
                ->add('nom_campus', TextType::class, [
                    'label' => 'Campus : ',
                    'disabled' => true,
                    'mapped' => false,
                   'attr' => ['value' => $options['data']->getCampus()->getNom()]
                ])
              ->add('telephone', TelType::class, [
                  'label' => 'Téléphone : '
              ])
                ->add('image', FileType::class, [
                'label' => 'Photo Profil :',
                'multiple' => false,
                'required' => false,
                'mapped' => false
                ]);
        }
        else {
            $builder
                ->add('campus', EntityType::class, [
                    'class' => Campus::class,
                    'label' => 'Campus :',
                    'disabled' => false,
                    'choice_label' => 'nom',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'nonAdministrateur' => false,
        ]);
    }
}
