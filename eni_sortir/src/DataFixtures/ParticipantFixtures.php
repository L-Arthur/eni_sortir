<?php

namespace App\DataFixtures;


use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $campus = $manager->getRepository(Campus::class)->findAll();
        // $product = new Product();
        // $manager->persist($product);
        for ($i = 1 ; $i <= 50; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName());
            $participant->setPrenom($faker->firstName());
            $participant->setPseudo($participant->getPrenom().$i);
            $participant->setAdministrateur(false);
            if ($i == 1) {
                $participant->setAdministrateur(true);
            }
            $participant->setEmail($faker->unique()->email());
            $participant->setTelephone($faker->phoneNumber());
            $participant->setMotDePasse($this->passwordHasher->hashPassword($participant, 'eni123'));
            $participant->setActif(true);
            $participant->setCampus($faker->randomElement($campus));
            $manager->persist($participant);
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [CampusFixtures::class];
    }
}
