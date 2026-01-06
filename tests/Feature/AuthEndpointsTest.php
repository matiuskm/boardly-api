<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('logs in with valid credentials and returns standardized response', function () {
    $password = 'secret123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $response = $this->withoutMiddleware(VerifyCsrfToken::class)
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['user'],
            'meta' => ['request_id'],
        ]);

    expect($response->headers->get('X-Request-Id'))->not->toBeNull();
});

it('rejects invalid login attempts with error payload', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->withoutMiddleware(VerifyCsrfToken::class)
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

    $response->assertStatus(401)
        ->assertJsonStructure([
            'error' => ['message', 'code'],
            'meta' => ['request_id'],
        ]);
});

it('returns authenticated user via /api/auth/me', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure([
            'data' => ['user'],
            'meta' => ['request_id'],
        ]);
});

it('logs out and returns standardized response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['message'],
            'meta' => ['request_id'],
        ]);
});
