<?php

declare (strict_types=1);
namespace TMS\Tamara\Notification;

use TMS\Symfony\Component\HttpFoundation\Request;
use TMS\Tamara\Notification\Exception\ForbiddenException;
use TMS\Tamara\Notification\Exception\NotificationException;
use TMS\Tamara\Notification\Message\AuthoriseMessage;
use TMS\Tamara\Notification\Message\WebhookMessage;
class NotificationService
{
    /**
     * @var string
     */
    private $tokenKey;
    public static function create(string $tokenKey) : \TMS\Tamara\Notification\NotificationService
    {
        $self = new self();
        $self->tokenKey = $tokenKey;
        return $self;
    }
    /**
     * @return AuthoriseMessage
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    public function processAuthoriseNotification() : \TMS\Tamara\Notification\Message\AuthoriseMessage
    {
        /** @var AuthoriseMessage $response */
        $response = \TMS\Tamara\Notification\Message\AuthoriseMessage::fromArray($this->process());
        return $response;
    }
    /**
     * @return WebhookMessage
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    public function processWebhook() : \TMS\Tamara\Notification\Message\WebhookMessage
    {
        /** @var WebhookMessage $response */
        $response = \TMS\Tamara\Notification\Message\WebhookMessage::fromArray($this->process());
        return $response;
    }
    /**
     * @return array
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    private function process() : array
    {
        $request = $this->createRequest();
        if ($request->getMethod() !== 'POST') {
            throw new \TMS\Tamara\Notification\Exception\NotificationException('Bad request.');
        }
        $this->authenticate($request);
        return \json_decode($request->getContent(), \true);
    }
    /**
     * @param Request $request
     *
     * @throws ForbiddenException
     */
    private function authenticate(\TMS\Symfony\Component\HttpFoundation\Request $request) : void
    {
        $this->createAuthenticator()->authenticate($request);
    }
    private function createAuthenticator() : \TMS\Tamara\Notification\Authenticator
    {
        return new \TMS\Tamara\Notification\Authenticator($this->tokenKey);
    }
    private function createRequest() : \TMS\Symfony\Component\HttpFoundation\Request
    {
        return \TMS\Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }
}
