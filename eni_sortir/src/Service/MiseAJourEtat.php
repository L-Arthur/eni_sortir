<?php

namespace App\Service;

use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class MiseAJourEtat
{

    private EtatRepository $etatRepository;
    private SortieRepository $sortieRepository;


    public function __construct(EtatRepository $etatRepository, SortieRepository $sortieRepository, EntityManagerInterface $entityManager)
    {
        $this->etatRepository = $etatRepository;
        $this->sortieRepository = $sortieRepository;
        $this->entityManager = $entityManager;
    }

    // Ancien système de mise à jour. Pour le moment toujours utilisé le temps que le nouveau système soit fini et testé.
    public function miseAJour()
    {
        $date = date('Y-m-d H:i');
        $listeEtats = $this->recuperationEtat();
        $listeSortie = $this->sortieRepository->listeSortiesAvecEtat();
        $listeSortiesAMettreAJour = [];
        foreach ($listeSortie as $sortie) {
            $nombreInscrits = count($sortie->getInscrits());
            if ($sortie->getEtat()->getLibelle() !== 'Passée' && $sortie->getEtat()->getLibelle() !== 'Annulée' && $sortie->getEtat()->getLibelle() !== 'Créée') {
                if ($sortie->getEtat()->getLibelle() !== 'Ouverte' &&
                    strtotime($sortie->getDateLimiteInscription()->format('Y-m-d H:i')) > strtotime($date) &&
                    $nombreInscrits < $sortie->getNbInscriptionsMax()) {
                    $sortie->setEtat($listeEtats['Ouverte']);
                }
                if ($sortie->getEtat()->getLibelle() !== 'Clôturée' &&
                    strtotime($sortie->getDateLimiteInscription()->format('Y-m-d H:i')) < strtotime($date) ||
                    $nombreInscrits == $sortie->getNbInscriptionsMax()) {
                    $sortie->setEtat($listeEtats['Clôturée']);
                }
                if ($sortie->getEtat()->getLibelle() !== 'Activité en cours' &&
                    strtotime($sortie->getDateHeureDebut()->format('Y-m-d H:i')) < strtotime($date) &&
                    strtotime($sortie->getDateHeureDebut()->format('Y-m-d H:i') . '+' . $sortie->getDuree() . 'minute') > strtotime($date)) {
                    $sortie->setEtat($listeEtats['Activité en cours']);
                }
                if ($sortie->getEtat()->getLibelle() !== 'Passée' &&
                    strtotime($sortie->getDateHeureDebut()->format('Y-m-d H:i') . '+' . $sortie->getDuree() . 'minute') < strtotime($date)) {
                    $sortie->setEtat($listeEtats['Passée']);
                }
                $listeSortiesAMettreAJour[] = $sortie;
            }
        }
       $this->sortieRepository->miseAJourEtatSortie($listeSortiesAMettreAJour);
    }

    // Nouveau système de mise à jour des sorties. Pas terminer et non tester.
    public function miseAJour2 () {
        $date = date('Y-m-d H:i');
        $listeEtats = $this->recuperationEtat();
        $listeSortiesMAJ = [];

        $sortiesOuvertes = $this->sortieRepository->listeSortiesOuvertesAMettreAJour($date);
        foreach ($sortiesOuvertes as $sortie) {
            $sortie->setEtat($listeEtats['Ouverte']);
            $listeSortiesMAJ[] = $sortie;
        }
        $sortiesCloturees = $this->sortieRepository->listeSortiesCloturerAMettreAJour($date);
        foreach ($sortiesCloturees as $sortie) {
            $sortie->setEtat($listeEtats['Clôturée']);
            $listeSortiesMAJ[] = $sortie;
        }
        $sortiesEnCours = $this->sortieRepository->listeSortiesEnCoursAMettreAJour($date);
        foreach ($sortiesEnCours as $sortie) {
            $sortie->setEtat($listeEtats['Activité en cours']);
            $listeSortiesMAJ[] = $sortie;
        }
        $sortiesPassees = $this->sortieRepository->listeSortiesPasserAMettreAJour($date);
        foreach ($sortiesPassees as $sortie) {
            $sortie->setEtat($listeEtats['Passée']);
            $listeSortiesMAJ[] = $sortie;
        }

        foreach ($listeSortiesMAJ as $sortie) {
            $this->entityManager->persist($sortie);
        }
        $this->entityManager->flush();

    }

    public function recuperationEtat(): array
    {
        $listesEtatsAvecNom = [];
        $etats = $this->etatRepository->findAll();
        foreach ($etats as $etat) {
            if ($etat->getLibelle() === 'Créée') {
                $listesEtatsAvecNom['Créée'] = $etat;
            }
            if ($etat->getLibelle() === 'Ouverte') {
                $listesEtatsAvecNom['Ouverte'] = $etat;
            }
            if ($etat->getLibelle() === 'Clôturée') {
                $listesEtatsAvecNom['Clôturée'] = $etat;
            }
            if ($etat->getLibelle() === 'Activité en cours') {
                $listesEtatsAvecNom['Activité en cours'] = $etat;
            }
            if ($etat->getLibelle() === 'Passée') {
                $listesEtatsAvecNom['Passée'] = $etat;
            }
            if ($etat->getLibelle() === 'Annulée') {
                $listesEtatsAvecNom['Annulée'] = $etat;
            }
        }
        return $listesEtatsAvecNom;
    }

}