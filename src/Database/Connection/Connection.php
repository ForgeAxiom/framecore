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
    public PDO $pdo;

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
}