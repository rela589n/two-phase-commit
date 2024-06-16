<?php

declare(strict_types=1);

namespace App\Service;

use Amp\Postgres\PostgresQueryError;
use Amp\Sql\SqlConnection;
use Amp\Sql\SqlConnectionException;
use Amp\Sql\SqlException;
use App\Command\ValueObject\FailurePoint;
use App\TransactionState\TransactionState;
use Closure;
use Throwable;

final class TransactionCoordinator
{
    public function __construct(
        private readonly TransactionState $transactionState,
        private readonly SqlConnection $firstConnection,
        private readonly SqlConnection $secondConnection,
    ) {
        $this->recoverFromStoredState();
    }

    public function transactional(string $transactionId, Closure $callback)
    {
        $this->transactionState->add($transactionId);

        try {
            $result = $callback();

            FailurePoint::COMMIT_POINT_FAILURE->trap();

            $this->transactionState->success($transactionId);

            return $result;
        } catch (Throwable $exception) {
            $this->transactionState->failure($transactionId);

            throw $exception;
        } finally {
            $this->resolveTransaction($transactionId);
        }
    }

    /** Recovery from WAL in case of coordinator failure */
    private function recoverFromStoredState(): void
    {
        array_map(
            $this->resolveTransaction(...),
            $this->transactionState->getAll(),
        );
    }

    private function resolveTransaction(string $transactionId): void
    {
        $isSuccessful = $this->transactionState->isSuccessful($transactionId);

        if ($isSuccessful) {
            $this->commit($transactionId);
        } else {
            $this->rollback($transactionId);
        }

        $this->transactionState->remove($transactionId);
    }

    private function commit(string $transactionId): void
    {
        try {
            $this->firstConnection->execute(sprintf('COMMIT PREPARED \'%s\'', $transactionId));
        } catch (PostgresQueryError $e) {
            // allow non-existent transaction (already committed)
            if ($e->getDiagnostics()['sqlstate'] !== '42704') {
                throw $e;
            }
        }

        FailurePoint::COORDINATOR_COMMIT_FAILURE->trap();

        try {
            $this->secondConnection->execute(sprintf('COMMIT PREPARED \'%s\'', $transactionId));
        } catch (PostgresQueryError $e) {
            // allow non-existent transaction (already committed)
            if ($e->getDiagnostics()['sqlstate'] !== '42704') {
                throw $e;
            }
        }
    }

    private function rollback(string $transactionId): void
    {
        try {
            $this->firstConnection->query(sprintf('ROLLBACK PREPARED \'%s\'', $transactionId));
        } catch (PostgresQueryError $e) {
            // allow non-existent transaction (already rolled back)
            if ($e->getDiagnostics()['sqlstate'] !== '42704') {
                throw $e;
            }
        }

        FailurePoint::COORDINATOR_ROLLBACK_FAILURE->trap();

        try {
            $this->secondConnection->query(sprintf('ROLLBACK PREPARED \'%s\'', $transactionId));
        } catch (PostgresQueryError $e) {
            // allow non-existent transaction (already rolled back)
            if ($e->getDiagnostics()['sqlstate'] !== '42704') {
                throw $e;
            }
        }
    }
}
