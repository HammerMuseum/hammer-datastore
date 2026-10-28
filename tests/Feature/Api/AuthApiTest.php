<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function testDeleteRequiresApiToken()
    {
        $this->deleteJson('/api/videos/2000')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unable to authenticate request. API token missing or invalid');
    }

    public function testLoginRequiresCredentials()
    {
        $this->postJson('/api/login')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
