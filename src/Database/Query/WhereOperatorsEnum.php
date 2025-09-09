<?php

namespace ForgeAxiom\Framecore\Database\Query;

use ForgeAxiom\Framecore\Exceptions\NotInWhiteListException;

enum WhereOperatorsEnum: string
{
    case EQUAL = '=';
    case GREATER = '>';
    case LESS = '<';
    case NOT_EQUAL = '!=';
    case NOT_EQUAL_ALT = '<>';
    case GREATER_THAN = '>=';
    case LESS_THAN_OR_EQUAL = '<=';
    case IS_NOT = 'IS NOT';
    case LIKE = 'LIKE';
    case NOT_LIKE = 'NOT LIKE';

    /**
     * @throws NotInWhiteListException If not in ['=', '<>', '!=', '<', '>', '<=', '>=', 'NOT LIKE', 'LIKE', 'IS NOT'].
     */
    public static function failIfNotInOperators(string $operator): void
    {
        if (!WhereOperatorsEnum::tryFrom($operator)) {
            throw new NotInWhiteListException('Invalid where operator. Given: ' . $operator);
        }
    }
}
