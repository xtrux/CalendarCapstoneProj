<?php
namespace Tests\Feature;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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
public function test_registration_fails_with_duplicate_email(){
    User::factory()->create(['email' => 'test@test.com']);
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('email');
}
public function test_email_is_proper_format(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('email');
}
    public function test_pw_length(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Pw1',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('password');
    }
    public function test_pw_uppercase(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'password123',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasErrors('password');
    }
    
    public function test_password_requirements(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',]);
        $response->assertSessionHasNoErrors();
    }
    public function test_password_is_hashed(){
    $response = $this->post('/register',[
        'username' => 'xtrux',
        'email' => 'test@test.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',]);
        $user = User::where('username','xtrux')->first();
        $this->assertNotEquals('Password123',$user->password);
        $this->assertTrue(Hash::check('Password123', $user->password));
    }
     

}   

