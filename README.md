
```shell
composer install
```

```shell
docker compose up -d
```

```shell
bin/console app:prepare-db
```

```shell
bin/console app:run-transaction -vv 
```

See `TransactionCoordinator.php` as the main class responsible for distributed commit.

## Basic transaction scenario

```shell
bin/console app:run-transaction
```

## Error handling

> You could run `bin/console app:recover-coordinator` command to trigger recovery. 

### Basic roll-back

```shell
bin/console app:run-transaction FIRST_TRANSACTION_FAILURE
bin/console app:run-transaction SECOND_TRANSACTION_FAILURE
```

### Coordinator recovery (commit)

```shell
bin/console app:run-transaction COORDINATOR_COMMIT_FAILURE
```

In this case, when you run `app:recover-coordinator`, second db will commit as well.

### Coordinator recovery (roll-back)

```shell
# all participants replied yes, but coordinator failed to write decisions WAL
bin/console app:run-transaction COMMIT_POINT_FAILURE=fault
# one of transactions failed, but coordinator failed to send roll-back
bin/console app:run-transaction SECOND_TRANSACTION_FAILURE COORDINATOR_ROLLBACK_FAILURE=fault 
```
