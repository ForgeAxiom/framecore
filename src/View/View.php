<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\View;

use ForgeAxiom\Framecore\Exceptions\FileNotExistsException;

/** 
 * Immutable Value Object. 
 * Responsible for keeping HTML pages. 
 */
final class View
{
    private const DEFAULT_VIEWS = ['404'];

    /**
     * @param string $view View name, like ('viewName').
     */
    public function __construct(
        private readonly string $view
    ){}

    /**
     * Gives HTML page of View.
     *
     * @return string HTML page.
     */
    public function getView(): string
    {
        $view = $this->view;
        $formatedPathToView = '/../../app/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists(__DIR__ . $formatedPathToView)) {
            return require_once __DIR__ . $formatedPathToView;
        } else if (in_array($view, self::DEFAULT_VIEWS)) {
            return require_once __DIR__ . "/default/$view" . ".php";
        }

        return self::fallback(__DIR__ . $formatedPathToView);
    }
    
    private static function fallback(string $path): string
    {  
       throw new FileNotExistsException("View does not exists, given: $path"); 
    }
}