<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class MealPlanNotFoundException extends HttpException
{
    public function __construct(int $mealPlanId)
    {
        parent::__construct(404, "Mahlzeitenplan mit ID {$mealPlanId} wurde nicht gefunden.");
    }
}
