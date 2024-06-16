<?php

declare(strict_types=1);

namespace App\TransactionState;

use Amp\Sql\SqlConnection;

final class PersistentTransactionState implements TransactionState
{
    public function __construct(
        private readonly SqlConnection $connection,
    ) {
    }

    public function add(string $transactionId): void
    {
        $this->connection->execute(
            'INSERT INTO transaction_outcomes VALUES (?, NULL)',
            [$transactionId],
        );
    }

    public function failure(string $transactionId): void
    {
        $this->connection->execute(
            'UPDATE transaction_outcomes SET outcome = FALSE WHERE id = ?',
            [$transactionId],
        );
    }

    public function success(string $transactionId): void
    {
        $this->connection->execute(
            'UPDATE transaction_outcomes SET outcome = TRUE WHERE id = ?',
            [$transactionId],
        );
    }

    public function isSuccessful(string $transactionId): ?bool
    {
        $result = $this->connection->execute('SELECT outcome FROM transaction_outcomes WHERE id = ?', [$transactionId]);

        /** @var ?bool $outcome */
        $outcome = $result->fetchRow()['outcome'] ?? null;

        return $outcome;
    }

    public function remove(string $transactionId): void
    {
        $this->connection->execute(
            'DELETE FROM transaction_outcomes WHERE id = ?',
            [$transactionId],
        );
    }

    public function getAll(): array
    {
        $result = $this->connection->execute('SELECT id FROM transaction_outcomes');

        return array_column(iterator_to_array($result), 'id');
    }
}
