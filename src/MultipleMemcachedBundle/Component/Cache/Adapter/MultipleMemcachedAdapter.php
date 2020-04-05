<?php

namespace MultipleMemcachedBundle\Component\Cache\Adapter;

use \Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Cache\Traits\MemcachedTrait;

class MultipleMemcachedAdapter extends MemcachedAdapter
{
    public static function createConnection($servers, array $options = []): \Memcached
    {
        if (\is_string($servers)) {
            $servers = \explode(',', $servers);
            foreach ($servers as $serverIndex => $server) {
                if (0 !== strpos($server, 'memcached://')) {
                    $servers[$serverIndex] = "memcached://$server";
                }
            }
        }
        if (!\is_array($servers)) {
            throw new InvalidArgumentException(sprintf('Unsupported Server/DSN list: %s.', var_export($servers, true)));
        }
        return MemcachedTrait::createConnection($servers, $options);
    }
}
