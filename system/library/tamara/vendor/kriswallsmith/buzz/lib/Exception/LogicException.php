<?php

declare (strict_types=1);
namespace TMS\Buzz\Exception;

/**
 * Thrown whenever a required call-flow is not respected.
 */
class LogicException extends \LogicException implements \TMS\Buzz\Exception\ExceptionInterface
{
}
