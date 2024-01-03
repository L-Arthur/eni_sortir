<?php

namespace App\Controller;

use App\Data\DataVille;
use App\Entity\Ville;
use App\Form\CreateVilleType;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VilleController extends AbstractController
{
    /**
     * @Route("/admin/ville", name="app_ville")
     * @Route("/admin/ville/{id}/modifier", name="app_ville_modification")
     */
    public function affichage(VilleRepository $villeRepository, Request $request, EntityManagerInterface $entityManager, Ville $ville =null): Response
    {

        /* Affichage de la liste de villes */
        $villes = $villeRepository->findBy([],['nom' => 'ASC']);

        $data = new DataVille();
        $rechercheVilleForm = $this->createForm(VilleType::class, $data);
        $rechercheVilleForm->handleRequest($request);

        if ($rechercheVilleForm->isSubmitted() && $rechercheVilleForm->isValid()){
            if ($data->q!==''){
                $villes = $villeRepository->rechercheVilles($data);
            }
        }
        if ($villes==null){
            $this->addFlash('error', 'Pas de villes trouvées avec ces critères :(');
        }

        /*Ajouter/modifier une ville*/
        if(!$ville) {
            $ville = new Ville();
        }
        $villeForm = $this->createForm(CreateVilleType::class,$ville);

        $villeForm->handleRequest($request);


        if ($villeForm->isSubmitted() && $villeForm->isValid()){
            if($ville->getId()) {
                $this->addFlash('success', 'Ville modifiée');
            }else{
                $this->addFlash('success', 'Ville ajoutée');
            }
            $entityManager->persist($ville);
            $entityManager->flush();

            return $this->redirectToRoute('app_ville');
        }

        return $this->render('admin/ville/villes.html.twig', [
            'editMode' => $ville->getId() !== null,
            'id' => $ville->getId(),
            'listVilles' => $villes,
            'rechercheVilleForm' => $rechercheVilleForm->createView(),
             'createVilleForm' => $villeForm->createView()
        ]);
    }


    /**
     *@Route("/admin/ville/{id}/supprimer", name="app_ville_supprimer")
     *
     */
    public function supprimerVille(?Ville $ville, EntityManagerInterface $entityManager): Response
    {
        /*Supprimer une ville*/
        if (!$ville) {
            $this->addFlash('error', 'Cette ville n\'a pas été trouvée');
        } elseif (!($ville->getLieux()->isEmpty())) {
            $this->addFlash('error', 'Impossible de supprimer cette ville');
        } else {
            $entityManager->remove($ville);
            $entityManager->flush();
            $this->addFlash('success', 'Cette ville a été supprimée');

        }
        return $this->redirectToRoute('app_ville');
    }
}
