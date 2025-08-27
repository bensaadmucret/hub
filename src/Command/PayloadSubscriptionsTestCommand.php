<?php

namespace App\Command;

use App\Application\Subscription\SubscriptionProvisioner;
use App\Integration\Payload\PayloadClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:payload:subscriptions:test',
    description: 'Test CRUD operations against Payload subscriptions API via S2S',
)]
class PayloadSubscriptionsTestCommand extends Command
{
    public function __construct(
        private readonly SubscriptionProvisioner $provisioner,
        private readonly PayloadClient $client,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list|create|update|delete')
            ->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page for list', '1')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit for list', '10')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User ID for create')
            ->addOption('subscriptionId', null, InputOption::VALUE_REQUIRED, 'Subscription ID for create')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Status for create/update (trialing|active|past_due|canceled)')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Subscription numeric ID for update/delete')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $argAction = $input->getArgument('action');
        $action = is_string($argAction) ? strtolower($argAction) : '';

        try {
            return match ($action) {
                'list' => $this->doList(
                    $io,
                    $this->toIntOrDefault($input->getOption('page'), 1),
                    $this->toIntOrDefault($input->getOption('limit'), 10)
                ),
                'create' => $this->doCreate($io, $input),
                'update' => $this->doUpdate($io, $input),
                'delete' => $this->doDelete($io, $input),
                default => $this->invalid($io, 'Unknown action. Use list|create|update|delete'),
            };
        } catch (\Throwable $e) {
            $io->error(sprintf('Error: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function doList(SymfonyStyle $io, int $page, int $limit): int
    {
        $data = $this->provisioner->list($page, $limit);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $io->writeln($json !== false ? $json : '{}');
        return Command::SUCCESS;
    }

    private function doCreate(SymfonyStyle $io, InputInterface $input): int
    {
        $user = $input->getOption('user');
        $subId = $input->getOption('subscriptionId');
        $statusRaw = $input->getOption('status');
        $status = is_string($statusRaw) ? $statusRaw : 'active';

        if ($user === null || $subId === null) {
            return $this->invalid($io, 'Missing --user or --subscriptionId option');
        }

        $userId = $this->toStringOrInt($user);
        if ($userId === null) {
            return $this->invalid($io, 'Invalid --user value. Expecting int or string');
        }

        if (!is_string($subId)) {
            return $this->invalid($io, 'Invalid --subscriptionId value. Expecting string');
        }

        $data = $this->provisioner->provisionInitial($userId, $subId, $status);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $io->writeln($json !== false ? $json : '{}');
        return Command::SUCCESS;
    }

    private function doUpdate(SymfonyStyle $io, InputInterface $input): int
    {
        $id = $input->getOption('id');
        $statusRaw = $input->getOption('status');
        if ($id === null || $statusRaw === null) {
            return $this->invalid($io, 'Missing --id or --status option');
        }
        $data = $this->provisioner->list(); // warm up
        $data = $this->provisioner->list(); // no-op but harmless
        $data = $this->provisioner->list(); // keep simple
        $status = is_string($statusRaw) ? $statusRaw : 'active';
        if (!is_string($id) && !is_int($id)) {
            return $this->invalid($io, 'Invalid --id value. Expecting string or int');
        }
        $res = $this->client->updateSubscription($id, ['status' => $status]);
        $json = json_encode($res, JSON_PRETTY_PRINT);
        $io->writeln($json !== false ? $json : '{}');
        return Command::SUCCESS;
    }

    private function doDelete(SymfonyStyle $io, InputInterface $input): int
    {
        $id = $input->getOption('id');
        if ($id === null) {
            return $this->invalid($io, 'Missing --id option');
        }
        if (!is_string($id) && !is_int($id)) {
            return $this->invalid($io, 'Invalid --id value. Expecting string or int');
        }
        $res = $this->client->deleteSubscription($id);
        $json = json_encode($res, JSON_PRETTY_PRINT);
        $io->writeln($json !== false ? $json : '{}');
        return Command::SUCCESS;
    }

    private function invalid(SymfonyStyle $io, string $msg): int
    {
        $io->warning($msg);
        return Command::INVALID;
    }

    /**
     * @param mixed $value
     */
    private function toIntOrDefault(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        return $default;
    }

    /**
     * @param mixed $value
     * @return int|string|null
     */
    private function toStringOrInt(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $value;
        }
        return null;
    }
}
