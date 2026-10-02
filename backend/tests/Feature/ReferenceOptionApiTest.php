<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\Pangkat;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferenceOptionApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reference_options_are_authenticated_and_units_follow_operator_scope(): void
    {
        $this->getJson('/api/v1/reference-options')->assertUnauthorized();

        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create();
        UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($root)->create([
            'scope_type' => ScopeType::UnitAndDescendants,
        ]);
        Pangkat::factory()->create();
        BidangFungsi::factory()->create();
        JenisKualifikasi::factory()->create();
        JenisPenugasan::factory()->create();
        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/reference-options')
            ->assertOk()
            ->assertJsonCount(2, 'data.unit_organisasi')
            ->assertJsonFragment(['id' => $root->id])
            ->assertJsonFragment(['id' => $child->id])
            ->assertJsonCount(1, 'data.pangkat')
            ->assertJsonCount(1, 'data.bidang_fungsi')
            ->assertJsonCount(1, 'data.jenis_kualifikasi')
            ->assertJsonCount(1, 'data.jenis_penugasan');
    }

    public function test_function_only_options_do_not_include_the_organization_tree(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        $function = BidangFungsi::factory()->create();
        UnitOrganisasi::factory()->count(3)->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/reference-options?only=bidang_fungsi')
            ->assertOk()
            ->assertJsonPath('data.bidang_fungsi.0.id', $function->id)
            ->assertJsonMissingPath('data.unit_organisasi')
            ->assertJsonMissingPath('data.pangkat');

        $this->getJson('/api/v1/reference-options?only=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('only');
    }

    public function test_non_unit_options_do_not_load_units_but_keep_form_references(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        UnitOrganisasi::factory()->count(3)->create();
        Pangkat::factory()->create();
        BidangFungsi::factory()->create();
        JenisKualifikasi::factory()->create();
        JenisPenugasan::factory()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/reference-options?only=non_unit')
            ->assertOk()
            ->assertJsonMissingPath('data.unit_organisasi')
            ->assertJsonCount(1, 'data.pangkat')
            ->assertJsonCount(1, 'data.bidang_fungsi')
            ->assertJsonCount(1, 'data.jenis_kualifikasi')
            ->assertJsonCount(1, 'data.jenis_penugasan');
    }

    public function test_unit_search_is_scoped_and_limited_to_twenty_five_results(): void
    {
        $root = UnitOrganisasi::factory()->create(['nama' => 'Polda Papua']);
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create(['nama' => 'Polres Papua Tengah']);
        UnitOrganisasi::factory()->create(['nama' => 'Polres Papua Luar']);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($root)->create(['scope_type' => ScopeType::UnitAndDescendants]);
        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/reference-options?only=unit_organisasi&search=papua')
            ->assertOk()
            ->assertJsonCount(2, 'data.unit_organisasi')
            ->assertJsonFragment(['id' => $root->id])
            ->assertJsonFragment(['id' => $child->id]);

        $this->getJson('/api/v1/reference-options?only=unit_organisasi&search=p')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');

        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        UnitOrganisasi::factory()->count(30)->create(['nama' => 'Satker Uji']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/reference-options?only=unit_organisasi&search=Satker')
            ->assertOk()
            ->assertJsonCount(25, 'data.unit_organisasi');
    }
}
