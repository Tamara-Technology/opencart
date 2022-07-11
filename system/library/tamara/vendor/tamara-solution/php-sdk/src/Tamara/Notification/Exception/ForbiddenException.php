<?php

declare (strict_types=1);
namespace TMS\Tamara\Notification\Exception;

class ForbiddenException extends \TMS\Tamara\Notification\Exception\NotificationException
{
    protected $code = 401;
}
