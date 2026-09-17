<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;
use Webkul\Lead\Services\DocumentExtractionService;

class DocumentExtractionServiceTest extends TestCase
{
    public function test_csv_is_extracted_as_structured_rows(): void
    {
        Storage::fake('document-tests');
        config(['document_imports.disk' => 'document-tests', 'document_imports.max_rows' => 100]);
        Storage::disk('document-tests')->put('sample.csv', "company,email\nTiara,hello@example.com\nAcme,team@example.com");

        $result = app(DocumentExtractionService::class)->extract('sample.csv', 'csv');

        $this->assertSame('bulk', $result['metadata']['mode_hint']);
        $this->assertSame(['company', 'email'], $result['tables'][0]['headers']);
        $this->assertCount(2, $result['tables'][0]['rows']);
    }

    public function test_spreadsheet_row_limit_is_enforced(): void
    {
        Storage::fake('document-tests');
        config(['document_imports.disk' => 'document-tests', 'document_imports.max_rows' => 1]);
        Storage::disk('document-tests')->put('sample.csv', "company\nOne\nTwo");

        $this->expectException(InvalidArgumentException::class);
        app(DocumentExtractionService::class)->extract('sample.csv', 'csv');
    }

    public function test_unsupported_extension_is_rejected(): void
    {
        Storage::fake('document-tests');
        config(['document_imports.disk' => 'document-tests']);
        Storage::disk('document-tests')->put('payload.php', '<?php');

        $this->expectException(InvalidArgumentException::class);
        app(DocumentExtractionService::class)->extract('payload.php', 'php');
    }
}
