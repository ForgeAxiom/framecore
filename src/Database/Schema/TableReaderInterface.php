<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Database\Schema;

interface TableReaderInterface
{
    public function getTableNames(): array; 
}