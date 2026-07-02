<?php

namespace App\Models\Traits;

trait HasRoles
{
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'voir vehicules',
            'ajouter vehicule',
            'modifier vehicule',
            'supprimer vehicule',
            'gerer utilisateurs',
            'gerer roles',
            'voir rapports',
        ],
        'gestionnaire' => [
            'voir vehicules',
            'ajouter vehicule',
            'modifier vehicule',
            'voir rapports',
        ],
    ];

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $userRole = $this->normalizedRole();

        foreach ($roles as $role) {
            if ($userRole === $this->normalizeRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermissionTo(string $permission): bool
    {
        $role = $this->normalizedRole();

        return in_array($permission, self::ROLE_PERMISSIONS[$role] ?? [], true);
    }

    public function normalizedRole(): string
    {
        return $this->normalizeRole((string) $this->role);
    }

    private function normalizeRole(string $role): string
    {
        return strtolower(trim($role));
    }
}
