<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Connection;

use PDO;
use PDOException;

/**
 * Statement service, responsible for PDO database connection.
 */
final readonly class Connection
{
    private PDO $pdo;

    /**
     * @param string $pdoDbConnection PDO database connection string.
     */
    public function __construct(string $pdoDbConnection)
    {
        $this->pdo = new PDO($pdoDbConnection);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepares a statement for execution.
     *
     * Prepares an SQL statement for execution, returning a statement handle.
     *
     * @param string $sql The SQL statement to prepare.
     *
     * @return Statement Returns a PDOStatement object or throws an exception on failure.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared.
     */
    public function prepare(string $sql): Statement
    {
        return new Statement($this->pdo->prepare($sql));
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * NOTE: The return value and behavior of this method may vary
     * depending on the underlying database driver, especially for tables
     * without an auto-incrementing primary key.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned (driver-specific).
     *
     * @return string|false The ID of the last inserted row, or false if the driver does not support this capability.
     * @throws PDOException On database-level failure.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }
}