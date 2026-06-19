<?php

namespace Tests\Feature\Disciplinary;

use App\Livewire\Disciplinary\FormatsCatalog;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Settings\OrganizationLetterheadService;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationLetterheadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_admin_can_upload_letterhead_from_formats_catalog(): void
    {
        $admin = User::factory()->create([
            'email' => 'letterhead-admin@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->image('membrete-empresa.png', 850, 1100);

        Livewire::actingAs($admin)
            ->test(FormatsCatalog::class)
            ->set('letterheadFile', $file)
            ->call('uploadLetterhead')
            ->assertHasNoErrors();

        $service = app(OrganizationLetterheadService::class);
        $this->assertTrue($service->hasImage());
        $this->assertNotNull($service->imageDataUri());
        $this->assertSame('membrete-empresa.png', $service->originalFileName());
    }

    public function test_lawyer_without_assign_permission_cannot_upload_letterhead(): void
    {
        $lawyer = User::factory()->create([
            'email' => 'letterhead-lawyer@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('abogado');

        $file = UploadedFile::fake()->image('membrete.png');

        Livewire::actingAs($lawyer)
            ->test(FormatsCatalog::class)
            ->set('letterheadFile', $file)
            ->call('uploadLetterhead')
            ->assertForbidden();
    }

    public function test_letterhead_preview_route_requires_configured_image(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('disciplinary.formats.letterhead'))
            ->assertNotFound();
    }

    public function test_letterhead_preview_route_returns_image_when_configured(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        app(OrganizationLetterheadService::class)->storeImage(
            UploadedFile::fake()->image('membrete.png', 200, 260),
        );

        $this->actingAs($admin)
            ->get(route('disciplinary.formats.letterhead'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_formats_page_shows_letterhead_section_for_authorized_user(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(FormatsCatalog::class)
            ->assertSee('Membrete · Acta de comité disciplinario')
            ->assertSee('Cargar imagen membrete');
    }

    public function test_formats_catalog_lists_comite_acta_blank_pdf(): void
    {
        $this->assertTrue(OfficialFormsCatalog::hasBlankPdf('ACTA-COMITE'));

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(FormatsCatalog::class)
            ->assertSee('ACTA-COMITE')
            ->assertSee('Comité disciplinario para decisión')
            ->assertSee('Ver plantilla PDF')
            ->call('openFormPreview', 'ACTA-COMITE')
            ->assertSet('activeFormPreview', 'ACTA-COMITE');
    }

    public function test_manage_official_letterhead_policy(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $lawyer = User::factory()->create(['is_active' => true]);
        $lawyer->assignRole('abogado');

        $this->assertTrue($admin->can('manageOfficialLetterhead', DisciplinaryCase::class));
        $this->assertFalse($lawyer->can('manageOfficialLetterhead', DisciplinaryCase::class));
    }
}
