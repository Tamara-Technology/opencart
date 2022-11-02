<?php

declare (strict_types=1);
namespace TMS\Buzz\Exception;

/**
 * Thrown when an invalid argument is provided.
 */
class InvalidArgumentException extends \InvalidArgumentException implements \TMS\Buzz\Exception\ExceptionInterface
{
}
