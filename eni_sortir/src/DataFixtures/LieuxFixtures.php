<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LieuxFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $villes = $manager->getRepository(Ville::class)->findAll();

        for ($i = 0 ; $i < 25; $i++) {
            $lieu = new Lieu();
            $lieu->setNom('lieu'.$i);
            $lieu->setRue($faker->streetAddress());
            $lieu->setLatitude($faker->randomFloat(5, 46, 48));
            $lieu->setLongitude($faker->randomFloat(5, 0.4, 1.6));
            $lieu->setVille($faker->randomElement($villes));
            $manager->persist($lieu);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [VilleFixtures::class];
    }
}
