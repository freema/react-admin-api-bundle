<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Vlp\Mailer\Api\Admin\Controller\ResourceController;
use Vlp\Mailer\Api\Admin\Security\PermissionService;
use Vlp\Mailer\Entity\Admin;

class ResourceAccessListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly PermissionService $permissionService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof ResourceController) {
            $request = $event->getRequest();
            $resource = $request->attributes->get('resource');
            $method = $request->getMethod();

            /** @var Admin|null $user */
            $user = $this->security->getUser();

            if (!$user instanceof Admin) {
                throw new AccessDeniedHttpException('Authentication required');
            }

            $actionMap = [
                'GET' => 'list',
                'POST' => 'create',
                'PUT' => 'edit',
                'DELETE' => 'delete',
            ];

            $action = $actionMap[$method] ?? 'list';

            if (!$this->permissionService->hasPermission($user, $resource, $action)) {
                throw new AccessDeniedHttpException('Insufficient permissions');
            }
        }
    }
}
