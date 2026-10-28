<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_sellers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('legal_name_ar');
            $table->string('legal_name_en')->nullable();
            $table->string('vat_number', 15);
            $table->string('registration_scheme', 10)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->string('branch_name')->nullable();
            $table->json('address');
            $table->char('currency_code', 3)->default('SAR');
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->unsignedInteger('lock_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('tax_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('tax_sellers')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('prefix', 40);
            $table->unsignedBigInteger('next_value')->default(1);
            $table->unsignedTinyInteger('padding')->default(10);
            $table->timestamps();
            $table->unique(['seller_id', 'code']);
        });

        Schema::create('tax_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('create_request_hash', 64);
            $table->foreignId('seller_id')->constrained('tax_sellers')->restrictOnDelete();
            $table->foreignId('sequence_id')->nullable()->constrained('tax_document_sequences')->restrictOnDelete();
            $table->unsignedBigInteger('sequence_number')->nullable();
            $table->string('document_number', 80)->nullable();
            $table->string('document_type', 20)->default('invoice');
            $table->string('purpose', 20)->default('ordinary');
            $table->string('profile', 20);
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedInteger('quote_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('created_by');
            // Existing Krayin IDs are unsigned INT, not BIGINT. Never cascade financial data.
            foreach (['quote_id' => 'quotes', 'person_id' => 'persons', 'organization_id' => 'organizations', 'created_by' => 'users'] as $column => $target) {
                $table->foreign($column)->references('id')->on($target)->restrictOnDelete();
            }
            $table->string('source_reference')->nullable();
            $table->unsignedInteger('source_revision')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->char('source_fingerprint', 64)->nullable();
            $table->string('subject');
            $table->json('seller_snapshot');
            $table->json('buyer_snapshot');
            $table->char('currency_code', 3)->default('SAR');
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->date('supply_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('issued_at', 6)->nullable();
            $table->unsignedInteger('issued_by')->nullable();
            $table->foreign('issued_by')->references('id')->on('users')->restrictOnDelete();
            foreach (['line_net_total', 'allowance_total', 'charge_total', 'tax_exclusive_total', 'vat_total', 'tax_inclusive_total', 'prepaid_amount', 'rounding_amount', 'payable_amount'] as $column) {
                $table->decimal($column, 20, 2)->nullable();
            }
            $table->json('tax_breakdown')->nullable();
            $table->unsignedInteger('snapshot_version')->nullable();
            $table->json('immutable_snapshot')->nullable();
            $table->char('content_digest', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['seller_id', 'document_number']);
            $table->unique(['sequence_id', 'sequence_number']);
            $table->index(['created_by', 'created_at']);
        });

        Schema::create('tax_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_document_id')->constrained('tax_documents')->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            // Provenance only: quote edits may delete/recreate source lines.
            $table->unsignedInteger('source_quote_item_id')->nullable();
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->string('sku')->nullable();
            $table->string('unit_code', 10)->default('C62');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->string('price_basis', 20)->default('exclusive');
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->string('tax_category', 20)->nullable();
            $table->decimal('tax_rate', 9, 6)->nullable();
            $table->string('exemption_code', 80)->nullable();
            $table->string('exemption_reason')->nullable();
            foreach (['net_amount', 'taxable_amount', 'vat_amount', 'gross_amount'] as $column) {
                $table->decimal($column, 20, 2)->nullable();
            }
            $table->json('source_metadata')->nullable();
            $table->timestamps();
            $table->unique(['tax_document_id', 'line_number']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seller_id')->constrained('tax_sellers')->restrictOnDelete();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('quote_id')->nullable();
            $table->unsignedInteger('created_by');
            foreach (['person_id' => 'persons', 'organization_id' => 'organizations', 'quote_id' => 'quotes', 'created_by' => 'users'] as $column => $target) {
                $table->foreign($column)->references('id')->on($target)->restrictOnDelete();
            }
            $table->string('direction', 20)->default('receipt');
            $table->string('purpose', 30)->default('payment');
            $table->string('status', 20)->default('draft');
            $table->decimal('amount', 20, 2);
            $table->char('currency_code', 3)->default('SAR');
            $table->dateTime('received_at', 6)->nullable();
            $table->dateTime('posted_at', 6)->nullable();
            $table->string('method', 30)->nullable();
            $table->string('external_reference')->nullable();
            $table->json('payer_snapshot')->nullable();
            $table->foreignId('original_payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('payments')->restrictOnDelete();
            $table->string('idempotency_key', 80)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['seller_id', 'idempotency_key']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('tax_document_id')->constrained('tax_documents')->restrictOnDelete();
            $table->decimal('amount', 20, 2);
            $table->char('currency_code', 3);
            $table->string('entry_type', 20)->default('apply');
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('payment_allocations')->restrictOnDelete();
            $table->unsignedInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->uuid('operation_uuid')->unique();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_document_id')->constrained('tax_documents')->restrictOnDelete();
            $table->foreignId('referenced_document_id')->constrained('tax_documents')->restrictOnDelete();
            $table->string('relation', 30);
            $table->decimal('net_amount', 20, 2)->nullable();
            $table->decimal('vat_amount', 20, 2)->nullable();
            $table->decimal('gross_amount', 20, 2)->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->json('reference_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['tax_document_id', 'referenced_document_id', 'relation'], 'tax_doc_link_unique');
        });

        Schema::create('tax_document_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_document_id')->constrained('tax_documents')->restrictOnDelete();
            $table->string('kind', 30);
            $table->unsignedInteger('version');
            $table->string('disk', 80);
            $table->string('path');
            $table->string('mime_type', 100);
            $table->char('sha256', 64);
            $table->unsignedBigInteger('byte_size');
            $table->string('renderer_version', 80)->nullable();
            $table->timestamps();
            $table->unique(['tax_document_id', 'kind', 'version'], 'tax_artifact_version_unique');
        });

        Schema::create('tax_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->nullable()->constrained('tax_sellers')->restrictOnDelete();
            $table->foreignId('tax_document_id')->nullable()->constrained('tax_documents')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->unsignedInteger('actor_id');
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('event', 80);
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Financial foundation rollback is only safe on an empty installation.
        foreach (['tax_documents', 'payments', 'tax_audit_events', 'tax_sellers'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Refusing to drop populated tax records. Restore through an approved recovery procedure.');
            }
        }

        foreach (['tax_audit_events', 'tax_document_artifacts', 'tax_document_links', 'payment_allocations', 'payments', 'tax_document_items', 'tax_documents', 'tax_document_sequences', 'tax_sellers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
