<?php

namespace Webkul\Lead\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Lead\Models\DocumentImport;
use Webkul\Lead\Services\DocumentAIService;
use Webkul\Lead\Services\DocumentExtractionService;

class ProcessDocumentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $documentImportId) {}

    public function handle(DocumentExtractionService $extractor, DocumentAIService $ai): void
    {
        $import = DocumentImport::findOrFail($this->documentImportId);
        $import->update([
            'status'         => DocumentImport::STATUS_EXTRACTING,
            'error_message'  => null,
            'ai_model'       => config('document_imports.model'),
            'ai_provider'    => config('document_imports.provider'),
        ]);

        try {
            $document = $extractor->extract($import->stored_path, $import->extension);

            // Persist local extraction before calling the external AI provider. This
            // keeps the audit trail available if the provider is unavailable.
            $import->update([
                'status'        => DocumentImport::STATUS_EXTRACTED,
                'raw_text'      => $document['text'],
                'document_type' => $document['type'],
            ]);

            $data = $ai->analyze($document);
            $data['_document_metadata'] = $document['metadata'] ?? [];

            $import->update([
                'status'         => DocumentImport::STATUS_REVIEW,
                'document_type'  => $data['document_type'] ?? $document['type'],
                'extracted_data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('Document import processing failed', ['id' => $import->id, 'exception' => $e]);
            $import->update(['status' => DocumentImport::STATUS_FAILED, 'error_message' => $this->safeMessage($e)]);
        }
    }

    private function safeMessage(Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if ($e instanceof RequestException) {
            $status = $e->response->status();
            $code = $e->response->json('error.type') ?? $e->response->json('error.code');

            if ($status === 401) {
                return trans('admin::app.document-imports.messages.ai-invalid-key');
            }

            if ($status === 429) {
                if (in_array($code, ['billing_error', 'credit_balance_exhausted', 'insufficient_quota'], true)
                    || str_contains($message, 'no credits')
                    || str_contains($message, 'credit balance')
                    || str_contains($message, 'quota')) {
                    return trans('admin::app.document-imports.messages.ai-no-credits');
                }

                return trans('admin::app.document-imports.messages.ai-rate-limit');
            }

            if (in_array($status, [400, 404], true) && str_contains($message, 'model')) {
                return trans('admin::app.document-imports.messages.ai-model');
            }
        }

        if ($e instanceof ConnectionException) {
            return trans('admin::app.document-imports.messages.ai-connection');
        }

        if (str_contains($message, 'scan')) {
            return $e->getMessage();
        }

        return trans('admin::app.document-imports.messages.processing-failed');
    }
}
