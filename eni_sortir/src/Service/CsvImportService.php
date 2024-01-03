<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class CsvImportService
{
    private EntityManagerInterface $entityManager;
    private CampusRepository $campusRepository;
    private ValidatorInterface $validator;
    private ParticipantRepository $participantRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        CampusRepository $campusRepository,
        ValidatorInterface $validator,
        ParticipantRepository $participantRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->campusRepository = $campusRepository;
        $this->validator = $validator;
        $this->participantRepository = $participantRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function importCsv(UploadedFile $csvFile): array
    {
        $importedUsers = 0;
        $handle = fopen($csvFile->getPathname(), 'r');
        $failedRows = [];
        $rowNumber = 1;

        if (!$handle) {
            throw new \Exception("Erreur lors de l'ouverture du fichier CSV.");
        }
        $participantsToPersist = [];
        // Récupérez toutes les adresses e-mail de la base de données | à changer (faire une requête pour chaque participant)
        $existingEmails = $this->participantRepository->findAllEmails();
        $emailSet = new \ArrayObject($existingEmails);
        // Chargez la liste des campus pour éviter un nombre de request trop important.
        $campusList = $this->campusRepository->findAll();
        // Lisez la première ligne pour ignorer l'en-tête
        fgetcsv($handle);

        // Parcourez les lignes restantes
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $campus = $this->getCampusByName($row[4], $campusList);

            if (!$campus) {
                $failedRows[] = ['rowNumber' => $rowNumber, 'rowData' => $row];
                continue;
            }

            if ($emailSet->offsetExists($row[2])) {
                $failedRows[] = ['rowNumber' => $rowNumber, 'rowData' => $row];
                continue;
            } else {
                $participant = new Participant();
                $participant->setPrenom($row[1])
                    ->setNom($row[0])
                    ->setEmail($row[2])
                    ->setTelephone(strval($row[3]))
                    ->setCampus($campus)
                    ->setActif(true)
                    ->setAdministrateur(false)
                    ->setMotDePasse($this->passwordHasher->hashPassword($participant,'eni123'))
                    ->setPseudo($participant->getEmail());

                // Validez le participant
                $errors = $this->validator->validate($participant);
                $emailSet->offsetSet($row[2], true);

                if (count($errors) > 0) {
                    $failedRows[] = ['rowNumber' => $rowNumber, 'rowData' => $row];
                    continue;
                }
                $importedUsers++;
                $participantsToPersist[] = $participant;
            }

        }
        foreach ($participantsToPersist as $participant) {
            $this->entityManager->persist($participant);
        }

        fclose($handle);
        $this->entityManager->flush();

        return [
            'importedUsers' => $importedUsers,
            'failedRows' => $failedRows,
        ];
    }

    private function getCampusByName($name, $campusList)
    {
        foreach ($campusList as $campus) {
            if ($campus->getNom() === strtoupper($name)) {
                return $campus;
            }
        }

        return null;
    }
}