<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $tabCampus = [
            'NANTES',
            'RENNES',
            'QUIMPER',
            'NIORT'
        ];

        for ($i = 0 ; $i < 4; $i++) {
            $campus = new Campus();
            $campus->setNom($tabCampus[$i]);
            $manager->persist($campus);
        }
        $manager->flush();
    }
}
