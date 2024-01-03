<?php

namespace App\Controller\Admin;

use App\Entity\Participant;
use App\Form\CsvImportType;
use App\Form\ProfilType;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Service\CsvImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{

    /**
     * @Route("/admin", name="app_admin")
     */
    public function ajoutUtilisateur(Request                     $request,
                                     UserPasswordHasherInterface $userPasswordHasher,
                                     EntityManagerInterface      $entityManager,
                                     ParticipantRepository       $participantRepository,
                                     CampusRepository            $campusRepository,
                                     CsvImportService            $csvImportService): Response
    {
        $participants = $participantRepository->findAllJointures();


        $listeCampus = $campusRepository->findAll();
        $participants = $participantRepository->findAll();
        $participant = new Participant();
        $participant->setActif(true);
        $participant->setAdministrateur(false);
        $participant->setMotDePasse('eni123');
        $motDePasse = $participant->getMotDePasse();
        $participant->setPseudo('UnPseudo');

        $profilForm = $this->createForm(ProfilType::class, $participant,
            array('nonAdministrateur' => false)
        );
        $profilForm->handleRequest($request);
        if ($profilForm->isSubmitted() && $profilForm->isValid()) {

            $participant->setMotDePasse(
                $userPasswordHasher->hashPassword($participant,
                    $motDePasse));
            $entityManager->persist($participant);
            $entityManager->flush();
            $participant->setPseudo($participant->getPrenom() . $participant->getId());
            $entityManager->flush();
            $this->addFlash('succes', 'Un nouvel utilisateur vient d\'être créé');
            return $this->redirectToRoute('app_admin');
        }
        // Créez le formulaire d'importation CSV
        $csvImportForm = $this->createForm(CsvImportType::class);
        $csvImportForm->handleRequest($request);

        if ($csvImportForm->isSubmitted() && $csvImportForm->isValid()) {
            $csvFile = $csvImportForm->get('csv_file')->getData();
            $result = $csvImportService->importCsv($csvFile);

            $importedUsers = $result['importedUsers'];
            $failedRows = $result['failedRows'];

            $this->addFlash('success', sprintf('%d utilisateurs ont été importés avec succès.', $importedUsers));
            if (!empty($failedRows)) {
                $errorMessage = "Les lignes suivantes n'ont pas pu être importées :\n";
                foreach ($failedRows as $failedRow) {
                    $errorMessage .= sprintf("Ligne %d : %s\n", $failedRow['rowNumber'], implode(',', $failedRow['rowData']));
                }
                $this->addFlash('error', $errorMessage);
            }
        }

        return $this->render('admin/index.html.twig', [
            'profilForm' => $profilForm->createView(),
            'csvImportForm' => $csvImportForm->createView(),
            'participants' => $participants,
        ]);
    }

    /**
     * @Route("/admin/desactiver/{id}", name="app_admin_desactiver")
     */

        public function desactiverUtilisateur(Participant            $participant,
                                          EntityManagerInterface $entityManager,
                                          ParticipantRepository  $participantRepository): Response
    {
        $participant->getId();

        if($participant->isActif()){

            $participant->setActif(false);
        }

        $entityManager->persist($participant);
        $entityManager->flush();
        $this->addFlash('succes', 'Cet utilisateur est maintenant inactif');

        return $this->redirectToRoute('app_admin');

    }

    /**
     * @Route("/admin/supprimer/{id}", name="app_admin_supprimer")
     */
    public function supprimerUtilisateur(Participant            $participant,
                                         EntityManagerInterface $entityManager,
                                         ParticipantRepository  $participantRepository): Response
    {
        $id = $participant->getId();
        $participant = $participantRepository->find($id);

        if (!empty($participant->getSortiesInscrits()) && !empty($participant->getSortiesOrganisees()))
        {
            $entityManager->remove($participant);
            $entityManager->flush();
            $this->addFlash('succes', 'Cet utilisateur est supprimé');
        } else {
            $this->addFlash('error', 'Cet utilisateur ne peut être supprimé car il est rattaché à une ou plusieurs sorties');
        }

        return $this->redirectToRoute('app_admin');

    }
}
