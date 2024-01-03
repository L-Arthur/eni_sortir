<?php

namespace App\Controller;

use App\Data\DataCampus;
use App\Entity\Campus;
use App\Form\CampusType;
use App\Form\DataCampusType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CampusController extends AbstractController
{
    /**
     * @Route("/admin/campus", name="app_campus")
     * @Route("/admin/campus/{id}/modifier", name="app_campus_modification")
     */
    public function affichage(CampusRepository $campusRepository, Request $request, EntityManagerInterface $entityManager, Campus $campus = null): Response
    {
        /* Affichage de la liste des campus */
        $campuses = $campusRepository->findBy([],['nom' => 'ASC']);

        $data = new DataCampus();
        $rechercheCampusForm = $this->createForm(DataCampusType::class, $data);
        $rechercheCampusForm->handleRequest($request);

        if ($rechercheCampusForm->isSubmitted() && $rechercheCampusForm->isValid()){
            if ($data->q!==''){
                $campuses = $campusRepository->rechercheCampus($data);
            }
        }
        if ($campuses==null){
            $this->addFlash('error', 'Pas de campus trouvé avec ces critères :(');
        }

        /*Ajouter/modifier un campus*/
        if(!$campus) {
            $campus = new Campus();
        }
        $campForm = $this->createForm(CampusType::class,$campus);

        $campForm->handleRequest($request);


        if ($campForm->isSubmitted() && $campForm->isValid()){
            if($campus->getId()) {
                $this->addFlash('success', 'Campus modifiée');
            }else{
                $this->addFlash('success', 'Campus ajoutée');
            }
            $entityManager->persist($campus);
            $entityManager->flush();

            return $this->redirectToRoute('app_campus');
        }

        return $this->render('admin/campus/campus.html.twig', [
            'editMode' => $campus->getId() !== null,
            'id' => $campus->getId(),
            'listCampus' => $campuses,
            'rechercheCampusForm' => $rechercheCampusForm->createView(),
            'createCampusForm' => $campForm->createView()
        ]);
    }

    /**
     *@Route("/admin/campus/{id}/supprimer", name="app_campus_supprimer")
     *
     */
    public function supprimerCampus(?Campus $campus, EntityManagerInterface $entityManager): Response
    {
        /*Supprimer un campus*/
        if (!$campus) {
            $this->addFlash('error', 'Ce campus n\'a pas été trouvée');
        } elseif (!($campus->getEtudiants()->isEmpty())) {
            $this->addFlash('error', 'Impossible de supprimer ce campus');
        } else {
            $entityManager->remove($campus);
            $entityManager->flush();
            $this->addFlash('success', 'Ce campus a été supprimée');

        }
        return $this->redirectToRoute('app_campus');
    }
}
