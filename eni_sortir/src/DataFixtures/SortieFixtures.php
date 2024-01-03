<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $things_to_do = array(
            "Se promener dans le parc",
            "Visiter le musée local",
            "Assister à un concert",
            "Déguster des spécialités culinaires",
            "Faire du shopping dans les boutiques",
            "Aller voir un film au cinéma",
            "Visiter un monument historique",
            "Faire une balade en bateau",
            "Assister à une pièce de théâtre",
            "Faire du sport dans un parc",
            "Explorer les rues en vélo",
            "Faire une excursion en montagne",
            "Visiter une galerie d'art",
            "Aller à la plage",
            "Faire du tourisme en bus",
            "Participer à une visite guidée",
            "Faire du patin à glace",
            "Assister à un match de sport",
            "Visiter un parc d'attractions",
            "Faire une randonnée en nature",
            "Aller dans un spa",
            "Faire du karting",
            "Assister à un festival",
            "Visiter un zoo",
            "Faire du camping"
        );

        $faker = \Faker\Factory::create('fr_FR');
        $campus = $manager->getRepository(Campus::class)->findAll();
        $allParticipants = $manager->getRepository(Participant::class)->findAll();
        $uniqueParticipants = array_unique($allParticipants, SORT_REGULAR);
        $etat = $manager->getRepository(Etat::class)->findAll();
        $lieu = $manager->getRepository(Lieu::class)->findAll();

        for($i = 1 ; $i <= 50 ; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($things_to_do[rand(0,24)]);
            $sortie->setDateLimiteInscription($faker->dateTimeBetween("-3 months", "+1 months"));
            $sortie->setDateHeureDebut($faker->dateTimeBetween($sortie->getDateLimiteInscription(), "+1 months"));
            $sortie->setDuree(rand(15,120));
            $sortie->setInfosSortie($faker->realText());
            $sortie->setLieu($faker->randomElement($lieu));
            $sortie->setEtat($etat[4]);
            $sortie->setCampus($faker->randomElement($campus));
            $sortie->setOrganisateur($faker->randomElement($allParticipants));
            $nbParticipant = rand(5, 20);
            $sortie->setNbInscriptionsMax($nbParticipant);
            // Generate a random number of participants between 1 and $nbParticipant
            $nbInscrits = rand(1, $nbParticipant);

            // Select a random set of participants
            $randomParticipants = array_slice($uniqueParticipants, 0, $nbInscrits);

            // Add the random set of participants to the sortie
            foreach ($randomParticipants as $participant) {
                $sortie->addInscrit($participant);
            }
            $manager->persist($sortie);

        }
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [ParticipantFixtures::class, LieuxFixtures::class, CampusFixtures::class, EtatFixtures::class];
    }
}
