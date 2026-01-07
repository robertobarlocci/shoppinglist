<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class SyncConflictException extends HttpException
{
    /**
     * @param  array<string, mixed>  $conflicts
     */
    public function __construct(
        public readonly array $conflicts,
        string $message = 'Synchronisierungskonflikt entdeckt.',
    ) {
        parent::__construct(409, $message);
    }

    /**
     * Get the conflicts array.
     *
     * @return array<string, mixed>
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
