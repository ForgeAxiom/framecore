<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Helpers;

/**
 * Dumps the given variables and ends the script.
 *
 * @param  mixed  ...$vars
 * @return void
 */
function dd(...$vars): void
{
    echo "<pre>";
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo "</pre>";
    die();
}
