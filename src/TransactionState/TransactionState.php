<?php

declare(strict_types=1);

namespace App\TransactionState;

interface TransactionState
{
    public function add(string $transactionId): void;

    public function success(string $transactionId): void;

    public function failure(string $transactionId): void;

    public function isSuccessful(string $transactionId): ?bool;

    public function remove(string $transactionId): void;

    /** @return string[] */
    public function getAll(): array;
}
