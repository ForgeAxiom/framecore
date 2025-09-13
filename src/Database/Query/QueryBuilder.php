<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Query; 
use ForgeAxiom\Framecore\Database\Connection\Connection;
use ForgeAxiom\Framecore\Database\Schema\SchemaReader;
use ForgeAxiom\Framecore\Exceptions\DatabaseTableException;
use ForgeAxiom\Framecore\Exceptions\NotInWhiteListException;
use ForgeAxiom\Framecore\Helpers\Formatter;
use PDO;
use PDOException;
use function ForgeAxiom\Framecore\Helpers\dd;

/** Service Class. Responsible for building query. */
final class QueryBuilder
{
    private const WHERE_OPERATORS = ['=', '<>', '!=', '<', '>', '<=', '>=', 'LIKE'];

    private array $queryParts = [];
    private array $bindings = [];

    /**
     * @param Connection $connection
     * @param SchemaReader $schemaReader
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly SchemaReader $schemaReader
    ) {
        $this->clearState(); // Инициализируем состояние
    }
    
    /**
     * Finals query and returns all query result.
     * 
     * @return array Query result data. 
     *
     * @throws PDOException On database-level failure.
     */
    public function get(): array
    {
        return $this->runQuery('fetchAll');
    }

    /**
     * Finals query and returns first query result.
     * 
     * @return array|false Query result data or false if not found. 
     *
     * @throws PDOException On database-level failure.
     */
    public function first(): array | false
    {
        $this->limit(1);
        return $this->runQuery('fetch');
    }

    /**
     * Selects columns from table for subsequent query.
     *
     * @param string $tableName Selectable table.
     * @param array|string $columns Selectable columns from table.
     * 
     * @return self For subsequent build of queries.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function select(string $tableName, array | string $columns = '*'): self
    {
        $columns = Formatter::formatIfStringToArray($columns);
        $this->schemaReader->validOrFailColumnsAndTable($columns, $tableName);

        $this->queryParts['select'] = $columns;
        $this->queryParts['from'] = $tableName;

        return $this;   
    }

    /**
     * Adds a basic "where" clause to the query.
     *
     * @param string $column Column which applies condition.
     * @param string $operator Condition compare operator(=,<,like...).
     * @param mixed $value Value for binding.
     * @param string $logicalOperator Logical operator for binding with previous clauses ('AND' or 'OR').
     *
     * @return self For subsequent build of queries.
     *
     * @throws NotInWhiteListException If logical or where_operator is not valid.
     * @throws DatabaseTableException DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function where(
        string $column, 
        string $operator,
        mixed $value,
        string $logicalOperator = 'AND'
    ): self {
        $logicalOperator = strtoupper($logicalOperator);
        $this->inArrayOrFail($logicalOperator, ['OR', 'AND']);

        $operator = strtoupper($operator);
        $this->inArrayOrFail($operator, self::WHERE_OPERATORS);
     
        $tableName = $this->queryParts['from'];
        $this->schemaReader->validOrFailColumnsAndTable([$column], $tableName);

        $this->queryParts['wheres'][] = [
            'column' => $column, 
            'operator' => $operator, 
            'value' => $value,
            'logical' => $logicalOperator
        ];

        return $this;
    }

    /**
     * Adds a basic "where" clause to the query with logical operator OR.
     *
     * @param string $column Column which applies condition.
     * @param string $operator Condition compare operator(=,<,like...).
     * @param mixed $value Value for binding.
     *
     * @return self For subsequent build of queries.
     *
     * @throws NotInWhiteListException If logical or where_operator is not valid.
     * @throws DatabaseTableException DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function orWhere(
        string $column,
        string $operator,
        mixed $value,
    ): self {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Adds a basic "where" clause to the query with logical operator AND.
     *
     * @param string $column Column which applies condition.
     * @param string $operator Condition compare operator(=,<,like...).
     * @param mixed $value Value for binding.
     *
     * @return self For subsequent build of queries.
     *
     * @throws NotInWhiteListException If logical or where_operator is not valid.
     * @throws DatabaseTableException DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function andWhere(
        string $column,
        string $operator,
        mixed $value,
    ): self {
        return $this->where($column, $operator, $value);
    }

    /**
     * Adds an "order by" clause to the query.
     *
     * @param string $column Column to order by.
     * @param string $direction Direction of sort ('ASC' or 'DESC').
     *
     * @return self
     *
     * @throws NotInWhiteListException If value not found in whitelist.
     * @throws DatabaseTableException DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self 
    {
        $direction = strtoupper($direction);
        $this->inArrayOrFail($direction, ['ASC', 'DESC']);
        $tableName = $this->queryParts['from'];
        $this->schemaReader->validOrFailColumnsAndTable([$column], $tableName);

        $this->queryParts['orders'][] = [
            'column' => $column,
            'direction' => $direction
        ];
        
        return $this;
    }

    /**
     * Adds a "limit" clause to the query.
     */
    public function limit(int $count): self
    {
        $this->queryParts['limit'] = $count;
        return $this;
    }

    /**
     * Adds an "offset" clause to the query.
     */
    public function offset(int $count): self
    {
        $this->queryParts['offset'] = $count;
        return $this;    
    }

    /**
     * Inserts passed data to new row in passed table.
     *
     * @param string $tableName Table name.
     * @param array $data Associative array with keys of columns and inserting value.
     *
     * @return bool Returns true on success or false if failure.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared or database-level failure.
     */
    public function insert(string $tableName, array $data): bool
    {
        $columns = array_keys($data);
        $this->schemaReader->validOrFailColumnsAndTable($columns, $tableName);

        $columnsSql = implode(', ', $columns);

        $columnsValuePlaceholdersSql = implode(', ', array_map(fn(string $column) => ":$column", $columns));

        $sql = <<<SQL
        INSERT INTO {$tableName} ({$columnsSql}) 
        VALUES ({$columnsValuePlaceholdersSql});
        SQL;

        $statement = $this->connection->prepare($sql);

        $this->clearState();
        return $statement->execute($data);
    }

    /**
     * Inserts passed data to new row in passed table and gives last inserted id.
     *
     * NOTE: The return value and behavior of this method may vary depending on the underlying database driver,
     * especially for tables without an auto-incrementing primary key.
     *
     * @param string $tableName Table name.
     * @param array $data Associative array with keys of columns and inserting value.
     *
     * @return string|false The ID of the last inserted row, or false if the driver does not support this capability.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared or database-level failure.
     */
    public function insertAndGetLastId(string $tableName, array $data): string|false
    {
        $this->insert($tableName, $data);
        return $this->connection->lastInsertId();
    }

    /**
     * @param string $fetchMethod
     * @return array|false
     *
     * @throws PDOException If the SQL statement is invalid or cannot be prepared.
     */
    private function runQuery(string $fetchMethod): array | false
    {
        $sql = $this->toSql();
        $statement = $this->connection->prepare($sql);
        $statement->execute($this->bindings);
        $this->clearState();
        return $statement->$fetchMethod();
    }
    
    private function toSql(): string
    {
        $this->bindings = []; // Сбрасываем биндинги перед сборкой

        $sqlParts = [
            $this->makeSelect(),
            $this->makeWhere(),
            $this->makeOrderBy(),
            $this->makeLimit(),
            $this->makeOffset(),
        ];

        return trim(implode(' ', array_filter($sqlParts)));
    }

    private function makeSelect(): string
    {
        if (empty($this->queryParts['select'])) {
            return '';
        }
        return sprintf(
            'SELECT %s FROM %s',
            implode(', ', $this->queryParts['select']),
            $this->queryParts['from']
        );
    }

    private function makeWhere(): string
    {
        if (empty($this->queryParts['wheres'])) {
            return '';
        }
        
        $sql = 'WHERE ';
        foreach ($this->queryParts['wheres'] as $i => $where) {
            if ($i > 0) {
                $sql .= $where['logical'] . ' ';
            }
            $placeholder = ":where_{$where['column']}_{$i}";
            $sql .= "{$where['column']} {$where['operator']} {$placeholder} ";
            $this->bindings[$placeholder] = $where['value'];
        }
        return trim($sql);
    }

    private function makeOrderBy(): string
    {
        if (empty($this->queryParts['orders'])) {
            return '';
        }
        $parts = [];
        foreach ($this->queryParts['orders'] as $order) {
            $parts[] = "{$order['column']} {$order['direction']}";
        }
        return 'ORDER BY ' . implode(', ', $parts);
    }

    private function makeLimit(): string
    {
        if (empty($this->queryParts['limit'])) {
            return '';
        }
        return 'LIMIT ' . (int)$this->queryParts['limit'];
    }

    private function makeOffset(): string
    {
        if (empty($this->queryParts['offset'])) {
            return '';
        }
        // Защита от OFFSET без LIMIT
        if (empty($this->queryParts['limit'])) {
            return '';
        }
        return 'OFFSET ' . (int)$this->queryParts['offset'];
    }

    /**
     * @throws NotInWhiteListException If value not found in whitelist.
     */
    private function inArrayOrFail(string $value, array $whiteList): void
    {
        if (!in_array($value, $whiteList)) {
            throw new NotInWhiteListException("Unavailable value: '{$value}'. Available: " . implode(', ', $whiteList));
        }
    }

    private function clearState(): void
    {
        $this->queryParts = [
            'select' => ['*'],
            'from' => '',
            'wheres' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        $this->bindings = [];
    }
}