<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Invoice numbering sequence
        Schema::create('urgent_tax_invoice_sequences', function (Blueprint $table) {
            $table->id();

            $table->string('series', 20)->unique();

            $table->unsignedBigInteger('next_number')->default(1);

            $table->timestamps();
        });

        // Initialize the invoice series.
        DB::table('urgent_tax_invoice_sequences')->insert([
            'series'      => 'TI',
            'next_number' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 2. Issued tax invoices
        Schema::create('urgent_tax_invoices', function (Blueprint $table) {
            $table->id();

            $table->char('uuid', 36)->unique();

            // Legal invoice identity
            $table->string('series', 20);
            $table->unsignedBigInteger('sequence_number');
            $table->string('invoice_number', 40)->unique();

            // One invoice per original proposal.
            // This ID is preserved even if the proposal is deleted.
            $table->unsignedInteger('source_quote_id')->unique();

            // Optional live link to the original proposal.
            $table->unsignedInteger('quote_id')->nullable();

            $table->string('source_quote_reference')->nullable();
            $table->unsignedInteger('source_quote_revision')->nullable();

            // standard or simplified
            $table->string('invoice_type', 20);

            // Stored in UTC, presented using Asia/Riyadh
            $table->dateTime('issued_at');
            $table->string('currency_code', 3)->default('SAR');

            // Frozen legal invoice data
            $table->json('seller_snapshot');
            $table->json('buyer_snapshot');
            $table->json('items_snapshot');
            $table->json('totals_snapshot');

            // ZATCA Phase 1 QR data
            $table->text('qr_payload')->nullable();

            // Archived PDF
            $table->string('pdf_path')->nullable();
            $table->char('pdf_sha256', 64)->nullable();

            // pending, ready, failed
            $table->string('archive_status', 20)->default('pending');
            $table->dateTime('archived_at')->nullable();

            // Audit
            $table->unsignedInteger('issued_by_user_id')->nullable();
            $table->string('issued_by_name')->nullable();

            $table->timestamps();

            $table->unique(
                ['series', 'sequence_number'],
                'urgent_tax_invoice_series_number_unique'
            );

            // Proposal deletion must not delete an issued invoice.
            $table->foreign('quote_id', 'urgent_tax_invoice_quote_fk')
                ->references('id')
                ->on('quotes')
                ->nullOnDelete();

            // Preserve issuer name even if the user is removed.
            $table->foreign('issued_by_user_id', 'urgent_tax_invoice_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Never accidentally destroy issued financial records.
        if (
            Schema::hasTable('urgent_tax_invoices')
            && DB::table('urgent_tax_invoices')->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back: issued tax invoices exist.'
            );
        }

        Schema::dropIfExists('urgent_tax_invoices');
        Schema::dropIfExists('urgent_tax_invoice_sequences');
    }
};
