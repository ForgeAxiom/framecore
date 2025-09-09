<?php
declare(strict_types=1);

namespace App\Database\Connection; 

enum FetchAttributeEnum
{
    case AS_ASSOC;
    case AS_BOTH;
    case AS_CLASS;
    case AS_NUM;
}