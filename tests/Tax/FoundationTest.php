<?php

namespace Tests\Tax;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Tax\Models\TaxDocument;
use Webkul\Tax\Models\TaxDocumentProxy;
use Webkul\Tax\Services\DraftLineNormalizer;
use Webkul\Tax\Services\DraftService;
use Webkul\Tax\Services\SellerService;

class FoundationTest extends TaxTestCase
{
    public function test_migrations_and_concord_registration(): void
    {
        foreach (['tax_sellers', 'tax_document_sequences', 'tax_documents', 'tax_document_items', 'payments', 'payment_allocations', 'tax_document_links', 'tax_document_artifacts', 'tax_audit_events'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertFalse(Schema::hasTable('zatca_devices'));
        $this->assertFalse(Schema::hasTable('zatca_submissions'));
        $this->assertSame(TaxDocument::class, TaxDocumentProxy::modelClass());
        $this->assertInstanceOf(TaxDocument::class, app(\Webkul\Tax\Contracts\TaxDocument::class));
        foreach (DB::select('PRAGMA foreign_key_list(tax_documents)') as $foreign) {
            $this->assertSame('RESTRICT', $foreign->on_delete);
        }
    }

    public function test_unpaid_draft_creation_is_unissued_and_audited(): void
    {
        $input = $this->draftInput();
        $input['status'] = 'issued';
        $input['document_number'] = 'FORGED';
        $input['vat_total'] = '999';
        $document = app(DraftService::class)->save($input, $this->actor);
        $this->assertSame('draft', $document->status);
        $this->assertNull($document->document_number);
        $this->assertNull($document->issued_at);
        $this->assertNull($document->vat_total);
        $this->assertSame('0.100000', $document->items->first()->unit_price);
        $this->assertSame('15.000000', $document->items->first()->tax_rate);
        $this->assertSame($document->seller_id, $document->seller->id);
        $this->assertSame($this->actor->id, $document->creator->id);
        $this->assertSame('draft.created', $document->events->first()->event);
        $this->assertSame(0, DB::table('payments')->count());
        $this->assertSame(0, DB::table('tax_document_sequences')->count());
    }

    public function test_create_replay_is_idempotent_and_changed_payload_conflicts(): void
    {
        $input = $this->draftInput();
        $service = app(DraftService::class);
        $first = $service->save($input, $this->actor);
        $this->assertSame($first->id, $service->save($input, $this->actor)->id);
        $this->assertSame(1, DB::table('tax_documents')->count());
        $this->assertSame(1, $first->events()->count());
        $this->postJson(route('admin.tax_documents.store'), array_replace($input, ['subject' => 'Different']))->assertStatus(409);
    }

    public function test_edit_checks_version_and_preserves_snapshot_history(): void
    {
        $input = $this->draftInput();
        $service = app(DraftService::class);
        $document = $service->save($input, $this->actor);
        $input['lock_version'] = 1;
        $input['subject'] = 'Updated';
        $updated = $service->save($input, $this->actor, $document->id);
        $this->assertSame(2, $updated->lock_version);
        $this->assertSame(2, $updated->events()->count());
        $this->putJson(route('admin.tax_documents.update', $document->id), $input)->assertStatus(409);
    }

    public function test_inactive_seller_and_invalid_vat_are_rejected(): void
    {
        $this->postJson(route('admin.tax_sellers.store'), array_replace($this->sellerInput(), ['vat_number' => '123']))->assertUnprocessable()->assertJsonValidationErrors('vat_number');
        $input = $this->draftInput();
        DB::table('tax_sellers')->where('id', $input['seller_id'])->update(['is_active' => false]);
        $this->postJson(route('admin.tax_documents.store'), $input)->assertUnprocessable();
        $this->assertSame(0, DB::table('tax_documents')->count());
    }

    public function test_seller_edit_does_not_mutate_existing_draft_snapshot(): void
    {
        $input = $this->draftInput();
        $document = app(DraftService::class)->save($input, $this->actor);
        app(SellerService::class)->save(array_replace($this->sellerInput(), ['legal_name_ar' => 'اسم جديد', 'lock_version' => 1]), $this->actor, $document->seller_id);
        $this->assertSame('شركة تيارا', $document->fresh()->seller_snapshot['legal_name_ar']);
        $this->assertSame(2, DB::table('tax_audit_events')->whereNull('tax_document_id')->count());
    }

    public function test_decimal_normalization_never_uses_float_rounding(): void
    {
        $lines = app(DraftLineNormalizer::class)->normalize([['quantity' => '123456789012.123456', 'unit_price' => '0.100001', 'discount_amount' => '0.01']]);
        $this->assertSame('123456789012.123456', $lines[0]['quantity']);
        $this->assertSame('0.100001', $lines[0]['unit_price']);
        $this->assertSame('0.01', $lines[0]['discount_amount']);
        $input = $this->draftInput();
        $input['items'][0]['unit_price'] = '1e5';
        $this->postJson(route('admin.tax_documents.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_inconsistent_tax_category_is_rejected(): void
    {
        $input = $this->draftInput();
        $input['items'][0]['tax_category'] = 'E';
        $this->postJson(route('admin.tax_documents.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('items.0.tax_rate');
        $input['items'][0]['tax_rate'] = '0';
        $this->postJson(route('admin.tax_documents.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('items.0.exemption_reason');
    }

    public function test_draft_can_keep_tax_treatment_unclassified(): void
    {
        $input = $this->draftInput();
        unset($input['items'][0]['tax_category'], $input['items'][0]['tax_rate']);
        $document = app(DraftService::class)->save($input, $this->actor);
        $this->assertNull($document->items->first()->tax_category);
        $this->assertNull($document->tax_inclusive_total);
    }

    public function test_document_and_audit_deletion_are_blocked(): void
    {
        $document = app(DraftService::class)->save($this->draftInput(), $this->actor);
        try {
            $document->events->first()->delete();
            $this->fail('Audit deletion allowed');
        } catch (\DomainException) {
        }
        $this->expectException(\DomainException::class);
        $document->delete();
    }

    public function test_existing_issued_document_cannot_be_edited(): void
    {
        $input = $this->draftInput();
        $document = app(DraftService::class)->save($input, $this->actor);
        // Simulate a future issued record without adding an issuance path.
        DB::table('tax_documents')->where('id', $document->id)->update(['status' => 'issued']);
        $this->putJson(route('admin.tax_documents.update', $document->id), array_replace($input, ['lock_version' => 1]))->assertStatus(409);
        $document = $document->fresh();
        $document->subject = 'Forged';
        $this->expectException(\DomainException::class);
        $document->save();
    }

    public function test_populated_migration_rollback_is_refused(): void
    {
        $this->draftInput();
        $migration = require base_path('packages/Webkul/Tax/src/Database/Migrations/2026_10_28_000001_create_tax_foundation_tables.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_foreign_key_prevents_seller_deletion(): void
    {
        $document = app(DraftService::class)->save($this->draftInput(), $this->actor);
        $this->expectException(QueryException::class);
        DB::table('tax_sellers')->where('id', $document->seller_id)->delete();
    }
}
