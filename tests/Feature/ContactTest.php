<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_contact_page_returns_ok(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
    }

    public function test_contact_page_renders_inertia_contact_component(): void
    {
        $response = $this->get('/contact');

        $response->assertInertia(fn ($page) => $page->component('Contact'));
    }

    public function test_contact_form_stores_submission(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, I would like to work with you on a project.',
        ]);

        $response->assertRedirect('/contact');
        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_contact_form_sets_success_flash(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'I have a project I would like to discuss with you.',
        ]);

        $response->assertSessionHas('success', true);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post('/contact', []);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_contact_form_validates_email_format(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'message' => 'This is a valid message with enough characters.',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_contact_form_validates_message_minimum_length(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'Short',
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_contact_submission_is_unread_by_default(): void
    {
        $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, this is a longer message for testing purposes.',
        ]);

        $submission = ContactSubmission::first();
        $this->assertNull($submission->read_at);
    }
}
