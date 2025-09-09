<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Query; 
use Closure;
use ForgeAxiom\Framecore\Database\Connection\Connection;
use ForgeAxiom\Framecore\Database\Schema\SchemaReader;
use ForgeAxiom\Framecore\Exceptions\Database\DatabaseTableException;
use ForgeAxiom\Framecore\Exceptions\Database\MissingFromClauseException;
use ForgeAxiom\Framecore\Exceptions\Database\MissingWhereClauseException;
use ForgeAxiom\Framecore\Exceptions\InvalidSyntaxException;
use ForgeAxiom\Framecore\Exceptions\InvalidTypeException;
use ForgeAxiom\Framecore\Exceptions\NotInWhiteListException;
use ForgeAxiom\Framecore\Exceptions\PrematureMethodCallException;
use ForgeAxiom\Framecore\Helpers\Formatter;
use ForgeAxiom\Framecore\Helpers\Validator;
use PDOException;

/** Service Class. Responsible for building query. */
final class QueryBuilder
{
    private array $queryParts = [];
    private array $bindings = [];
    private array $aliasTable = [];

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
     * Selects table for subsequent query.
     *
     * @param string $tableName Table name of selecting table.
     *
     * @return self For subsequent build of queries.
     *
     * @throws DatabaseTableException If table does not exist or unavailable.
     */
    public function from(string $tableName): self
    {
        $this->schemaReader->validateTableName($tableName);
        $this->queryParts['from'] = $tableName;
        return $this;
    }

    /**
     * Selects columns based on the table from previous "from" clause for subsequent query.
     *
     * @param array|string $columns Selectable columns from table.
     *
     * @return self For subsequent build of queries.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws MissingFromClauseException If from clause was not used.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function select(array | string $columns = '*'): self
    {
        $this->validOrFailIfMissingFromClause();
        $columns = Formatter::formatIfStringToArray($columns);

        $this->schemaReader->validateDottedColumns(
            $columns,
            $this->queryParts['from']
        );

        $this->queryParts['select'] = $columns;

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
     * @throws NotInWhiteListException If logical or "where" operator is not valid.
     */
    public function where(
        string $column, 
        string $operator,
        mixed $value,
        string $logicalOperator = 'AND'
    ): self {
        $logicalOperator = strtoupper($logicalOperator);
        Validator::inArrayOrFail($logicalOperator, ['OR', 'AND']);

        $operator = strtoupper($operator);
        WhereOperatorsEnum::failIfNotInOperators($operator);

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
     * @throws NotInWhiteListException If logical or "where" operator is not valid.
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
     * @throws NotInWhiteListException If logical or "where" operator is not valid.
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
     * @throws NotInWhiteListException If direction does not comply 'ASC' or 'DESC'.
     * @throws DatabaseTableException DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self 
    {
        $direction = strtoupper($direction);
        Validator::inArrayOrFail($direction, ['ASC', 'DESC']);
        $tableName = $this->queryParts['from'];
        $this->schemaReader->validateDottedColumns([$column], $tableName);

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
     * Inserts passed data to new row in the table from previous "from" clause.
     *
     * @param array $data Associative array with keys of columns and inserting value.
     *
     * @return bool Returns true on success or false if failure.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared or database-level failure.
     * @throws MissingFromClauseException If "from" clause was not used.
     */
    public function insert(array $data): bool
    {
        $this->validOrFailIfMissingFromClause();
        $tableName = $this->queryParts['from'];

        $columns = array_keys($data);

        $this->schemaReader->validOrFailColumnsAndTable(
            $columns,
            $tableName
        );

        $columnsSql = implode(', ', $columns);
        $columnsValuePlaceholdersSql = implode(
            ', ',
            array_map(fn(string $column) => ":$column", $columns)
        );

        $sql = <<<SQL
        INSERT INTO {$tableName} ({$columnsSql}) 
        VALUES ({$columnsValuePlaceholdersSql});
        SQL;

        $statement = $this->connection->prepare($sql);

        $this->clearState();
        return $statement->execute($data);
    }

    /**
     * Inserts passed data to new row in the table from previous "from" clause and gives last inserted id.
     *
     * NOTE: The return value and behavior of this method may vary depending on the underlying database driver,
     * especially for tables without an auto-incrementing primary key.
     *
     * @param array $data Associative array with keys of columns and inserting value.
     *
     * @return string|false The ID of the last inserted row, or false if the driver does not support this capability.
     *
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared or database-level failure.
     * @throws MissingFromClauseException If "from" clause was not used.
     */
    public function insertAndGetLastId(array $data): string|false
    {
        $this->insert($data);
        return $this->connection->lastInsertId();
    }

    /**
     * Updates row or rows in the table from previous "from" clause based on previous "where" clauses.
     *
     * @param array $data New data.
     *
     * @return bool Returns true on success.
     *
     * @throws DatabaseTableException If column from table does not exist or table is unavailable.
     * @throws PDOException If the SQL statement is invalid or cannot be prepared. Or if database-level failure.
     * @throws MissingWhereClauseException If "where" clause was not used.
     * @throws MissingFromClauseException If "from" clause was not used.
     */
    public function update(array $data): bool
    {
        $this->validOrFailIfMissingFromClause();
        $tableName = $this->queryParts['from'];

        $columns = array_keys($data);

        $this->schemaReader->validOrFailColumnsAndTable($columns, $tableName);

        $setSql = 'SET ';
        foreach ($data as $column => $value) {
            $setSql .= "{$column} = :{$column}, ";
        }
        $setSql = rtrim($setSql, ', ');

        $whereSql = $this->makeWhereOrFailIfEmpty();

        $sql = <<<SQL
        UPDATE {$tableName}
        {$setSql}
        {$whereSql};
        SQL;

        $result = $this->connection->prepare($sql)->execute(array_merge($data, $this->bindings));
        $this->clearState();
        return $result;
    }

    /**
     * Deletes row or rows in the table from previous "from" clause based on previous "where" clauses.
     *
     * @return bool Returns true on success.
     *
     * @throws PDOException If the SQL statement is invalid or cannot be prepared. Or if database-level failure.
     * @throws MissingWhereClauseException If "where" clause was not used.
     * @throws MissingFromClauseException If "from" clause was not used.
     */
    public function delete(): bool
    {
        $this->validOrFailIfMissingFromClause();
        $tableName = $this->queryParts['from'];

        $whereSql = $this->makeWhereOrFailIfEmpty();

        $sql = <<<SQL
        DELETE FROM {$tableName} 
        $whereSql;
        SQL;

        $result = $this->connection->prepare($sql)->execute($this->bindings);
        $this->clearState();
        return $result;
    }

    /**
     * Adds a "join" clause to the query.
     *
     * Example:
     * $qb->join('users', fn(JoinClause $join) => $join->onColumn('posts.user_id', '=', 'u.id'), 'u');
     *
     * @param string $tableName Joining table.
     * @param Closure $callback Closure for next "on" clauses. Accepts instance of JoinClause $join for "on" clauses.
     * @param string|null $alias Alias for joining table.
     * @param string $joinType Join type, like 'INNER'.
     *
     * @throws DatabaseTableException If invalid tableName.
     * @throws NotInWhiteListException If $joinType does not comply 'INNER', 'LEFT', 'RIGHT' or 'OUTER'.
     * @throws PrematureMethodCallException If called before "from" clause.
     */
    public function join(string $tableName, Closure $callback, ?string $alias = null, string $joinType = 'INNER'): QueryBuilder
    {
        $this->schemaReader->validateTableName($tableName);
        $this->addAliasIfNotNull($tableName, $alias);

        if (empty($this->queryParts['from'])) {
            throw new PrematureMethodCallException('Call "from" before "join"');
        }

        $joinClause = new JoinClause(
            joinType: $joinType,
            schemaReader: $this->schemaReader,
            tableName: $tableName,
            fromTableName: $this->queryParts['from'],
            aliasTable: $this->aliasTable,
            alias: $alias
        );

        $callback($joinClause);

        $this->queryParts['joins'][] = $joinClause;

        return $this;
    }

    private function addAliasIfNotNull(string $tableName, ?string $alias): void
    {
        if ($alias !== null) {
            $this->aliasTable[$alias] = $tableName;
        }
    }

    /**
     * @throws MissingFromClauseException
     */
    private function validOrFailIfMissingFromClause(): void
    {
        if (empty($this->queryParts['from'])) {
            throw new MissingFromClauseException('Missing "from" clause, use method from().');
        }
    }

    /**
     * @throws MissingWhereClauseException If "where" clause was not used.
     */
    private function makeWhereOrFailIfEmpty(): string
    {
        $whereSql = $this->makeWhere();
        if ($whereSql === '') {
            throw new MissingWhereClauseException('"Where" clause was not used, use where method');
        }
        return $whereSql;
    }

    /**
     * @param string $fetchMethod
     * @return array|false
     *
     * @throws PDOException If the SQL statement is invalid or cannot be prepared.
     * @throws InvalidTypeException If in queryParts['joins'] not JoinClause.
     */
    private function runQuery(string $fetchMethod): array | false
    {
        $sql = $this->toSql();
        $statement = $this->connection->prepare($sql);
        $statement->execute($this->bindings);
        $this->clearState();
        return $statement->$fetchMethod();
    }

    /**
     * @throws InvalidTypeException If in queryParts['joins'] not JoinClause.
     */
    private function toSql(): string
    {
        $this->bindings = []; // Сбрасываем биндинги перед сборкой

        $sqlParts = [
            $this->makeSelect(),
            $this->makeJoin(),
            $this->makeWhere(),
            $this->makeOrderBy(),
            $this->makeLimit(),
            $this->makeOffset(),
        ];

        return trim(implode(' ', array_filter($sqlParts)));
    }

    /**
     * @throws InvalidTypeException If in queryParts['joins'] not JoinClause.
     */
    private function makeJoin(): string
    {
        if (empty($this->queryParts['joins'])) {
            return '';
        }
        $sql = '';
        foreach ($this->queryParts['joins'] as $join) {
            $this->failIfNotJoin($join);
            $sql .= $join->makeJoin() . ' ';
            $this->bindings = array_merge($this->bindings, $join->getBindings());
        }

        return rtrim($sql, ' ');
    }

    /**
     * @throws InvalidTypeException If in queryParts['joins'] not JoinClause.
     */
    private function failIfNotJoin(mixed $join): void
    {
        if (gettype($join) !== 'object' && get_class($join) !== JoinClause::class) {
            throw new InvalidTypeException('Only instance of ' . JoinClause::class . ' found: ' . $join);
        }
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
            $columnWithoutDot = str_replace('.', '_', $where['column']);
            $placeholder = ":where_{$columnWithoutDot}_{$i}";
            $sql .= "{$where['column']} {$where['operator']} {$placeholder} ";
            $this->bindings[ltrim($placeholder, ':')] = $where['value'];
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

    private function clearState(): void
    {
        $this->queryParts = [
            'select' => ['*'],
            'join' => [],
            'from' => '',
            'wheres' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        $this->bindings = [];
    }
}