<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $nomsVilles = [
            'Nantes',
            'Rennes',
            'Niort',
            'Quimper'
        ];

        $codePostals = [
            '44000',
            '35000',
            '79000',
            '29000',
        ];

        for ($i = 0 ; $i < 4; $i++) {
            $ville = new Ville();
            $ville->setNom($nomsVilles[$i]);
            $ville->setCodePostal($codePostals[$i]);
            $manager->persist($ville);
        }
            // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
