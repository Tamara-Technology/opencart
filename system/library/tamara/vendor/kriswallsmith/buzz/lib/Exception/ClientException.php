<?php

declare (strict_types=1);
namespace TMS\Buzz\Exception;

use TMS\Http\Client\Exception as HTTPlugException;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ClientException extends \RuntimeException implements \TMS\Buzz\Exception\ExceptionInterface, \TMS\Http\Client\Exception
{
}
