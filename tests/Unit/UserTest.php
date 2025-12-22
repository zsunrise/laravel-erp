<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_roles()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'admin']);

        $user->roles()->attach($role);

        $this->assertTrue($user->roles->contains($role));
        $this->assertEquals(1, $user->roles->count());
    }

    public function test_user_has_role_by_slug()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'admin']);

        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('manager'));
    }

    public function test_user_has_role_by_model()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'admin']);

        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole($role));
    }

    public function test_user_has_permission_through_role()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'admin']);
        $permission = \App\Models\Permission::factory()->create(['slug' => 'users.create']);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    public function test_user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['slug' => 'admin']);
        $role2 = Role::factory()->create(['slug' => 'manager']);

        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertEquals(2, $user->roles->count());
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('manager'));
    }
}

