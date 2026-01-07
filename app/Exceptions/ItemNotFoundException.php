<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class ItemNotFoundException extends HttpException
{
    public function __construct(int $itemId)
    {
        parent::__construct(404, "Item mit ID {$itemId} wurde nicht gefunden.");
    }
}
