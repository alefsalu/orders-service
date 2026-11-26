<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Infrastructure\Outbox\OutboxRelayService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'orders:outbox:relay',
    description: 'Relays pending unpublished outbox messages to RabbitMQ exchange'
)]
class OutboxRelayCommand extends Command
{
    public function __construct(private readonly OutboxRelayService $relayService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_OPTIONAL,
            'Maximum number of outbox events to process in one run',
            100
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = (int) $input->getOption('batch-size');

        $published = $this->relayService->publishPendingMessages($batchSize);

        $io->success(sprintf('Published %d outbox messages to RabbitMQ.', $published));

        return Command::SUCCESS;
    }
}
