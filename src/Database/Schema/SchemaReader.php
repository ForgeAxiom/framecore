<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Schema;

use ForgeAxiom\Framecore\Database\Connection\Connection;
use InvalidArgumentException;

final class SchemaReader
{
    private static array $cache = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly TableReaderInterface $tablesReader
    ) {}

    public function getTableNames(): array
    {
        $cacheKey = 'table_names';
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        return self::$cache[$cacheKey] = $this->tablesReader->getTableNames();
    }

    public function getColumnNames(string $tableName): array
    {
        $this->validateTableName($tableName);
        $cacheKey = "columns_{$tableName}";

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $sql = "PRAGMA table_info({$tableName});";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        $rawColumns = $statement->fetchAll();
        $columnNames = array_map(fn($item) => $item['name'], $rawColumns);
        
        return self::$cache[$cacheKey] = $columnNames;
    }
    
    public static function clearCache(): void
    {
        self::$cache = [];    
    }

    private function validateTableName(string $tableName): void
    {
        $whiteListTables = $this->getTableNames();
        if (!in_array($tableName, $whiteListTables)) {
            throw new InvalidArgumentException("Таблица '{$tableName}' не существует или недоступна.");
        }
    }

    public function validOrFailColumnsAndTable(array $columns, string $tableName): void
    {
        $this->validateTableName($tableName);
        if (in_array('*', $columns, true)) {
            return;
        }
        $whiteListColumns = $this->getColumnNames($tableName);
        foreach ($columns as $column) {
            if (!in_array($column, $whiteListColumns)) {
                 throw new InvalidArgumentException("Колонка '{$column}' не существует в таблице '{$tableName}'.");
            }
        }
    }
}