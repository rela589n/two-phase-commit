<?php

namespace App\Command;

use App\Service\TransactionCoordinator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:recover-coordinator',
    description: 'Add a short description for your command',
)]
class RecoverCoordinatorCommand extends Command
{
    public function __construct(
        #[Autowire('@app.transaction_coordinator')]
        private TransactionCoordinator $coordinator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Coordinator recovery starts every time coordinator is instantiated');

        return Command::SUCCESS;
    }
}
