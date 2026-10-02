<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use App\Services\OrganizationalScopeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationScopeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_global_roles_can_access_personnel_from_any_unit(): void
    {
        $personel = Personel::factory()->create();

        foreach ([UserRole::SystemAdmin, UserRole::AdminSsdm] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue(Gate::forUser($user)->allows('view', $personel));
        }
    }

    public function test_own_unit_scope_does_not_include_child_unit(): void
    {
        $parent = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($parent, 'parent')->create();
        $user = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($user)->for($parent)->create([
            'scope_type' => ScopeType::OwnUnit,
        ]);

        $parentPersonnel = Personel::factory()->for($parent)->create();
        $childPersonnel = Personel::factory()->for($child)->create();

        $this->assertTrue(Gate::forUser($user)->allows('view', $parentPersonnel));
        $this->assertFalse(Gate::forUser($user)->allows('view', $childPersonnel));
    }

    public function test_descendant_scope_includes_every_level_but_not_sibling_branch(): void
    {
        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create();
        $grandchild = UnitOrganisasi::factory()->for($child, 'parent')->create();
        $outside = UnitOrganisasi::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($user)->for($root)->create([
            'scope_type' => ScopeType::UnitAndDescendants,
        ]);

        foreach ([$root, $child, $grandchild] as $unit) {
            $personel = Personel::factory()->for($unit)->create();
            $this->assertTrue(Gate::forUser($user)->allows('view', $personel));
        }

        $outsidePersonnel = Personel::factory()->for($outside)->create();
        $this->assertFalse(Gate::forUser($user)->allows('view', $outsidePersonnel));
    }

    public function test_expired_and_inactive_scopes_do_not_grant_access(): void
    {
        $unit = UnitOrganisasi::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Operator]);

        UserScope::factory()->for($user)->for($unit)->create([
            'is_active' => false,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('view', Personel::factory()->for($unit)->create()));

        $userWithExpiredScope = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($userWithExpiredScope)->for($unit)->create([
            'berlaku_mulai' => today()->subYear(),
            'berlaku_sampai' => today()->subDay(),
        ]);

        $this->assertFalse(Gate::forUser($userWithExpiredScope)->allows('viewAny', Personel::class));
    }

    public function test_personnel_list_query_only_returns_units_in_operator_scope(): void
    {
        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create();
        $outside = UnitOrganisasi::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($user)->for($root)->create([
            'scope_type' => ScopeType::UnitAndDescendants,
        ]);

        $visibleIds = [
            Personel::factory()->for($root)->create()->id,
            Personel::factory()->for($child)->create()->id,
        ];
        Personel::factory()->for($outside)->create();

        $actualIds = app(OrganizationalScopeService::class)
            ->scopePersonelQuery(Personel::query(), $user)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing($visibleIds, $actualIds);
    }

    public function test_combined_scopes_include_only_active_descendants_and_owned_units(): void
    {
        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create();
        $inactiveChild = UnitOrganisasi::factory()->for($root, 'parent')->create(['is_active' => false]);
        $inactiveGrandchild = UnitOrganisasi::factory()->for($inactiveChild, 'parent')->create();
        $owned = UnitOrganisasi::factory()->create();
        $ownedChild = UnitOrganisasi::factory()->for($owned, 'parent')->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($root)->create(['scope_type' => ScopeType::UnitAndDescendants]);
        UserScope::factory()->for($operator)->for($owned)->create(['scope_type' => ScopeType::OwnUnit]);
        $scope = app(OrganizationalScopeService::class);

        $this->assertEqualsCanonicalizing([$root->id, $child->id, $owned->id], $scope->accessibleUnitIds($operator));
        $this->assertFalse($scope->canAccessUnit($operator, $inactiveChild->id));
        $this->assertFalse($scope->canAccessUnit($operator, $inactiveGrandchild->id));
        $this->assertFalse($scope->canAccessUnit($operator, $ownedChild->id));
        $this->assertEqualsCanonicalizing(
            [$root->id, $child->id, $owned->id],
            $scope->scopeUnitQuery(UnitOrganisasi::query(), $operator)->pluck('id')->all(),
        );
    }
}
