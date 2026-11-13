<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Facades\GenerateSignedUploadUrlFacade;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Tests\TestCase;

/**
 * Regression tests for the November 2026 incident, where a bot obtained a signed
 * upload URL from the public admin login component and stored PHP files in livewire-tmp.
 */
class LivewireUploadHardeningTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(FileUploadConfiguration::disk());
    }

    private function signedUploadUrl(): string
    {
        return GenerateSignedUploadUrlFacade::forLocal();
    }

    /**
     * @return list<string>
     */
    private function storedTemporaryFiles(): array
    {
        return FileUploadConfiguration::storage()->allFiles(FileUploadConfiguration::path());
    }

    public function test_guest_cannot_upload_even_with_a_valid_signed_url(): void
    {
        $response = $this->post($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')],
        ]);

        $response->assertRedirect('/admin/login');
        $this->assertSame([], $this->storedTemporaryFiles());
    }

    public function test_guest_json_upload_is_rejected_as_unauthenticated(): void
    {
        $response = $this->postJson($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('probe.php', 1, 'application/pdf')],
        ]);

        $response->assertUnauthorized();
        $this->assertSame([], $this->storedTemporaryFiles());
    }

    public function test_authenticated_user_cannot_upload_php_file_disguised_as_pdf(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('probe.php', 1, 'application/pdf')],
        ]);

        $response->assertUnprocessable();
        $this->assertSame([], $this->storedTemporaryFiles());
    }

    public function test_authenticated_user_cannot_upload_php_file_disguised_as_image(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('shell.php', 1, 'image/jpeg')],
        ]);

        $response->assertUnprocessable();
        $this->assertSame([], $this->storedTemporaryFiles());
    }

    public function test_authenticated_user_cannot_upload_non_image_file(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')],
        ]);

        $response->assertUnprocessable();
        $this->assertSame([], $this->storedTemporaryFiles());
    }

    public function test_authenticated_user_can_upload_an_image(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson($this->signedUploadUrl(), [
            'files' => [UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')],
        ]);

        $response->assertOk()->assertJsonStructure(['paths']);
        $this->assertCount(2, $this->storedTemporaryFiles());
    }
}
