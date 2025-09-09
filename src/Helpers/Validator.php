<?php

namespace ForgeAxiom\Framecore\Helpers;

use ForgeAxiom\Framecore\Exceptions\NotInWhiteListException;

final readonly class Validator
{
    /**
     * @throws NotInWhiteListException If value not found in whitelist.
     */
    public static function inArrayOrFail(string $value, array $whiteList): void
    {
        if (!in_array($value, $whiteList)) {
            throw new NotInWhiteListException("Unavailable value: '{$value}'. Available: " . implode(', ', $whiteList));
        }
    }
}