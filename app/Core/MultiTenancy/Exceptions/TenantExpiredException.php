<?php

namespace App\Core\MultiTenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantExpiredException extends HttpException
{
    public function __construct(string $message = 'Your subscription or trial has expired. Please renew your subscription to continue.')
    {
        parent::__construct(403, $message);
    }
}
