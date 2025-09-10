<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Connection;

use PDO;
use PDOException;
use PDOStatement;

final readonly class Statement
{
    public function __construct(
        private PDOStatement $statement
    ){}
    
    /**
     * Executes prepared statement.
     * 
     * @param array $args Arguments for passing to prepared placeholders.
     *
     * @return bool Returns true on success.
     * @throws PDOException On database-level failure.
     */
    public function execute(array $args = []): bool
    {
        return $this->statement->execute($args);
    }

    /**
     * Fetches the next row from a result set.
     * 
     * @param FetchAttributeEnum $pdoFetchResponse Controls how the next row will be returned to the caller. This value must be one of the AS_*
     *
     * @return array|false The return value of this function on success depends on the fetch type. Or false if no more rows
     * @throws PDOException On database-level failure.
     */
    public function fetch(FetchAttributeEnum $pdoFetchResponse = FetchAttributeEnum::AS_ASSOC): array | false
    {
        $pdoFetchAttribute = $this->getPdoFetchAttribute($pdoFetchResponse);
        return $this->statement->fetch($pdoFetchAttribute);
    }

    /**
     * Fetches all remaining rows from a result set.
     * 
     * @param FetchAttributeEnum $pdoFetchResponse Controls how the next row will be returned to the caller. This value must be one of the AS_*
     *
     * @return array The return value of this function on success depends on the fetch type.
     * @throws PDOException On database-level failure.
     */
    public function fetchAll(FetchAttributeEnum $pdoFetchResponse = FetchAttributeEnum::AS_ASSOC): array
    {
        $pdoFetchAttribute = $this->getPdoFetchAttribute($pdoFetchResponse);
        return $this->statement->fetchAll($pdoFetchAttribute);
    }
    
    /**
     * Returns PDO::FETCH_* constant values by FetchAttributeEnum.
     * 
     * @param FetchAttributeEnum $pdoFetchResponse
     * @return int
     */
    private function getPdoFetchAttribute(FetchAttributeEnum $pdoFetchResponse): int 
    {
        return match ($pdoFetchResponse) {
            FetchAttributeEnum::AS_ASSOC => PDO::FETCH_ASSOC,
            FetchAttributeEnum::AS_BOTH => PDO::FETCH_BOTH,
            FetchAttributeEnum::AS_CLASS => PDO::FETCH_CLASS,
            FetchAttributeEnum::AS_NUM => PDO::FETCH_NUM
        };
    }
    
    /**
     * Returns the number of columns in the result set.
     *
     * @return int Returns the number of columns in the result set represented by the Statement object, even if the result set is empty.
     * If there is no result set, returns 0.
     */
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }
    
    /**
     * Returns metadata for a column in a result set.
     *
     * @param int $column The 0-indexed column in the result set.
     * 
     * @return array|false An associative array containing the values representing the metadata for a single column. Or false if the column does not exist.
     * @throws PDOException On database-level failure.
     */
    public function getColumnMeta(int $column): array | false
    {
        return $this->statement->getColumnMeta($column);
    }
}