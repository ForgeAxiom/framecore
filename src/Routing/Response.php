<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Routing;

use ForgeAxiom\Framecore\View\View;

/** 
 * Immutable Value Object. 
 * Represents final HTTP response to be sent to the client.
 */
final class Response
{   
    /**
     * @param int $httpResponseCode
     * @param ?View $view
     */
    public function __construct(
        public readonly int $httpResponseCode = 200,
        public readonly ?View $view = null
    ){}

    /**
     * Creates a new 404 Not Found Response.
     * 
     * If no View provided, a default View named '404' will be used.
     * 
     * @param ?View $view Optional custom View object. 
     * 
     * @return self New instance of Response.
     */
    public static function notFound(?View $view = null) {
        if ($view === null) {
            $view = new View('404');
        }
        return new self(404, $view);
    }
}