<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
    ) {
        parent::__construct(
            "Only {$availableQuantity} unit(s) available for item #{$itemId}, {$requestedQuantity} requested."
        );
    }
}
