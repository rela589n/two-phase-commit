<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Command;

use Amp\Sql\SqlConnection;
use App\Command\ValueObject\FailurePoint;
use App\Command\ValueObject\FailureType;
use App\Service\TransactionCoordinator;
use loophp\collection\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:run-transaction',
    description: 'Runs distributed transaction',
)]
class RunTransactionCommand extends Command
{
    public function __construct(
        #[Autowire('@app.transaction_coordinator')]
        private readonly TransactionCoordinator $coordinator,
        #[Autowire('@app.db_connections.first')]
        private readonly SqlConnection $firstConnection,
        #[Autowire('@app.db_connections.second')]
        private readonly SqlConnection $secondConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addArgument('failurePoints', InputArgument::IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $failurePoints */
        $failurePoints = $input->getArgument('failurePoints');

        FailurePoint::establish($this->buildFailurePoints($failurePoints));

        $distributedTransactionId = Uuid::v7();

        $this->coordinator->transactional(
            $distributedTransactionId->toRfc4122(),
            fn () => $this->distributedTransaction($distributedTransactionId),
        );

        return Command::SUCCESS;
    }

    private function distributedTransaction(Uuid $distributedTransactionId): void
    {
        $this->firstTransaction($distributedTransactionId);
        $this->secondTransaction($distributedTransactionId);
    }

    private function firstTransaction(Uuid $distributedTransactionId): void
    {
        $this->firstConnection->query('BEGIN;');

        $this->firstConnection->execute('UPDATE test1 SET tld=? where domain=?', ['com', 'amphp']);

        $this->firstConnection->execute(sprintf('PREPARE TRANSACTION \'%s\'', $distributedTransactionId->toRfc4122()));

        FailurePoint::FIRST_TRANSACTION_FAILURE->trap();
    }

    private function secondTransaction(Uuid $distributedTransactionId): void
    {
        $this->secondConnection->query('BEGIN;');

        $this->secondConnection->execute('UPDATE test2 SET domain=? WHERE domain=?', ['amphp.com', 'amphp.org']);

        $this->secondConnection->execute(sprintf('PREPARE TRANSACTION \'%s\'', $distributedTransactionId->toRfc4122()));

        FailurePoint::SECOND_TRANSACTION_FAILURE->trap();
    }

    /**
     * @param string[] $failurePoints
     *
     * @return \loophp\collection\Contract\Collection<FailurePoint,FailureType>
     */
    private function buildFailurePoints(array $failurePoints): \loophp\collection\Contract\Collection
    {
        return Collection::fromIterable($failurePoints)
            ->map(static fn (string $failurePoint) => strtolower($failurePoint))
            ->map(static fn (string $failurePoint) => explode('=', $failurePoint))
            ->ifThenElse(
                static fn (array $failurePoint) => 1 === count($failurePoint),
                static fn (array $failurePoint) => [...$failurePoint, FailureType::EXCEPTION->value],
            )
            ->map(static fn (array $failurePoint) => [FailurePoint::from($failurePoint[0]), FailureType::from($failurePoint[1])])
            ->unpack();
    }
}
