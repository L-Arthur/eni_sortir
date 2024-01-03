<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use App\Service\MiseAJourEtat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    private $listeEtats = [];

    public function __construct(MiseAJourEtat $miseAJourEtat)
    {
        $this->listeEtats = $miseAJourEtat->recuperationEtat();
    }

    /**
     * @Route("/sortie/nouvelle", name="app_sortie_create")
     * @Route("/sortie/{id}/modifier", name="sortie_modification")
     */
    public function create(
        Sortie $sortie = null,
        Request $request,
        EntityManagerInterface $entityManager,
        VilleRepository $villeRepository
    ): Response {

        if (!$sortie) {
            //Création de l'instance de sortie
            $sortie = new Sortie();
            //Ajouter l'état en sortie Créée
            $sortie->setEtat($this->listeEtats['Créée']);

            //Récupération de l'utilisateur
            $currentUser = $this->getUser();
            $sortie->setOrganisateur($currentUser);

            //Récupération du campus lié à l'utilisateur
            $campus = $currentUser->getCampus();
            $sortie->setCampus($campus);
            $sortie->setDateHeureDebut(new \DateTime());
            //création du formulaire
            $sortieForm = $this->createForm(SortieType::class, $sortie, array(
                'sortieGardee'=>true,
                'sortieNouvelle'=>true
            ));
        }
        else {
            $lieu = $sortie->getLieu();
            $villeId = $lieu->getVille()->getId();

            $ville = $villeRepository->findOneBy(['id' => $villeId]);

            $etat = $sortie->getEtat();
            $organisateur = $sortie->getOrganisateur();

            //Si utilisateur n'est pas l'organisateur ou si l'état n'est plus en statut Créée Impossible de la modifier
            if ($organisateur !== $this->getUser() || $etat !== $this->listeEtats['Créée'])
            {
                $this->addFlash('error', 'Vous ne pouvez pas modifier cette sortie');
                return $this->redirectToRoute('app_sortie_detail', ['id'=> $sortie->getId()]);
            }
            else {

                //Si une sortie existe créer un formulaire avec la valeur sortieAModifier à true
                $sortieForm = $this->createForm(SortieType::class, $sortie, [
                    'lieu' => $lieu,
                    'sortieGardee' => true,
                    'sortieNouvelle' => false,
                    'ville' => $ville
                ]);
            }

        }

        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()){

            if (isset($_POST['enregistrer'])){
                $entityManager->persist($sortie);
                $entityManager->flush();

                $this->addFlash('success', 'Sortie enregistrée.');
                return $this->redirectToRoute('sortie_modification', ['id' => $sortie->getId()]);
            } elseif (isset($_POST['publier'])) {
                $sortie->setEtat($this->listeEtats['Ouverte']);

                $entityManager->persist($sortie);
                $entityManager->flush();

                $this->addFlash('success', 'Sortie enregistrée et publiée.');
                return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
            } else {

                $entityManager->persist($sortie);
                $entityManager->flush();

                return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
            }
        }

        return $this->render("sortie/create.html.twig", [
            "sortieForm"=>$sortieForm->createView(),
            'editMode' => $sortie->getId() !== null,
            'id' => $sortie->getId()
        ]);
    }

    /**
     * @Route("/sortie/{id}", name="app_sortie_detail")
     */
    public function detailSortie($id, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->sortieDetail($id);
        if (!$sortie){
            $this->addFlash('error', 'Cette sortie n\'a pas été trouvée');
            return $this->redirectToRoute('app_main_accueil');
        }
        return $this->render('sortie/detail.html.twig', [
            "sortie" => $sortie,
        ]);
    }

    /**
     * @Route("/sortie/{id}/supprimer", name="app_sortie_supprimer")
     */
    public function supprimerSortie(?Sortie $sortie,
                                    EntityManagerInterface $entityManager): Response
    {

        //$sortie = $sortieRepository->findOneBy(['id' => $id]); //A supprimer si Sortie $sortie ok
        if (!$sortie){
            $this->addFlash('error', 'Cette sortie n\'a pas été trouvée');
            return $this->redirectToRoute('app_main_accueil');
        }

        $etat = $sortie->getEtat();
        $organisateur = $sortie->getOrganisateur();


        //Si utilisateur n'est pas l'organisateur ou si l'état n'est plus en statut Créée Impossible de la supprimer
        if ($organisateur !== $this->getUser() || $etat !== $this->listeEtats['Créée'])
        {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette sortie');
            return $this->redirectToRoute('app_sortie_detail', ['id'=> $sortie->getId()]);
        }
        else {
            $entityManager->remove($sortie);
            $entityManager->flush();
            $this->addFlash('success', 'Cette sortie a été supprimée');

            return $this->redirectToRoute('app_main_accueil');
        }
    }

    /**
     * @Route("/sortie/{id}/annuler", name="app_sortie_annulersortie")
     */
    public function annulerSortie(int $id,
                                  Request $request,
                                  SortieRepository $sortieRepository,
                                  EntityManagerInterface $entityManager,
                                  VilleRepository $villeRepository): Response
    {
        $sortie = $sortieRepository->findOneBy(['id' => $id]);
        //si la sortie n'existe pas retour sur la page d'acceuil
        if (!$sortie){
            $this->addFlash('error', 'Cette sortie n\'a pas été trouvée');
            return $this->redirectToRoute('app_main_accueil');
        }

        //récupération de l'état et de l'organisateur pour vérifier les conditions de changement
        $etat = $sortie->getEtat();
        $organisateur = $sortie->getOrganisateur();

        //Si utilisateur n'est pas l'organisateur ou si l'état n'est plus en statut Ouverte ou Clôturée Impossible de l'annuler
        if (($organisateur === $this->getUser() || $this->getUser()->isAdministrateur())
            && ($etat === $this->listeEtats['Ouverte'] || $etat === $this->listeEtats['Clôturée']))
        {
            //Récupération d
            $lieu = $sortie->getLieu();
            $ville = $lieu->getVille();
            //effacement de la description pour affichage l'input du motif de l'annulation
            $sortie -> setInfosSortie("");

            //$ville = $villeRepository->findOneBy(['id' => $villeId]);

            //Création du formulaire pour modifier la description qui recevra le modif de l'annulation
            $sortieForm = $this->createForm(SortieType::class, $sortie, [
                'lieu' => $lieu,
                'sortieGardee' => false,
                'ville' => $ville
            ]);

            $sortieForm->handleRequest($request);


            if ($sortieForm->isSubmitted()){
                //Modification de l'état de la sortie
                $sortie -> setEtat($this->listeEtats['Annulée']);


                $entityManager->persist($sortie);
                $entityManager->flush();
                $this->addFlash('success', 'Cette sortie a été annulée');

                return $this->redirectToRoute('app_main_accueil');

            }

            return $this->render("sortie/annuler.html.twig", [
                "sortieForm"=>$sortieForm->createView(),
                'editMode' => $sortie->getId() !== null,
                'sortie' => $sortie
            ]);


        }
        else {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette sortie');
            return $this->redirectToRoute('app_sortie_detail', ['id'=> $sortie->getId()]);
        }
    }

    /**
     * @Route("/sortie/sinscrire/{id}", name="app_sortie_sinscrire")
     */
    public function sinscrire(?Sortie $sortie,
                              EntityManagerInterface $em): Response
    {
        //condition si $sortie null
        $nbMaxParticipant = $sortie->getNbInscriptionsMax();
        /** @var Participant $participant */
        $participant = $this->getUser();
        $NbInscrits = $sortie->getInscrits()->count();

        if ($sortie->getDateLimiteInscription() < (new \DateTime())
            || $sortie->getEtat()->getLibelle() != "Ouverte"
            || $NbInscrits >= $nbMaxParticipant
            || $sortie->getInscrits()->contains($participant)) {
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie');
            //return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        } else {

            $sortie->addInscrit($participant);
            if (count($sortie->getInscrits()) == $nbMaxParticipant) {
                $sortie->setEtat($this->listeEtats['Clôturée']);
            }
            $em->flush();
            $this->addFlash('success', 'Vous êtes inscrit à la sortie');
        }
        return $this->redirectToRoute('app_main_accueil');
    }

    /**
     * @Route("/sortie/desinscrire/{id}", name="app_sortie_sedesinscrire")
     */
    public function seDesinscrire(Sortie                 $sortie,
                                  EntityManagerInterface $em): Response
    {
        $NbMaxParticipant = $sortie->getNbInscriptionsMax();
        /** @var Participant $participant */
        $participant = $this->getUser();
        $NbInscrits = $sortie->getInscrits()->count();
        var_dump('test1');
        if ($sortie->getDateHeureDebut() > (new \DateTime())
            && $sortie->getInscrits()->contains($participant)) {

            $sortie->removeInscrit($participant);
            if ($sortie->getDateLimiteInscription() > (new \DateTime())) {
                $sortie->setEtat($this->listeEtats['Ouverte']);
            }
            $em->flush();
            $this->addFlash('success', 'Vous êtes désinscrit de la sortie');

        } else {
            $this->addFlash('error', 'Vous ne pouvez pas vous désinscrire de cette sortie');
            //return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }
        return $this->redirectToRoute('app_main_accueil');
    }
}