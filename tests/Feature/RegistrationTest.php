<?php
namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    // i used claude to write this first test case to see how they work
    public function test_registration_fails_with_duplicate_username(){
    User::factory()->create(['username' => 'xtrux']);
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('username');
}
    public function test_pw_length(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Pw1',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('password');
    }


}

