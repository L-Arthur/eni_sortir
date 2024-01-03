<?php

namespace App\Controller\Api;

use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiLieuxController extends AbstractController
{
    /**
     * @Route("/api/lieux/ville/{id}", name="api_lieux_ville", methods={"POST"})
     */

    public function listeLieux(int $id, LieuRepository $lieuRepository, SerializerInterface $serializer): JsonResponse
    {
        $lieux = $lieuRepository->findByVille($id);


        $lieuxNames = [];
        foreach ($lieux as $lieu) {
            $lieuxNames[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
                'villeId' => $lieu->getVille()->getId(),
                'codePostal' => $lieu->getVille()->getCodePostal(),
                'rue' => $lieu->getRue(),
                'latitude' => $lieu->getLatitude(),
                'longitude' => $lieu->getLongitude()
            ];
        }

        $data = $serializer->serialize($lieuxNames, 'json');

        return new JsonResponse($data, 200, [], true);
    }
}