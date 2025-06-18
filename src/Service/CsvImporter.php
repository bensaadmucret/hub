<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ImportRecord;
use App\Service\Dto\ImportRowDto;
use App\Exception\InvalidCsvRowException;
use App\Repository\ImportRecordRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service d'importation de fichiers CSV
 */
class CsvImporter
{
    public function __construct(
        private readonly ImportRecordRepository $importRecordRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Importe un fichier CSV dans la base de données
     *
     * @param string $filePath Chemin vers le fichier CSV
     *
     * @throws \RuntimeException Si le fichier n'existe pas, est illisible ou mal formé
     */
    public function import(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException(sprintf('Le fichier "%s" n\'existe pas ou est illisible', $filePath));
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Impossible d\'ouvrir le fichier "%s"', $filePath));
        }

        try {
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                throw new \RuntimeException('Le fichier CSV est vide ou mal formé');
            }

            $lineNumber = 1;

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $lineNumber++;
                $this->processRow($row, $lineNumber);
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * Traite une ligne du fichier CSV
     *
     * @param array<int, string|null> $row La ligne à traiter
     * @param int $lineNumber Le numéro de la ligne en cours de traitement
     * @throws InvalidCsvRowException Si la ligne n'est pas valide
     */
    private function processRow(array $row, int $lineNumber): void
    {
        // Créer un tableau associatif à partir des valeurs de la ligne
        // En supposant que les colonnes sont dans l'ordre: name, email, amount
        $name = $row[0] ?? '';
        $email = $row[1] ?? '';
        $amount = $row[2] ?? null;

        $rowData = [
            'name' => $name,
            'email' => $email,
            'amount' => $amount,
        ];

        $dto = new ImportRowDto($rowData);

        // Valider le DTO
        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = [(string)$violation->getMessage()];
            }

            throw new InvalidCsvRowException(
                sprintf('Erreur de validation à la ligne %d', $lineNumber),
                $errors
            );
        }

        $importRecord = new ImportRecord();
        $importRecord->setName((string)$dto->getName());
        $importRecord->setEmail((string)$dto->getEmail());

        if ($dto->getAmount() !== null) {
            $importRecord->setAmount((string)$dto->getAmount());
        }

        $this->importRecordRepository->save($importRecord);
    }
}
