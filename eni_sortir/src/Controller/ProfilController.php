<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\AppAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilController extends AbstractController
{
    /**
     * @Route("/profil", name="app_profil")
     */
    public function modifierProfil(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger): Response

    {
        /** @var Participant $participant */
        $participant = $this->getUser();
        $profilForm = $this->createForm(ProfilType::class, $participant,
            array('nonAdministrateur' => true));

        $profilForm->handleRequest($request);

        if ($profilForm->isSubmitted() && $profilForm->isValid()){
            $image = $profilForm->get('image')->getData();
            if ($image) {
                $newFilename = uniqid().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Ajout de l\'image impossible');
                }
                $participant->setPhotoProfil($newFilename);
                $entityManager->persist($participant);
            }

            $participant->setMotDePasse(
                $userPasswordHasher->hashPassword(
                    $participant,
                    $profilForm->get('motDePasse')->getData()
                )
            );
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour !');
        }

        return $this->render('profil/modifier.html.twig', [
            'profilForm' => $profilForm->createView(),
        ]);
    }
    /**
     * @Route("/profil/{id}", name="app_profil_details")
     */
    public function afficherProfil ($id, ParticipantRepository $participantRepository): Response
    {
        $participant = $participantRepository->find($id);
        if (!$participant){
            throw $this->createNotFoundException('Cet utilisateur n\'existe pas');
        }
        return $this->render('profil/details.html.twig', ["p" => $participant]);
    }
}
