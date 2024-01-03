<?php

namespace App\Form;



use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['sortieGardee']) {
            $lieu = $options['lieu'];
            $ville = $options['ville'];

            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom de la sortie : ',
                ])
                ->add('dateHeureDebut', DateTimeType::class, [
                    'html5' => true,
                    'widget' => 'single_text',
                    'label' => 'Date et heure de la sortie : '
                ])
                ->add('dateLimiteInscription', DateType::class, [
                    'html5' => true,
                    'widget' => 'single_text',
                    'label' => 'Date limite d\'insciption : '
                ])
                ->add('nbInscriptionsMax', IntegerType::class, [
                    'label' => 'Nombre de places : '
                ])
                ->add('duree', IntegerType::class, [
                    'label' => 'Durée : '
                ])
                ->add('infosSortie', TextareaType::class, [
                    'required' => false,
                    'label' => 'Description et infos : '
                ])
                ->add('campus', EntityType::class, [
                    'class' => Campus::class,
                    'choice_label' => 'nom',
                    'disabled' => true
                ])
                ->add('ville', EntityType::class, [
                    'class' => Ville::class,
                    'choice_label' => 'nom',
                    'mapped' => false,
                    'placeholder' => 'Sélectionner la ville',
                    'data' => $ville
                ]);
            if ($options['sortieNouvelle']) {
                $builder
                    ->add('lieu', ChoiceType::class, [
                        'placeholder' => 'Lieu (Choisir une ville)',
                        'required' => false
                    ]);
            }
            else {
                $builder
                    ->add('lieu', EntityType::class, [
                        'class' => Lieu::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Lieu (Choisir une ville)',
                        'required' => false
                    ]);
            }
            $builder
                ->add('rue', TextType::class, [
                    'disabled' => true,
                    'mapped' => false,
                    'data' => $lieu ? $lieu->getRue() : null,
                ])
                ->add('codePostal', TextType::class, [
                    'disabled' => true,
                    'mapped' => false,
                    'attr' => ['class' => 'cpVilleChoice'],
                    'data' => $lieu ? $lieu->getVille()->getCodePostal() : null
                ])
                ->add('latitude', TextType::class, [
                    'disabled' => true,
                    'mapped' => false,
                    'data' => $lieu ? $lieu->getLatitude() : null
                ])
                ->add('longitude', TextType::class, [
                    'disabled' => true,
                    'mapped' => false,
                    'data' => $lieu ? $lieu->getLongitude() : null
                ])
            ;

            $formModifier = function (FormInterface $form, ?Ville $ville = null) {
                $lieu = $ville === null ? [] : $ville->getLieux();

                $form
                    ->add('lieu', EntityType::class, [
                    'class' => Lieu::class,
                    'choices' => $lieu,
                    'choice_label' => 'nom',
                    'required' => false,
                    'attr' => ['class' => 'custom-select'],
                    ])

                ;
            };


            $builder->get('ville')->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $ville = $event->getForm()->getData();

                    $formModifier($event->getForm()->getParent(), $ville);
                }
            );

            $builder->get('lieu')->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    $lieu = $event->getForm()->getData();
                    $form = $event->getForm()->getParent();

                    $form->get('rue')->setData($lieu->getRue());
                    $form->get('latitude')->setData($lieu->getLatitude());
                    $form->get('longitude')->setData($lieu->getLongitude());
                }
            );
        }
        else
        {
            $builder
                ->add('infosSortie', TextareaType::class, [
                    'required' => true,
                    'label' => 'Motif : '
                ]);

        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
            'sortieNouvelle' => false,
            'sortieGardee' => true,
            'lieu' => null,
            'ville' => null
        ]);
    }
}
