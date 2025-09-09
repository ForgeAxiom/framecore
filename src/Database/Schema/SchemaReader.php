<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Schema;

use ForgeAxiom\Framecore\Database\Connection\Connection;
use ForgeAxiom\Framecore\Exceptions\Database\DatabaseTableException;
use ForgeAxiom\Framecore\Exceptions\InvalidSyntaxException;
use PDOException;

/** Responsible for reading database schema. */
final class SchemaReader
{
    private static array $cache = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly TableReaderInterface $tablesReader
    ) {}

    /**
     * Uploads table names from database.
     *
     * @throws PDOException On database-level failure.
     */
    public function getTableNames(): array
    {
        $cacheKey = 'table_names';
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        return self::$cache[$cacheKey] = $this->tablesReader->getTableNames();
    }

    /**
     * Uploads column names from passed table.
     *
     * @throws DatabaseTableException If table does not exist or unavailable.
     */
    public function getColumnNames(string $tableName, ?array $aliasTable = null): array
    {
        $this->validateTableName($tableName, $aliasTable);

        $tableName = $aliasTable[$tableName] ?? $tableName;

        $cacheKey = "columns_{$tableName}";

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $sql = "PRAGMA table_info({$tableName});";
        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $rawColumns = $statement->fetchAll();
        $columnNames = array_map(fn($item) => $item['name'], $rawColumns);
        
        return self::$cache[$cacheKey] = $columnNames;
    }
    
    public static function clearCache(): void
    {
        self::$cache = [];    
    }

    /**
     * @throws DatabaseTableException If table does not exist or unavailable.
     */
    public function validateTableName(string $tableName, ?array $aliasTable = null): void
    {
        $whiteListTables = $this->getTableNames();

        if ($aliasTable !== null && !empty($aliasTable[$tableName])) {
            $tableName = $aliasTable[$tableName];
        }

        if (!in_array($tableName, $whiteListTables) ) {
            throw new DatabaseTableException("Table '{$tableName}' does not exist or unavailable.");
        }
    }

    /**
     * Validates the existence of passed table and columns.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function validOrFailColumnsAndTable(array $columns, string $defaultTable): void
    {
        if (in_array('*', $columns, true)) {
            return;
        }

        $this->failIfColumnsNotInList($columns, $defaultTable);
    }

    /**
     * Validates the existence of passed columns in table, may validate 'table.column' syntax.
     *
     * @param array $columns validating columns.
     * @param string|null $defaultTable For common column like 'users' without 'table.column' syntax.
     * @param array|null $aliasTable Alias table for able to validate alias columns like 'u.id'.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function validateDottedColumns(array $columns, ?string $defaultTable = null, ?array $aliasTable = null): void
    {
        if (in_array('*', $columns, true)) {
            return;
        }

        $sortedColumnsWithTables = [];
        foreach ($columns as $column) {
            if (!str_contains($column, '.') && $defaultTable !== null) {
                $sortedColumnsWithTables[$defaultTable][] = $column;
                continue;
            }

            $tableAndColumn = explode('.', $column);
            if (count($tableAndColumn) > 2) {
                throw new InvalidSyntaxException("Allowed only 'table.column' syntax, given: '{$column}'.");
            }

            $sortedColumnsWithTables[$tableAndColumn[0]][] = $tableAndColumn[1];
        }

        foreach ($sortedColumnsWithTables as $table => $columns) {
            $this->failIfColumnsNotInList($columns, $table, $aliasTable);
        }
    }

    /**
     * @throws DatabaseTableException
     */
    private function failIfColumnsNotInList(array $columns, string $tableName, ?array $aliasTable = null): void
    {
        $whiteListColumns = $this->getColumnNames($tableName, $aliasTable);
        foreach ($columns as $column) {
            $messageAddonOfAlias = '';
            if ($aliasTable !== null && !empty($aliasTable[$tableName])) {
                $alias = $tableName;
                $tableName = $aliasTable[$tableName];
                $messageAddonOfAlias = ", table alias: '{$alias}'";
            }
            if (!in_array($column, $whiteListColumns)) {
                throw new DatabaseTableException("Column '{$column}' does not exist in table '{$tableName}'{$messageAddonOfAlias}.");
            }
        }
    }
}