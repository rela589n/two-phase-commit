<?php

namespace App\Command;

use Amp\Postgres\PostgresConnection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:prepare-db',
    description: 'Add a short description for your command',
)]
class PrepareDbCommand extends Command
{
    public function __construct(
        #[Autowire('@app.db_connections.first')]
        private readonly PostgresConnection $firstConnection,
        #[Autowire('@app.db_connections.second')]
        private readonly PostgresConnection $secondConnection,
        #[Autowire('@app.db_connections.coordinator')]
        private readonly PostgresConnection $coordinatorConnection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->prepareFirstDB();
        $this->prepareSecondDB();
        $this->prepareCoordinatorDB();

        return Command::SUCCESS;
    }

    private function prepareFirstDB(): void
    {
        $this->firstConnection->query('DROP TABLE IF EXISTS test1');

        $transaction = $this->firstConnection->beginTransaction();

        $transaction->query('CREATE TABLE test1 (domain VARCHAR(63), tld VARCHAR(63), PRIMARY KEY (domain, tld))');

        $statement = $transaction->prepare('INSERT INTO test1 VALUES (?, ?)');

        $statement->execute(['amphp', 'org']);
        $statement->execute(['google', 'com']);
        $statement->execute(['github', 'com']);

        $transaction->commit();
        $this->firstConnection->close();
    }

    private function prepareSecondDB(): void
    {
        $this->secondConnection->query('DROP TABLE IF EXISTS test2');

        $transaction = $this->secondConnection->beginTransaction();

        $transaction->query('CREATE TABLE test2 (domain VARCHAR(63), PRIMARY KEY (domain))');

        $statement = $transaction->prepare('INSERT INTO test2 VALUES (?)');
        $statement->execute(['amphp.org']);
        $statement->execute(['google.com']);
        $statement->execute(['github.com']);

        $transaction->commit();
        $this->secondConnection->close();
    }

    private function prepareCoordinatorDB(): void
    {
        $this->coordinatorConnection->query('DROP TABLE IF EXISTS transaction_outcomes');
        $this->coordinatorConnection->query('CREATE TABLE transaction_outcomes (id VARCHAR(63), outcome BOOLEAN, PRIMARY KEY (id))');
    }
}
