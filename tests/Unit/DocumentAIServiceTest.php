<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Webkul\Lead\Services\DocumentAIService;

class DocumentAIServiceTest extends TestCase
{
    public function test_anthropic_call_is_mocked_and_structured_response_is_validated(): void
    {
        config([
            'document_imports.api_key'     => 'test-key',
            'document_imports.endpoint'    => 'https://anthropic.test/v1/messages',
            'document_imports.api_version' => '2023-06-01',
            'document_imports.model'       => 'claude-sonnet-4-6',
            'document_imports.max_tokens'  => 8192,
        ]);

        Http::fake(['anthropic.test/*' => Http::response([
            'content'     => [['type' => 'text', 'text' => json_encode(['mode' => 'single', 'company' => ['name' => 'Tiara']])]],
            'stop_reason' => 'end_turn',
        ])]);

        $result = app(DocumentAIService::class)->analyze(['type' => 'pdf', 'text' => 'Tiara']);

        $this->assertSame('Tiara', $result['company']['name']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://anthropic.test/v1/messages'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request['model'] === 'claude-sonnet-4-6'
                && $request['max_tokens'] === 8192
                && isset($request['system'])
                && ! isset($request['response_format']);
        });
    }

    public function test_anthropic_markdown_json_response_is_tolerated(): void
    {
        config([
            'document_imports.api_key'  => 'test-key',
            'document_imports.endpoint' => 'https://anthropic.test/v1/messages',
        ]);

        Http::fake(['anthropic.test/*' => Http::response([
            'content' => [[
                'type' => 'text',
                'text' => "```json\n{\"mode\":\"single\",\"company\":{\"name\":\"Tiara\"}}\n```",
            ]],
        ])]);

        $result = app(DocumentAIService::class)->analyze(['type' => 'pdf', 'text' => 'Tiara']);

        $this->assertSame('Tiara', $result['company']['name']);
    }

    public function test_missing_fields_are_normalized_to_null(): void
    {
        $result = app(DocumentAIService::class)->validate(['mode' => 'single', 'company' => ['name' => 'Tiara']]);

        $this->assertSame('Tiara', $result['company']['name']);
        $this->assertNull($result['company']['vat_number']);
        $this->assertNull($result['contact']['email']);
    }

    public function test_invalid_nested_shape_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        app(DocumentAIService::class)->validate(['mode' => 'single', 'company' => 'fabricated']);
    }

    public function test_invalid_confidence_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        app(DocumentAIService::class)->validate(['mode' => 'single', 'confidence' => ['email' => 4]]);
    }

    public function test_bulk_rows_respect_configured_limit(): void
    {
        config(['document_imports.max_rows' => 2]);
        $this->expectException(RuntimeException::class);
        app(DocumentAIService::class)->validate(['mode' => 'bulk', 'rows' => [[], [], []]]);
    }
}
