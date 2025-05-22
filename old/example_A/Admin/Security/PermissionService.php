<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Security;

use Vlp\Mailer\Entity\Admin;

class PermissionService
{
    private array $permissions;

    public function __construct(array $permissions)
    {
        $this->validatePermissionsStructure($permissions);
        $this->permissions = $permissions;
    }

    private function validatePermissionsStructure(array $permissions): void
    {
        if (!isset($permissions['roles']) || !is_array($permissions['roles'])) {
            throw new \InvalidArgumentException('Permissions must contain "roles" array');
        }

        if (!isset($permissions['project_rights']) || !is_array($permissions['project_rights'])) {
            throw new \InvalidArgumentException('Permissions must contain "project_rights" array');
        }
    }

    public function getUserPermissions(Admin $user): array
    {
        if ($user->isSuperAdmin()) {
            $allPermissions = [];
            foreach ($this->permissions['roles'] as $rolePermissions) {
                foreach ($rolePermissions as $resource => $actions) {
                    if (!isset($allPermissions[$resource])) {
                        $allPermissions[$resource] = [];
                    }
                    $allPermissions[$resource] = array_unique(array_merge($allPermissions[$resource], $actions));
                }
            }

            // Přidáme i všechny zdroje a akce z projektových oprávnění
            foreach ($this->permissions['project_rights'] as $rightPermissions) {
                foreach ($rightPermissions as $resource => $actions) {
                    if (!isset($allPermissions[$resource])) {
                        $allPermissions[$resource] = [];
                    }
                    $allPermissions[$resource] = array_unique(array_merge($allPermissions[$resource], $actions));
                }
            }

            return [
                'roles' => $user->getRoles(),
                'permissions' => $allPermissions,
            ];
        }

        $permissions = $this->getBasePermissions($user->getRoles());
        foreach ($user->getProjectRights() as $projectRight) {
            $projectRights = $projectRight->getRights();

            foreach ($projectRights as $right) {
                if (isset($this->permissions['project_rights'][$right])) {
                    $this->mergePermissions($permissions, $this->permissions['project_rights'][$right]);
                }
            }
        }

        return [
            'roles' => $user->getRoles(),
            'permissions' => $permissions,
        ];
    }

    private function getBasePermissions(array $roles): array
    {
        $permissions = [];

        foreach ($roles as $role) {
            if (isset($this->permissions['roles'][$role])) {
                $this->mergePermissions($permissions, $this->permissions['roles'][$role]);
            }
        }

        return $permissions;
    }

    private function mergePermissions(array &$target, array $source): void
    {
        foreach ($source as $resource => $actions) {
            if (!isset($target[$resource])) {
                $target[$resource] = [];
            }

            $target[$resource] = array_unique(array_merge($target[$resource], $actions));
        }
    }

    public function hasPermission(Admin $user, string $resource, string $action): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        return isset($userPermissions['permissions'][$resource])
            && in_array($action, $userPermissions['permissions'][$resource], true);
    }
}
