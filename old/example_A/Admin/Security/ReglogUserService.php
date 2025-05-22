<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Security;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use VLM\libs\OAuth2\Provider\ReglogResourceOwner;
use VLM\libs\ReglogClient\Client\ClientRegistry;

class ReglogUserService
{
    use LoggerAwareTrait;

    private const CACHE_TTL = 3600;

    private const CACHE_KEY_PREFIX = 'reglog_user_';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $avatarHost,
    ) {
        $this->logger = new NullLogger();
    }

    public function getResourceOwner(string $accessToken): ReglogResourceOwner|ResourceOwnerInterface
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($accessToken);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            if ($data) {
                return new ReglogResourceOwner(unserialize($data));
            }
        }

        try {
            $client = $this->clientRegistry->getClient('admin');
            $resourceOwner = $client->getResourceOwner(new AccessToken([
                'access_token' => $accessToken,
            ]));

            $cacheItem->set(serialize($resourceOwner->toArray()));
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);

            return $resourceOwner;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get resource owner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new ResourceOwnerException('Failed to get resource owner', 0, $e);
        }
    }

    public function getAvatarUrl(?string $avatarUri): ?string
    {
        if (empty($avatarUri) || empty($this->avatarHost)) {
            return null;
        }

        return rtrim($this->avatarHost, '/').'/'.ltrim($avatarUri, '/');
    }
}
