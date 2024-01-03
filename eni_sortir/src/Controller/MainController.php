<?php

namespace App\Controller;

use App\Data\SearchData;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\RechercheAccueilType;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Service\MiseAJourEtat;
use App\Utils\UpdateEtat;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;


class MainController extends AbstractController
{


    /**
     * @Route("/", name="app_main_accueil")
     */
    public function accueil(SortieRepository $sortieRepository, MiseAJourEtat $miseAJourEtat, Request $request): Response
    {
        /** @var Participant $participant */
        $participant = $this->getUser();
        $miseAJourEtat->miseAJour();
        $sorties = $sortieRepository->listeToutesLesSortiesMoinsUnMois($participant);

        $data = new SearchData();
        $data->setCampuses($participant->getCampus());
        $rechercheForm = $this->createForm(RechercheAccueilType::class, $data);
        $rechercheForm->handleRequest($request);
        if ($rechercheForm->isSubmitted() && $rechercheForm->isValid()){
            $data->criteres = $rechercheForm->get('criteres')->getData();
            $sorties = $sortieRepository->rechercheSortiesParCampus($data, $participant);
        }
        if ($sorties==null){
            $this->addFlash('error', 'Pas de sorties trouvées avec ces critères :(');
        }


        return $this->render('main/accueil.html.twig', [
            'listeSorties' => $sorties,
             'rechercheForm' => $rechercheForm->createView()
        ]);
    }
}
