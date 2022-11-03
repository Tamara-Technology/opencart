<?php

declare (strict_types=1);
namespace TMS\Tamara\Notification;

use TMS\Firebase\JWT\JWT;
use TMS\Symfony\Component\HttpFoundation\Request;
use TMS\Tamara\Notification\Exception\ForbiddenException;
use Throwable;
class Authenticator
{
    private const AUTHORIZATION = 'Authorization', TOKEN = 'tamaraToken';
    /**
     * @var string
     */
    private $tokenKey;
    public function __construct(string $tokenKey)
    {
        $this->tokenKey = $tokenKey;
    }
    /**
     * @param Request $request
     *
     * @throws ForbiddenException
     */
    public function authenticate(\TMS\Symfony\Component\HttpFoundation\Request $request) : void
    {
        if (!$request->headers->has(self::AUTHORIZATION) && !$request->get(self::TOKEN)) {
            throw new \TMS\Tamara\Notification\Exception\ForbiddenException('Access denied.');
        }
        $token = $request->headers->get(self::AUTHORIZATION) ? $this->getBearerToken($request->headers->get(self::AUTHORIZATION)) : $request->get(self::TOKEN);
        try {
            $this->decode($token);
        } catch (\Throwable $exception) {
            throw new \TMS\Tamara\Notification\Exception\ForbiddenException('Access denied.');
        }
    }
    protected function getBearerToken(string $authorizationHeader) : string
    {
        if (!empty($authorizationHeader) && \preg_match('/Bearer\\s(\\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        throw new \TMS\Tamara\Notification\Exception\ForbiddenException('Access denied.');
    }
    /**
     * @param string $token
     *
     * @return object
     */
    protected function decode(string $token)
    {
        return \TMS\Firebase\JWT\JWT::decode($token, $this->tokenKey, ['HS256']);
    }
}
