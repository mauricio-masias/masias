<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_admin_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_accessible_when_authenticated(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_works_list_accessible_when_authenticated(): void
    {
        $user = $this->adminUser();
        Work::factory(3)->create(['published_at' => now()]);

        $response = $this->actingAs($user)->get('/admin/works');

        $response->assertStatus(200);
    }

    public function test_contact_submissions_list_accessible_when_authenticated(): void
    {
        $user = $this->adminUser();
        ContactSubmission::factory(2)->create();

        $response = $this->actingAs($user)->get('/admin/contact-submissions');

        $response->assertStatus(200);
    }

    public function test_manage_homepage_accessible_when_authenticated(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get('/admin/manage-homepage');

        $response->assertStatus(200);
    }

    public function test_admin_register_page_is_accessible(): void
    {
        $response = $this->get('/admin/register');

        $response->assertStatus(200);
    }
}
