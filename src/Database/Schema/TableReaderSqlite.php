<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Schema;

use ForgeAxiom\Framecore\Database\Connection\Connection;
use ForgeAxiom\Framecore\Database\Schema\TableReaderInterface;

class TableReaderSqlite implements TableReaderInterface
{
    public function __construct(
        private readonly Connection $connection
    ){}

    public function getTableNames(): array
    {
        $statement = $this->connection->prepare(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';"
        );

        $statement->execute([]);
        $fetchedData = $statement->fetchAll();

        return array_map(fn($item) => $item['name'], $fetchedData);
    }
}