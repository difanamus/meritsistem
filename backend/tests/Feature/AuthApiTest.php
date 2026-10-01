<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_user_can_login_and_receive_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.test',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'phpunit',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif.');
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_authenticated_active_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', $user->role->value);
    }

    public function test_deactivated_user_with_existing_token_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif.');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('phpunit')->plainTextToken;
        $tokenId = (int) str($plainTextToken)->before('|')->value();

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->assertNull(PersonalAccessToken::find($tokenId));
    }
}
