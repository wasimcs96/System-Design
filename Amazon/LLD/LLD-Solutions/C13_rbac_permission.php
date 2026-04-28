<?php
/**
 * C13. RBAC — ROLE-BASED ACCESS CONTROL
 * ============================================================
 * PROBLEM: Define roles, assign permissions to roles, assign
 * roles to users, and evaluate whether a user can perform
 * an action on a resource.
 *
 * PATTERNS:
 *  - Strategy : PolicyEvaluationStrategy (deny-override / permit-override)
 *  - Composite: Roles can inherit other roles (role hierarchy)
 * ============================================================
 */

// ─── Permission ───────────────────────────────────────────────
class Permission {
    public function __construct(
        public readonly string $resource,  // e.g., 'order', 'report', 'user'
        public readonly string $action     // e.g., 'read', 'write', 'delete', '*'
    ) {}

    public function matches(string $resource, string $action): bool {
        return ($this->resource === $resource || $this->resource === '*')
            && ($this->action   === $action   || $this->action   === '*');
    }

    public function __toString(): string { return "{$this->resource}:{$this->action}"; }
}

// ─── Role (supports inheritance) ──────────────────────────────
class Role {
    /** @var Permission[] */
    private array $permissions    = [];
    /** @var Role[] */
    private array $parentRoles    = [];

    public function __construct(
        public readonly string $name,
        public readonly string $description = ''
    ) {}

    public function addPermission(Permission $p): void { $this->permissions[] = $p; }
    public function inherit(Role $role): void { $this->parentRoles[] = $role; }

    /** Get all permissions including inherited */
    public function getAllPermissions(): array {
        $all = $this->permissions;
        foreach ($this->parentRoles as $parent) {
            $all = array_merge($all, $parent->getAllPermissions());
        }
        return $all;
    }

    public function can(string $resource, string $action): bool {
        foreach ($this->getAllPermissions() as $perm) {
            if ($perm->matches($resource, $action)) return true;
        }
        return false;
    }
}

// ─── User ─────────────────────────────────────────────────────
class RBACUser {
    /** @var Role[] */
    private array $roles = [];

    public function __construct(
        public readonly string $userId,
        public readonly string $username
    ) {}

    public function assignRole(Role $role): void { $this->roles[] = $role; }
    public function getRoles(): array { return $this->roles; }

    public function can(string $resource, string $action): bool {
        foreach ($this->roles as $role) {
            if ($role->can($resource, $action)) return true;
        }
        return false;
    }

    public function getRoleNames(): string {
        return implode(', ', array_map(fn($r) => $r->name, $this->roles));
    }
}

// ─── RBAC Service ─────────────────────────────────────────────
class RBACService {
    /** @var array<string,Role> */
    private array $roles = [];
    /** @var array<string,RBACUser> */
    private array $users = [];

    public function createRole(string $name, string $desc = ''): Role {
        return $this->roles[$name] = new Role($name, $desc);
    }

    public function getRole(string $name): ?Role { return $this->roles[$name] ?? null; }

    public function createUser(string $userId, string $username): RBACUser {
        return $this->users[$userId] = new RBACUser($userId, $username);
    }

    public function check(string $userId, string $resource, string $action): bool {
        $user = $this->users[$userId] ?? null;
        if (!$user) return false;
        return $user->can($resource, $action);
    }

    public function enforce(string $userId, string $resource, string $action): void {
        $allowed = $this->check($userId, $resource, $action);
        $symbol  = $allowed ? '✓' : '✗';
        $user    = $this->users[$userId] ?? null;
        echo "  {$symbol} {$user?->username} [{$user?->getRoleNames()}] → {$resource}:{$action}\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C13. RBAC Permission System ===\n\n";

$rbac = new RBACService();

// Define roles
$viewer  = $rbac->createRole('Viewer',  'Read-only access');
$editor  = $rbac->createRole('Editor',  'Read + write');
$admin   = $rbac->createRole('Admin',   'Full access');

$viewer->addPermission(new Permission('order',  'read'));
$viewer->addPermission(new Permission('report', 'read'));

$editor->inherit($viewer);  // Editor inherits viewer permissions
$editor->addPermission(new Permission('order', 'write'));
$editor->addPermission(new Permission('order', 'update'));

$admin->addPermission(new Permission('*', '*'));  // Wildcard: all resources/actions

// Create users
$alice = $rbac->createUser('U001', 'Alice');
$bob   = $rbac->createUser('U002', 'Bob');
$carol = $rbac->createUser('U003', 'Carol');

$alice->assignRole($viewer);
$bob->assignRole($editor);
$carol->assignRole($admin);

echo "--- Permission checks ---\n";
$rbac->enforce('U001', 'order',  'read');    // ✓ viewer
$rbac->enforce('U001', 'order',  'delete');  // ✗ viewer no delete
$rbac->enforce('U002', 'order',  'read');    // ✓ editor inherits viewer
$rbac->enforce('U002', 'order',  'write');   // ✓ editor
$rbac->enforce('U002', 'user',   'delete');  // ✗ editor no user delete
$rbac->enforce('U003', 'user',   'delete');  // ✓ admin wildcard
$rbac->enforce('U003', 'report', 'export');  // ✓ admin wildcard
