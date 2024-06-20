<?php

namespace TMS\Http\Message;

/**
 * Factory for PSR-7 Request and Response.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface MessageFactory extends \TMS\Http\Message\RequestFactory, \TMS\Http\Message\ResponseFactory
{
}
