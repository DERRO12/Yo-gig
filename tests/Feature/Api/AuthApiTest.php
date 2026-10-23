<?php

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a user signs in with email or phone and gets a token that authenticates later requests', function (string $loginField) {
    $user = User::factory()->client()->create();

    $response = $this->postJson(route('api.auth.login'), [
        'login' => $user->{$loginField},
        'password' => 'password',
    ])->assertOk()->assertJsonPath('user.role', 'client');

    $this->withToken($response->json('token'))
        ->getJson(route('api.me'))
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
})->with(['email', 'phone_number']);

test('wrong passwords are rejected', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.auth.login'), ['login' => $user->email, 'password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('login');
});

test('suspended accounts cannot sign in and lose access with an existing token', function () {
    $user = User::factory()->inactive()->create();
    $token = ApiToken::issue($user);

    $this->postJson(route('api.auth.login'), ['login' => $user->email, 'password' => 'password'])
        ->assertJsonValidationErrors('login');

    $this->withToken($token)->getJson(route('api.me'))->assertForbidden();
    expect($user->apiTokens()->count())->toBe(0);
});

test('requests without a valid token are unauthenticated', function () {
    $this->getJson(route('api.me'))->assertUnauthorized();
    $this->withToken('not-a-real-token')->getJson(route('api.me'))->assertUnauthorized();
});

test('logout revokes the token', function () {
    $user = User::factory()->create();
    $token = ApiToken::issue($user);

    $this->withToken($token)->postJson(route('api.auth.logout'))->assertOk();

    expect(ApiToken::query()->count())->toBe(0);
});

test('clients can register from the app', function () {
    $this->postJson(route('api.auth.register.client'), [
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
        'phone_number' => '+256700111222',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'role']]);

    expect(User::query()->where('email', 'jane@example.com')->value('role'))->toBe('client');
});

test('maids register with a privately stored national ID photo', function () {
    Storage::fake('local');

    $this->post(route('api.auth.register.maid'), [
        'name' => 'Grace Maid',
        'email' => 'grace@example.com',
        'phone_number' => '+256700333444',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        'national_id_number' => 'CM1234567890',
        'national_id_photo' => UploadedFile::fake()->create('id.jpg', 100, 'image/jpeg'),
        'service_area' => 'Ntinda',
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('user.maid_profile.verification_status', 'pending');

    $maid = User::query()->where('email', 'grace@example.com')->sole();
    Storage::disk('local')->assertExists($maid->maidProfile->national_id_photo_path);
});

test('users can only reach the API area for their role', function () {
    $client = User::factory()->client()->create();

    apiAs($client)->getJson(route('api.maid.dashboard'))->assertForbidden();
    apiAs($client)->getJson(route('api.admin.dashboard'))->assertForbidden();
    apiAs($client)->getJson(route('api.client.dashboard'))->assertOk();
});
