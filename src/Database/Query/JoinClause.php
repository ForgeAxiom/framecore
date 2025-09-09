<?php

namespace ForgeAxiom\Framecore\Database\Query;

use ForgeAxiom\Framecore\Database\Schema\SchemaReader;
use ForgeAxiom\Framecore\Exceptions\Database\DatabaseTableException;
use ForgeAxiom\Framecore\Exceptions\InvalidSyntaxException;
use ForgeAxiom\Framecore\Exceptions\NotInWhiteListException;
use ForgeAxiom\Framecore\Helpers\Validator;

final class JoinClause
{
    const JOIN_TYPES = ['INNER', 'LEFT', 'RIGHT', 'OUTER'];
    public readonly string $tableName;
    public readonly string $fromTableName;
    private readonly SchemaReader $schemaReader;
    private string $joinType;
    private array $onConditions = [];
    private array $bindings = [];
    private array $aliasTable;
    private ?string $alias;


    /**
     * @throws NotInWhiteListException If invalid join type.
     * @throws DatabaseTableException If invalid table name.
     */
    public function __construct(
        string $joinType,
        SchemaReader $schemaReader,
        string $tableName,
        string $fromTableName,
        array $aliasTable,
        ?string $alias = null
    ) {
        $this->schemaReader = $schemaReader;
        $this->schemaReader->validateTableName($tableName);
        $this->tableName = $tableName;
        $this->schemaReader->validateTableName($fromTableName);
        $this->fromTableName = $fromTableName;
        $this->aliasTable = $aliasTable;
        $this->alias = $alias;
        $joinType = strtoupper($joinType);
        Validator::inArrayOrFail($joinType, self::JOIN_TYPES);
        $this->joinType = $joinType;
    }

    /**
     * Adds an "ON" clause condition that compares two columns.
     *
     * Example: `$join->onColumn('posts.user_id', '=', 'users.id');`
     * Example with aliases: `$join->onColumn('p.user_id', '=', 'u.id');`
     *
     * @param string $leftColumn The column on the left side of the comparison. Must be fully qualified (e.g., 'table.column' or 'alias.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param string $rightColumn The column on the right side of the comparison. Must be fully qualified.
     * @param string $logical The logical operator to combine with the previous ON condition ('AND' or 'OR'). Default is 'AND'.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If operator not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function onColumn(string $leftColumn, string $operator, string $rightColumn, string $logical = "AND"): self
    {
        WhereOperatorsEnum::failIfNotInOperators($operator);
        $this->schemaReader->validateDottedColumns([$leftColumn, $rightColumn], $this->fromTableName, $this->aliasTable);
        $logical = strtoupper($logical);
        Validator::inArrayOrFail($logical, ['AND', 'OR']);

        $this->onConditions['onColumns'][] = [
            'leftColumn' => $leftColumn,
            'operator' => $operator,
            'rightColumn' => $rightColumn,
            'logical' => $logical
        ];

        return $this;
    }

    /**
     * Adds an "ON" clause condition that compares two columns with previous condition with "AND".
     *
     * Example: `$join->onColumn('posts.user_id', '=', 'users.id');`
     * Example with aliases: `$join->onColumn('p.user_id', '=', 'u.id');`
     *
     * @param string $leftColumn The column on the left side of the comparison. Must be fully qualified (e.g., 'table.column' or 'alias.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param string $rightColumn The column on the right side of the comparison. Must be fully qualified.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If operator not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function andOnColumn(string $leftColumn, string $operator, string $rightColumn): self
    {
        return $this->onColumn($leftColumn, $operator, $rightColumn);
    }

    /**
     * Adds an "ON" clause condition that compares two columns with previous condition with "OR".
     *
     * Example: `$join->onColumn('posts.user_id', '=', 'users.id');`
     * Example with aliases: `$join->onColumn('p.user_id', '=', 'u.id');`
     *
     * @param string $leftColumn The column on the left side of the comparison. Must be fully qualified (e.g., 'table.column' or 'alias.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param string $rightColumn The column on the right side of the comparison. Must be fully qualified.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If operator not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function orOnColumn(string $leftColumn, string $operator, string $rightColumn): self
    {
        return $this->onColumn($leftColumn, $operator, $rightColumn, 'OR');
    }

    /**
     * Adds an "ON" clause condition that compares a column against a specific value.
     *
     * Example: `$join->onValue('posts.user_id', '=', 10);`
     *
     * @param string $column The column on the left side of the comparison. Can be fully qualified (e.g., 'table.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param mixed $value The value to compare against. This value will be safely bound to a placeholder.
     * @param string $logical The logical operator to combine with the previous ON condition ('AND' or 'OR'). Default is 'AND'.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If operator not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function onValue(string $column, string $operator, string|int|float $value, string $logical = "AND"): self
    {
        WhereOperatorsEnum::failIfNotInOperators($operator);
        $this->schemaReader->validateDottedColumns([$column], $this->fromTableName, $this->aliasTable);
        $logical = strtoupper($logical);
        Validator::inArrayOrFail($logical, ['AND', 'OR']);

        $this->onConditions['onValues'][] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'logical' => $logical
        ];

        return $this;
    }

    /**
     * Adds an "ON" clause condition that compares a column against a specific value with previous condition with "AND".
     *
     * Example: `$join->onValue('posts.user_id', '=', 10);`
     *
     * @param string $column The column on the left side of the comparison. Can be fully qualified (e.g., 'table.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param mixed $value The value to compare against. This value will be safely bound to a placeholder.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function andOnValue(string $column, string $operator, string|int|float $value): self
    {
        return $this->onValue($column, $operator, $value);
    }

    /**
     * Adds an "ON" clause condition that compares a column against a specific value with previous condition with "OR".
     *
     * Example: `$join->onValue('posts.user_id', '=', 10);`
     *
     * @param string $column The column on the left side of the comparison. Can be fully qualified (e.g., 'table.column').
     * @param string $operator The comparison operator (e.g., '=', '>', 'LIKE').
     * @param mixed $value The value to compare against. This value will be safely bound to a placeholder.
     *
     * @return self For subsequent chaining of ON conditions.
     *
     * @throws NotInWhiteListException If not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE'] or logical in ['AND', 'OR'].
     * @throws DatabaseTableException If column from table does not exist or table does not exist or unavailable.
     * @throws InvalidSyntaxException If passed column was used "join" syntax, like 'table.column', but invalid.
     */
    public function orOnValue(string $column, string $operator, string|int|float $value): self
    {
        return $this->onValue($column, $operator, $value, 'OR');
    }

    public function makeJoin(): ?string
    {
        if (empty($this->onConditions)) {
            return '';
        }

        $sql = $this->joinType . ' JOIN ' . $this->tableName;
        if ($this->alias !== null) {
            $sql .= ' AS ' . $this->alias . ' ON ';
        } else {
            $sql .= ' ON ';
        }

        $i = 0;
        foreach ($this->onConditions as $key => $condition) {
            $i++;
            foreach ($this->onConditions[$key] as $j => $on) {
                if ($i > 1) {
                    $sql .= $on['logical'] . ' ';
                }

                if ($key === 'onValues') {
                    $columnWithoutDot = str_replace('.', '_', $on['column']);
                    $placeholder = ":on_{$columnWithoutDot}_{$j}";
                    $sql .= "{$on['column']} {$on['operator']} {$placeholder} ";
                    $this->bindings[ltrim($placeholder, ':')] = $on['value'];
                }
                if ($key === 'onColumns') {
                    $sql .= "{$on['leftColumn']} {$on['operator']} {$on['rightColumn']} ";
                }
            }
        }

        return trim($sql);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}