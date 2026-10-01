<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Functional\App;

use Freema\ReactAdminApiBundle\Event\Common\ResourceAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Denies every access whose "resource:operation" is listed in $deny, and
 * records every access it sees.
 */
class AccessListener implements EventSubscriberInterface
{
    /** @var list<string> */
    public static array $deny = [];

    /** @var list<string> */
    public static array $seen = [];

    public static function getSubscribedEvents(): array
    {
        return ['react_admin_api.resource_access' => 'onAccess'];
    }

    public function onAccess(ResourceAccessEvent $event): void
    {
        $key = $event->getResource().':'.$event->getOperation();
        self::$seen[] = $key.($event->getResourceId() !== null ? ':'.$event->getResourceId() : '');
        if (in_array($key, self::$deny, true) || in_array($event->getResource().':*', self::$deny, true)) {
            $event->cancel();
        }
    }
}
