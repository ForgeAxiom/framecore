<?php

declare(strict_types=1);

namespace App\Database\Query; use App\Database\Connection\Connection;
use App\Database\Schema\SchemaReader;
use ForgeAxiom\Framecore\Helpers\Formatter;
use InvalidArgumentException;

/** Service Class. Responsible for building query. */
final class QueryBuilder
{
    const WHERE_OPERATORS = ['=', '<>', '!=', '<', '>', '<=', '>=', 'LIKE'];

    private array $queryParts = [];
    private array $bindings = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly SchemaReader $schemaReader
    ) {
        $this->clear(); // Инициализируем состояние
    }
    
    /**
     * Finals query and returns all query result.
     * 
     * @return array Query result data. 
     * 
     * @throws \PDOException If PDO failure.
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
     * @throws \PDOException If PDO failure.
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
     * Adds a basic where clause to the query.
     * 
     * @param string $column Column which applies condition.
     * @param string $operator Condition compare operator(=,<,like...).
     * @param mixed $value Value for binding.
     * @param string $logicalOperator Logical operator for binding with previous clauses ('AND' or 'OR').
     * 
     * @return self For subsequent build of queries.
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
     * Adds an "order by" clause to the query.
     * 
     * @param string $column Column to order by.
     * @param string $direction Direction of sort ('ASC' or 'DESC').
     * 
     * @return self
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

    private function runQuery(string $fetchMethod): array | false
    {
        $sql = $this->toSql();
        $statement = $this->connection->prepare($sql);
        $statement->execute($this->bindings);
        $this->clear();
        return $statement->$fetchMethod();
    }
    
    public function toSql(): string
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

    private function inArrayOrFail(string $value, array $whiteList): void 
    {
        if (!in_array($value, $whiteList)) {
            throw new InvalidArgumentException("Недопустимое значение '{$value}'. Доступно: " . implode(', ', $whiteList));
        }
    }

    private function clear(): void
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