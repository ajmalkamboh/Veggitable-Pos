<?php

namespace App\Core\MultiTenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantSuspendedException extends HttpException
{
    public function __construct(string $message = 'This business account is currently suspended. Please contact platform administration.')
    {
        parent::__construct(403, $message);
    }
}
