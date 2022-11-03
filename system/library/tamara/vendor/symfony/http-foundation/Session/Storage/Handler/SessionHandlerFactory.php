<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler;

use TMS\Doctrine\DBAL\DriverManager;
use TMS\Symfony\Component\Cache\Adapter\AbstractAdapter;
use TMS\Symfony\Component\Cache\Traits\RedisClusterProxy;
use TMS\Symfony\Component\Cache\Traits\RedisProxy;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class SessionHandlerFactory
{
    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\ClientInterface|RedisProxy|RedisClusterProxy|\Memcached|\PDO|string $connection Connection or DSN
     */
    public static function createHandler($connection) : \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler
    {
        if (!\is_string($connection) && !\is_object($connection)) {
            throw new \TypeError(\sprintf('Argument 1 passed to "%s()" must be a string or a connection object, "%s" given.', __METHOD__, \gettype($connection)));
        }
        switch (\true) {
            case $connection instanceof \TMS\Redis:
            case $connection instanceof \TMS\RedisArray:
            case $connection instanceof \TMS\RedisCluster:
            case $connection instanceof \TMS\Predis\ClientInterface:
            case $connection instanceof \TMS\Symfony\Component\Cache\Traits\RedisProxy:
            case $connection instanceof \TMS\Symfony\Component\Cache\Traits\RedisClusterProxy:
                return new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler($connection);
            case $connection instanceof \TMS\Memcached:
                return new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler($connection);
            case $connection instanceof \PDO:
                return new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler($connection);
            case !\is_string($connection):
                throw new \InvalidArgumentException(\sprintf('Unsupported Connection: "%s".', \get_class($connection)));
            case str_starts_with($connection, 'file://'):
                $savePath = \substr($connection, 7);
                return new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\StrictSessionHandler(new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler('' === $savePath ? null : $savePath));
            case str_starts_with($connection, 'redis:'):
            case str_starts_with($connection, 'rediss:'):
            case str_starts_with($connection, 'memcached:'):
                if (!\class_exists(\TMS\Symfony\Component\Cache\Adapter\AbstractAdapter::class)) {
                    throw new \InvalidArgumentException(\sprintf('Unsupported DSN "%s". Try running "composer require symfony/cache".', $connection));
                }
                $handlerClass = str_starts_with($connection, 'memcached:') ? \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler::class : \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler::class;
                $connection = \TMS\Symfony\Component\Cache\Adapter\AbstractAdapter::createConnection($connection, ['lazy' => \true]);
                return new $handlerClass($connection);
            case str_starts_with($connection, 'pdo_oci://'):
                if (!\class_exists(\TMS\Doctrine\DBAL\DriverManager::class)) {
                    throw new \InvalidArgumentException(\sprintf('Unsupported DSN "%s". Try running "composer require doctrine/dbal".', $connection));
                }
                $connection = \TMS\Doctrine\DBAL\DriverManager::getConnection(['url' => $connection])->getWrappedConnection();
            // no break;
            case str_starts_with($connection, 'mssql://'):
            case str_starts_with($connection, 'mysql://'):
            case str_starts_with($connection, 'mysql2://'):
            case str_starts_with($connection, 'pgsql://'):
            case str_starts_with($connection, 'postgres://'):
            case str_starts_with($connection, 'postgresql://'):
            case str_starts_with($connection, 'sqlsrv://'):
            case str_starts_with($connection, 'sqlite://'):
            case str_starts_with($connection, 'sqlite3://'):
                return new \TMS\Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler($connection);
        }
        throw new \InvalidArgumentException(\sprintf('Unsupported Connection: "%s".', $connection));
    }
}
