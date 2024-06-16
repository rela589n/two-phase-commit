<?php

declare(strict_types=1);

namespace App\Service;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnection;

use function Amp\Postgres\connect;

final class ConnectionFactory
{
    public static function createFirstConnection(): PostgresConnection
    {
        $config = PostgresConfig::fromString("host=localhost port=5431 user=postgres db=postgres password=example");

        return connect($config);
    }

    public static function createSecondConnection(): PostgresConnection
    {
        $config = PostgresConfig::fromString("host=localhost port=5432 user=postgres db=postgres password=example");

        return connect($config);
    }

    public static function createCoordinatorConnection(): PostgresConnection
    {
        $config = PostgresConfig::fromString("host=localhost port=5433 user=postgres db=postgres password=example");

        return connect($config);
    }
}
