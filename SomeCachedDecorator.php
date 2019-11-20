<?php

declare(strict_types=1);

namespace src\Decorator;

use DateTime;
use src\Cache\CacheItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProviderInterface;

class SomeCachedDecorator
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DataProviderInterface
     */
    protected $provider;

    /**
     * @param DataProviderInterface $provider
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(DataProviderInterface $provider, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @param array $input
     * @return array
     * @see DataProviderInterface::get()
     */
    public function get(array $input): array
    {
        $cacheItem = new CacheItem($input);
        try {
            $cacheItem = $this->cache->getItem($cacheItem->getKey());
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            $this->logger->critical(
                'cache getItem error',
                [
                    'message' => $e->getMessage(),
                    // + все доп. данные по исключению, ключ кеша, имя класса пула
                ]
            );
        }
        $result = $this->provider->get($input);
        $cacheItem
            ->set($result)
            ->expiresAt(
                (new DateTime())->modify('+1 day')
            );
        if (!$this->cache->save($cacheItem)) {
            $this->logger->critical(
                'cache save error',
                [
                    'cachePoolClassname' => get_class($this->cache),
                    //  и любая другая информация чтобы было понимание почему данные не записались в кеш
                ]
            );
        }

        return $result;
    }
}
