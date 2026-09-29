<?php
namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_lockout_five_attempts(){
    $user = User::factory()->create(['email' => 'test@test.com','password' => 'Password123']);
    
        
    for ($i=0; $i < 5 ; $i++) { 
        $response = $this->post('/login',[
        'email' => 'test@test.com',
        'password' => 'Password1']);
    }
    $response = $this->post('/login',[
        'email' => 'test@test.com',
        'password' => 'Password123']);
    $user->refresh();
    $this->assertTrue($user->failed_login_attempts == 5);
    $this->assertNotNull($user->locked_until);
    $response->assertSessionHasErrors('locked_until');
    }
}