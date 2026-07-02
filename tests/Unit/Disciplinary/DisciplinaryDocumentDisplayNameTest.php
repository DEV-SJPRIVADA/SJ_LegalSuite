<?php

namespace Tests\Unit\Disciplinary;

use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryDocument;
use PHPUnit\Framework\TestCase;

class DisciplinaryDocumentDisplayNameTest extends TestCase
{
    public function test_evidence_image_with_hash_name_displays_as_imagen_png(): void
    {
        $doc = new DisciplinaryDocument([
            'document_type' => DocumentType::EVIDENCIA,
            'original_name' => 'j4q4hZ5nX7pZ79J8g5JlXsDPZlvfjVzhhPMHHNgJ.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertSame('Imagen.png', $doc->displayName());
    }

    public function test_friendly_evidence_image_name_for_index(): void
    {
        $this->assertSame('Imagen.png', DisciplinaryDocument::friendlyEvidenceImageNameForIndex(0, 'png'));
        $this->assertSame('Imagen-2.jpg', DisciplinaryDocument::friendlyEvidenceImageNameForIndex(1, 'jpg'));
    }
}
