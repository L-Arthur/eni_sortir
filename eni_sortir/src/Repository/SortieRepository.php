<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use function PHPUnit\Framework\isNull;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function add(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function listeToutesLesSorties() {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->orderBy('s.dateHeureDebut', 'DESC');
        $queryBuilder->leftJoin('s.campus', 'c')->addSelect('c');
        $queryBuilder->leftJoin('s.lieu', 'l')->addSelect('l');
        $queryBuilder->leftJoin('s.etat', 'e')->addSelect('e');
        $queryBuilder->leftJoin('s.inscrits', 'i')->addSelect('i');
        $queryBuilder->leftJoin('s.organisateur', 'o')->addSelect('o');
        return $queryBuilder->getQuery()->getResult();
    }

    public function listeSortiesOuvertesAMettreAJour($date) {
        $queryBuilder = $this->createQueryBuilder('s')
            ->update()
            ->leftJoin('s.etat', 'etat')->addSelect('etat')
            ->andWhere('s.etat != :etatOuverte')
            ->setParameter('etatOuverte', 'Ouverte')
            ->andWhere('s.dateLimiteInscription > :date')
            ->setParameter('date', $date)
            ->andWhere('SIZE(s.inscrits) < s.nbInscriptionsMax');

        return $queryBuilder->getQuery()->getResult();
    }
    public function listeSortiesPasserAMettreAJour($date) {
        $queryBuilder = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'etat')->addSelect('etat')
            ->andWhere('etat.libelle != :etatPassee')
            ->andWhere('s.dateHeureDebut + s.duree * 60 < :date')
            ->setParameter('etatPassee', 'Passée')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }
    public function listeSortiesEnCoursAMettreAJour($date) {
        $queryBuilder = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'etat')->addSelect('etat')
            ->andWhere('etat.libelle != :etatEnCours')
            ->andWhere('s.dateHeureDebut < :date AND s.dateHeureDebut + s.duree * 60 > :date')
            ->setParameter('etatEnCours', 'Activité en cours')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }
    public function listeSortiesCloturerAMettreAJour($date) {
        $queryBuilder = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'etat')->addSelect('etat')
            ->andWhere('etat.libelle != :etatCloturee')
            ->andWhere('s.dateLimiteInscription < :date OR SIZE(s.inscrits) = s.nbInscriptionsMax')
            ->setParameter('etatCloturee', 'Clôturée')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }

    public function listeToutesLesSortiesMoinsUnMois(Participant $participant) {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->orderBy('s.dateHeureDebut', 'DESC');
        $queryBuilder->leftJoin('s.campus', 'c')->addSelect('c');
        $queryBuilder->leftJoin('s.lieu', 'l')->addSelect('l');
        $queryBuilder->leftJoin('s.etat', 'e')->addSelect('e');
        $queryBuilder->leftJoin('s.inscrits', 'i')->addSelect('i');
        $queryBuilder->leftJoin('s.organisateur', 'o')->addSelect('o');
        $queryBuilder->andWhere('s.dateHeureDebut >= :dateDebut')
            ->setParameter('dateDebut', new \DateTime('-31 days'));
        $queryBuilder->andWhere('c.nom = :campusParticipant')
            ->setParameter('campusParticipant', $participant->getCampus()->getNom());
        return $queryBuilder->getQuery()->getResult();
    }

    public function sortieDetail($id) {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->leftJoin('s.campus', 'c')->addSelect('c');
        $queryBuilder->leftJoin('s.lieu', 'l')->addSelect('l');
        $queryBuilder->leftJoin('l.ville', 'v')->addSelect('v');
        $queryBuilder->leftJoin('s.etat', 'e')->addSelect('e');
        $queryBuilder->leftJoin('s.inscrits', 'i')->addSelect('i');
        $queryBuilder->leftJoin('s.organisateur', 'o')->addSelect('o');
        $queryBuilder->andWhere('s.id = :idRecherche')
            ->setParameter('idRecherche', $id);
        $result = $queryBuilder->getQuery()->getOneOrNullResult();
        return $result;
    }
    public function listeSortiesAvecEtat() {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->leftJoin('s.inscrits', 'i')->addSelect('i');
        $queryBuilder->leftJoin('s.etat', 'e')->addSelect('e');
        $query = $queryBuilder->getQuery();
        return new Paginator($query);
        //return $queryBuilder->getQuery()->getResult();
    }

    public function miseAJourEtatSortie ($listeSorties) {
        foreach ($listeSorties as $sortie) {
            $this->getEntityManager()->persist($sortie);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère les sorties en lien avec une recherche
     */
    public function rechercheSortiesParCampus(SearchData $data, Participant $participant)
    {

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s', 'c');
        $queryBuilder->join('s.campus', 'c');
        $queryBuilder->orderBy('s.dateHeureDebut', 'DESC');
        $queryBuilder->leftJoin('s.lieu', 'l')->addSelect('l');
        $queryBuilder->leftJoin('s.etat', 'e')->addSelect('e');
        $queryBuilder->leftJoin('s.inscrits', 'i')->addSelect('i');
        $queryBuilder->leftJoin('s.organisateur', 'o')->addSelect('o');
        $queryBuilder->andWhere('s.dateHeureDebut >= :dateDebut')
            ->setParameter('dateDebut', new \DateTime('-31 days'));

            if ($data->q != '') {
                $queryBuilder->andWhere('s.nom LIKE :motClef')
                    ->setParameter('motClef', '%'.$data->q.'%');
            }

            $queryBuilder->andWhere('c.nom = :nomCampus')
                ->setParameter('nomCampus', $data->campuses->getNom());

            if ($data->criteres != []){
                if (in_array("inscrit", $data->criteres)&& !in_array("nonInscrit", $data->criteres)){
                    $queryBuilder->andWhere(':participant member Of s.inscrits')
                        ->setParameter('participant', $participant);
                }
                if (in_array("nonInscrit", $data->criteres)&& !in_array("inscrit", $data->criteres)){
                    $queryBuilder->andWhere(':participant not member Of s.inscrits')
                        ->setParameter('participant', $participant);
                }
                if (in_array("sortiesPassees", $data->criteres)){
                    $queryBuilder->andWhere('e.libelle = :sortiePassee')
                        ->setParameter('sortiePassee', 'Passée');
                }
                if (in_array("organisateur", $data->criteres)){
                    $queryBuilder->andWhere('s.organisateur = :orga')
                        ->setParameter('orga', $participant);
                }
            }
            if ($data->dateMin>$data->dateMax){
                $dateTemp = $data->dateMin;
                $data->dateMin=$data->dateMax;
                $data->dateMax=$dateTemp;
            }

                if ($data->dateMin !== null) {
                    $queryBuilder->andWhere('s.dateHeureDebut >= :dateDebutRecherche')
                        ->setParameter('dateDebutRecherche', $data->dateMin);
                }
                if ($data->dateMax !== null) {
                    $dateMax = $data->dateMax;
                    $dateMax->modify('+1 day');
                    $queryBuilder->andWhere('s.dateHeureDebut <= :dateFinRecherche')
                        ->setParameter('dateFinRecherche', $dateMax);
                }


        return $queryBuilder->getQuery()->getResult();
    }


    /*public function publierSortie(int $id,
                                 EntityManagerInterface $entityManager,
                                 SortieRepository $sortieRepository){

        $sortie = $sortieRepository->sortieDetail($id);
        if (!$sortie){
            $this->addFlash('error', 'Cette sortie n\'a pas été trouvée');
            return $this->redirectToRoute('app_main_accueil');
        }

        $sortie->setEtat($this->listeEtats['Ouverte']);

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'Sortie enregistrée et publiée.');
    }*/


        /**
        * @return Sortie[] Returns an array of Sortie objects
         */
        /*public function findByCampus($campus): array
        {
          return $this->createQueryBuilder('s')
                ->andWhere('s.campus = :nom')
                ->setParameter('nom', $campus)
                ->orderBy('s.nom', 'ASC')
             ->getQuery()
               ->getResult()
           ;
        }*/

            /*$queryBuilder = $this->createQueryBuilder('s')
                ->select('s','c')
                ->join('s.campus', 'c')
                ->where('s.c LIKE :q')
                ->setParameter('q', "%{$search->q}%");

                return $queryBuilder->getQuery()->getResult();*/





   /*    /*
    * @return Sortie[] Returns an array of Sortie objects
     */
    /*public function findByCampus($campus): array
    {
      return $this->createQueryBuilder('s')
            ->andWhere('s.campus = :nom')
            ->setParameter('nom', $campus)
            ->orderBy('s.nom', 'ASC')
         ->getQuery()
           ->getResult()
       ;
    }*/

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
