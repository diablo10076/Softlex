<?php

declare(strict_types=1);

namespace src\Decorator;
use Exception;
use DateTime;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProviderInterface;

class SomeCachedDecorator implements DataProviderInterface
{
    /**
     * @var CacheItemInterface
     */
    private $caching;
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get(array $input): array
    {
        try {
            if (!($this->caching instanceof CacheItemInterface)){
                throw new Exception('wrong setter');
            }
            try {
                $cacheItem = $this->cache->getItem($this->caching->getKey());
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
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
            } catch (Exception $e) {
                $this->logger->error(
                    $e->getMessage(),
                    [
                        'exception' => $e
                    ]
                );
            }
        }catch (Exception $e){
            $this->logger->error(
                $e->getMessage(),
                [
                    'exception' => $e
                ]
            );

        }

        return [];
    }

    public function setCaching(CacheItemInterface $caching)
    {
        $this->caching = $caching;
    }
}
