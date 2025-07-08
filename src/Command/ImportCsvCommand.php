<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CsvImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:import:csv',
    description: 'Importe un fichier CSV dans la base de données',
)]
class ImportCsvCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 100;

    public function __construct(
        private readonly CsvImporter $csvImporter,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier CSV à importer')
            ->addOption('delimiter', 'd', InputOption::VALUE_OPTIONAL, 'Délimiteur du fichier CSV', ';')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Taille des lots pour la sauvegarde en base', self::DEFAULT_BATCH_SIZE)
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Vider la table avant l\'importation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('import');
        $fileArgument = $input->getArgument('file');
        if (!is_string($fileArgument)) {
            $io->error('L\'argument `file` doit être une chaîne de caractères.');
            return Command::FAILURE;
        }
        $filePath = $this->projectDir . '/import/csv/' . $fileArgument;

        // Si le chemin est relatif, on le rend absolu par rapport au répertoire du projet
        $fullPath = !file_exists($filePath) ? $this->projectDir . '/' . ltrim($filePath, '/') : $filePath;

        if (!file_exists($fullPath)) {
            $io->error(sprintf('Le fichier "%s" n\'existe pas', $filePath));
            return Command::FAILURE;
        }

        // Vider la table si l'option --truncate est spécifiée
        if ($input->getOption('truncate')) {
            $io->note('Vidage de la table avant l\'importation...');
            $connection = $this->entityManager->getConnection();
            $platform = $connection->getDatabasePlatform();
            $connection->executeStatement($platform->getTruncateTableSQL('import_record', true));
            $io->success('Table vidée avec succès.');
        }

        $io->title(sprintf('Import du fichier : %s', $filePath));

        try {
            $this->csvImporter->import($fullPath);

            $io->success('Import terminé avec succès');

            $event = $stopwatch->stop('import');
            $io->comment(sprintf(
                'Temps d\'exécution : %.2f secondes, Mémoire utilisée : %.2f MB',
                $event->getDuration() / 1000,
                $event->getMemory() / 1024 / 1024
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf(
                'Erreur lors de l\'import : %s\n%s',
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            return Command::FAILURE;
        }
    }
}
