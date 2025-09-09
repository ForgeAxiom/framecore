<?php
declare(strict_types=1);

namespace App\Database\Schema;

interface TableReaderInterface
{
    public function getTableNames(): array; 
}