<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    /**
     * Authorize a given action for the current user.
     *
     * @param mixed $ability
     * @param mixed $arguments
     * @return Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = []): Response
    {
        return Gate::authorize($ability, $arguments);
    }
}
