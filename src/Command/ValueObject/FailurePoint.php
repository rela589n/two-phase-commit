<?php

declare(strict_types=1);

namespace App\Command\ValueObject;

use loophp\collection\Collection;

enum FailurePoint: string
{
    case FIRST_TRANSACTION_FAILURE = 'first_transaction_failure';

    case SECOND_TRANSACTION_FAILURE = 'second_transaction_failure';

    case COMMIT_POINT_FAILURE = 'commit_point_failure';

    case COORDINATOR_COMMIT_FAILURE = 'coordinator_commit_failure';

    case COORDINATOR_ROLLBACK_FAILURE = 'coordinator_rollback_failure';

    public static function establish(Collection $failurePoints): void
    {
        $pints = $failurePoints
            ->flip()
            ->map(static fn (FailurePoint $failurePoint) => $failurePoint->value)
            ->flip()
            ->all(false);

        $GLOBALS['FAILURE_POINTS'] = $pints;
    }

    public function trap(): void
    {
        /** @var ?FailureType $failureType */
        $failureType = $GLOBALS['FAILURE_POINTS'][$this->value] ?? null;

        if (!isset($failureType)) {
            return;
        }

        $failureType->raise($this);
    }
}
