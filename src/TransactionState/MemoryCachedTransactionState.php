<?php

declare(strict_types=1);

namespace App\TransactionState;

final class MemoryCachedTransactionState implements TransactionState
{
    /** @var array<string,?bool> */
    private array $cachedState = [];

    public function __construct(
        private readonly TransactionState $state,
    ) {
    }

    public function add(string $transactionId): void
    {
        $this->state->add($transactionId);

        $this->cachedState[$transactionId] = null;
    }

    public function success(string $transactionId): void
    {
        $this->state->success($transactionId);

        $this->cachedState[$transactionId] = true;
    }

    public function failure(string $transactionId): void
    {
        $this->state->failure($transactionId);

        $this->cachedState[$transactionId] = false;
    }

    public function isSuccessful(string $transactionId): ?bool
    {
        if (array_key_exists($transactionId, $this->cachedState)) {
            return $this->cachedState[$transactionId];
        }

        return $this->cachedState[$transactionId] = $this->state->isSuccessful($transactionId);
    }

    public function remove(string $transactionId): void
    {
        $this->state->remove($transactionId);

        unset($this->cachedState[$transactionId]);
    }

    public function getAll(): array
    {
        return $this->state->getAll();
    }
}
