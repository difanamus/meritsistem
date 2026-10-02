<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiQualityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_consistent_401_json_without_a_token_or_json_accept_header(): void
    {
        $this->get('/api/v1/auth/me', ['Accept' => 'text/html'])
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'message' => 'Autentikasi diperlukan.']);
    }

    public function test_returns_consistent_403_json_when_role_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));

        $this->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda tidak berwenang mengakses data ini.');
    }

    public function test_returns_generic_404_json_for_an_unknown_api_path(): void
    {
        $this->getJson('/api/v1/not-an-endpoint')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Data atau endpoint tidak ditemukan.']);
    }

    public function test_returns_consistent_405_json_for_the_wrong_http_method(): void
    {
        $this->getJson('/api/v1/auth/login')
            ->assertStatus(405)
            ->assertExactJson(['success' => false, 'message' => 'Metode HTTP tidak didukung.']);
    }

    public function test_returns_field_errors_and_consistent_422_json_for_invalid_login(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_returns_generic_500_json_without_exception_detail(): void
    {
        Route::get('/api/v1/qa-unhandled-exception', function (): never {
            throw new \RuntimeException('secret-connection-string');
        });

        $response = $this->getJson('/api/v1/qa-unhandled-exception')
            ->assertInternalServerError()
            ->assertExactJson(['success' => false, 'message' => 'Terjadi kesalahan pada layanan. Silakan coba lagi.']);

        $this->assertStringNotContainsString('secret-connection-string', $response->getContent());
    }

    public function test_returns_consistent_429_json_after_login_attempt_limit(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'qa.rate.limit@example.test',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'qa.rate.limit@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429)->assertJsonPath('success', false)->assertJsonStructure(['message']);
    }

    public static function frontendOrigins(): array
    {
        return [
            'default port' => ['http://127.0.0.1:5173', 'http://localhost:5173'],
            'alternate port' => ['http://127.0.0.1:5174', 'http://localhost:5174'],
        ];
    }

    #[DataProvider('frontendOrigins')]
    public function test_preflight_allows_only_the_configured_local_frontend_origin(string $origin, string $localhostOrigin): void
    {
        config(['cors.allowed_origins' => [$origin, $localhostOrigin]]);

        $allowed = $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/v1/auth/login');

        $allowed->assertNoContent()->assertHeader('Access-Control-Allow-Origin', $origin);

        $denied = $this->withHeaders([
            'Origin' => 'http://untrusted.example.test',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/auth/login');

        $denied->assertNoContent()->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
