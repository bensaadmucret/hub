<?php

namespace App\Command;

use App\Repository\PaddleWebhookEventRepository;
use App\Webhook\PaddleWebhookRetryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour retraiter les événements webhook Paddle échoués
 * À exécuter périodiquement via un cron
 */
#[AsCommand(
    name: 'app:paddle:process-webhook-retries',
    description: 'Traite les événements webhook Paddle programmés pour retry',
)]
class ProcessPaddleWebhookRetryCommand extends Command
{
    public function __construct(
        private readonly PaddleWebhookRetryService $retryService,
        private readonly PaddleWebhookEventRepository $webhookEventRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('failed', 'f', InputOption::VALUE_NONE, 'Retraiter également les événements en échec définitif')
            ->addOption('max-retries', null, InputOption::VALUE_REQUIRED, 'Nombre maximum de tentatives', 5)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre maximum d\'événements à traiter', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $processFailedEvents = $input->getOption('failed');
        $maxRetries = (int) $input->getOption('max-retries');
        $limit = (int) $input->getOption('limit');

        // 1. Traiter les événements programmés pour retry
        $processedCount = $this->retryService->processScheduledRetries();

        $io->success(sprintf('Traitement de %d événements programmés pour retry terminé', $processedCount));

        // 2. Optionnellement, retraiter les événements en échec définitif
        if ($processFailedEvents) {
            $failedEvents = $this->webhookEventRepository->findRetryableFailedEvents($maxRetries);
            $failedCount = 0;

            foreach ($failedEvents as $event) {
                if ($failedCount >= $limit) {
                    break;
                }

                // Programmer l'événement pour un nouveau retry
                $event->scheduleRetry(60); // 1 minute
                $failedCount++;
            }

            if ($failedCount > 0) {
                $io->success(sprintf('%d événements en échec ont été reprogrammés pour retry', $failedCount));
            } else {
                $io->info('Aucun événement en échec à reprogrammer');
            }
        }

        // 3. Afficher les statistiques
        $stats = $this->webhookEventRepository->getWebhookStats();
        $io->section('Statistiques des événements webhook');

        $rows = [];
        foreach ($stats as $stat) {
            $rows[] = [$stat['status'], $stat['count']];
        }

        $io->table(['Status', 'Count'], $rows);

        return Command::SUCCESS;
    }
}
