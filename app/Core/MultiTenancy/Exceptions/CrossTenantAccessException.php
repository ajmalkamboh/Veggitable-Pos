<?php

namespace App\Core\MultiTenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CrossTenantAccessException extends HttpException
{
    public function __construct(string $message = 'Unauthorized cross-tenant data access attempt detected.')
    {
        parent::__construct(403, $message);
    }
}
