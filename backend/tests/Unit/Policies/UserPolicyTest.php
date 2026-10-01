<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    /** @return array<string, array{UserRole, UserRole, bool}> */
    public static function viewMatrix(): array
    {
        return [
            'system admin views system admin' => [UserRole::SystemAdmin, UserRole::SystemAdmin, true],
            'system admin views admin ssdm' => [UserRole::SystemAdmin, UserRole::AdminSsdm, true],
            'system admin views operator' => [UserRole::SystemAdmin, UserRole::Operator, true],
            'admin ssdm cannot view system admin' => [UserRole::AdminSsdm, UserRole::SystemAdmin, false],
            'admin ssdm cannot view admin ssdm' => [UserRole::AdminSsdm, UserRole::AdminSsdm, false],
            'admin ssdm views operator' => [UserRole::AdminSsdm, UserRole::Operator, true],
            'operator cannot view operator' => [UserRole::Operator, UserRole::Operator, false],
        ];
    }

    #[DataProvider('viewMatrix')]
    public function test_view_permission_matches_role_matrix(UserRole $actorRole, UserRole $targetRole, bool $expected): void
    {
        $actor = User::factory()->make(['id' => 1, 'role' => $actorRole]);
        $target = User::factory()->make(['id' => 2, 'role' => $targetRole]);

        $actual = (new UserPolicy)->view($actor, $target);

        $this->assertSame($expected, $actual);
    }

    public function test_no_role_can_update_or_delete_its_own_account(): void
    {
        $policy = new UserPolicy;

        foreach ([UserRole::SystemAdmin, UserRole::AdminSsdm, UserRole::Operator] as $role) {
            $actor = User::factory()->make(['id' => 1, 'role' => $role]);

            $this->assertFalse($policy->update($actor, $actor));
            $this->assertFalse($policy->delete($actor, $actor));
        }
    }

    public function test_inactive_actor_is_rejected_before_other_permissions(): void
    {
        $actor = User::factory()->make([
            'id' => 1,
            'role' => UserRole::SystemAdmin,
            'is_active' => false,
        ]);

        $this->assertFalse((new UserPolicy)->before($actor));
    }
}
