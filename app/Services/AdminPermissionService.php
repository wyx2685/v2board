<?php

namespace App\Services;

class AdminPermissionService
{
    public static function isSuper(array $admin): bool
    {
        return !empty($admin['is_admin'])
            && array_key_exists('admin_permissions', $admin)
            && $admin['admin_permissions'] === null;
    }

    public static function has(array $admin, string $permission): bool
    {
        return !empty($admin['is_admin']) && (self::isSuper($admin)
            || (is_array($admin['admin_permissions'] ?? null)
                && in_array($permission, $admin['admin_permissions'], true)));
    }

    public static function allowsAction(array $admin, string $action, $request = null): bool
    {
        if (empty($admin['is_admin'])) return false;
        if (self::isSuper($admin)) return true;
        $action = ltrim($action, '\\');
        if ($action === 'App\\Http\\Controllers\\V1\\Admin\\PermissionController@current') return true;
        // Existing business pages need currency labels, never secret configuration.
        if ($action === 'App\\Http\\Controllers\\V1\\Admin\\ConfigController@fetch'
            && $request && $request->input('key') === 'site') return true;
        $permission = config('admin_permissions.actions', [])[$action] ?? null;
        return $permission !== null && self::has($admin, $permission);
    }

    public static function access(array $admin): array
    {
        return [
            'id' => $admin['id'],
            'is_super_admin' => self::isSuper($admin),
            'permissions' => is_array($admin['admin_permissions'] ?? null) ? $admin['admin_permissions'] : [],
            'catalog' => config('admin_permissions.labels'),
        ];
    }

    public static function normalize(array $permissions): array
    {
        $dependencies = [
            'user.view' => ['plan.view', 'server.group.view'],
            'order.view' => ['plan.view'],
            'coupon.view' => ['plan.view'],
            'giftcard.view' => ['plan.view'],
            'plan.view' => ['server.group.view'],
            'server.view' => ['server.group.view'],
            'user.finance' => ['user.update'],
            'stat.view' => ['dashboard.view'],
        ];
        $permissions = array_values(array_unique($permissions));
        for ($i = 0; $i < count($permissions); $i++) {
            $permission = $permissions[$i];
            $separator = strrpos($permission, '.');
            $module = substr($permission, 0, $separator);
            $operation = substr($permission, $separator + 1);
            $view = $module . '.view';
            $required = $dependencies[$permission] ?? [];
            if ($operation !== 'view' && array_key_exists($view, config('admin_permissions.labels'))) $required[] = $view;
            foreach ($required as $dependency) {
                if (!in_array($dependency, $permissions, true)) $permissions[] = $dependency;
            }
        }
        return array_values(array_unique($permissions));
    }
}
