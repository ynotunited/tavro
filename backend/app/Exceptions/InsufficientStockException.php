<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly string $itemName,
        public readonly float  $requested,
        public readonly float  $available,
    ) {
        parent::__construct(
            "Insufficient stock for \"{$itemName}\". " .
            "Requested: {$requested}, available: {$available}."
        );
    }
}
