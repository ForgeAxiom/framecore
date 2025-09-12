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
     * Returns a last inserted id.
     *
     * @return string|false If a sequence name was not specified for the name parameter, $connection->lastInsertId returns a string representing the row ID of the last row that was inserted into the database.
     * If a sequence name was specified for the name parameter, $connection->lastInsertId returns a string representing the last value retrieved from the specified sequence object.
     * If the PDO driver does not support this capability, $connection->lastInsertId triggers an IM001 SQLSTATE.
     *
     * @throws PDOException If the PDO driver does not support this capability, $connection->lastInsertId triggers an IM001 SQLSTATE.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }
}