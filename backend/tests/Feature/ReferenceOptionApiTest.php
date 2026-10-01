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
}
