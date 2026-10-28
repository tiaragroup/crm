<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('urgent_tax_invoices', function (Blueprint $table) {
            $table->unsignedInteger('source_owner_user_id')->nullable();
            $table->unsignedInteger('snapshot_version')->default(1);
            $table->char('content_sha256', 64)->nullable();
        });

        $columns = ['uuid', 'series', 'sequence_number', 'invoice_number', 'source_quote_id', 'source_quote_reference', 'source_quote_revision', 'invoice_type', 'issued_at', 'currency_code', 'seller_snapshot', 'buyer_snapshot', 'items_snapshot', 'totals_snapshot', 'qr_payload', 'issued_by_name', 'source_owner_user_id', 'snapshot_version', 'content_sha256'];
        if (DB::getDriverName() === 'mysql') {
            $changed = implode(' OR ', array_map(fn ($column) => "NOT (OLD.`$column` <=> NEW.`$column`)", $columns));
            DB::unprepared("CREATE TRIGGER urgent_invoice_immutable BEFORE UPDATE ON urgent_tax_invoices FOR EACH ROW BEGIN IF ($changed) OR (OLD.archive_status = 'ready' AND (NOT (OLD.pdf_path <=> NEW.pdf_path) OR NOT (OLD.pdf_sha256 <=> NEW.pdf_sha256) OR NOT (OLD.archived_at <=> NEW.archived_at) OR NEW.archive_status <> 'ready')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Issued invoice content is immutable'; END IF; END");
            DB::unprepared("CREATE TRIGGER urgent_invoice_no_delete BEFORE DELETE ON urgent_tax_invoices FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Issued invoices must be retained'");
            DB::unprepared("CREATE TRIGGER urgent_sequence_no_reset BEFORE UPDATE ON urgent_tax_invoice_sequences FOR EACH ROW BEGIN IF NEW.next_number < OLD.next_number OR NEW.series <> OLD.series THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invoice sequence cannot be reset'; END IF; END");
        } elseif (DB::getDriverName() === 'sqlite') {
            $changed = implode(' OR ', array_map(fn ($column) => "OLD.$column IS NOT NEW.$column", $columns));
            DB::unprepared("CREATE TRIGGER urgent_invoice_immutable BEFORE UPDATE ON urgent_tax_invoices WHEN ($changed) OR (OLD.archive_status = 'ready' AND (OLD.pdf_path IS NOT NEW.pdf_path OR OLD.pdf_sha256 IS NOT NEW.pdf_sha256 OR OLD.archived_at IS NOT NEW.archived_at OR NEW.archive_status <> 'ready')) BEGIN SELECT RAISE(ABORT, 'Issued invoice content is immutable'); END");
            DB::unprepared("CREATE TRIGGER urgent_invoice_no_delete BEFORE DELETE ON urgent_tax_invoices BEGIN SELECT RAISE(ABORT, 'Issued invoices must be retained'); END");
            DB::unprepared("CREATE TRIGGER urgent_sequence_no_reset BEFORE UPDATE ON urgent_tax_invoice_sequences WHEN NEW.next_number < OLD.next_number OR NEW.series <> OLD.series BEGIN SELECT RAISE(ABORT, 'Invoice sequence cannot be reset'); END");
        } else {
            throw new RuntimeException('Urgent invoices require MySQL or isolated SQLite tests.');
        }
    }

    public function down(): void
    {
        if (DB::table('urgent_tax_invoices')->exists()) {
            throw new RuntimeException('Cannot remove protection from issued invoices.');
        }
        foreach (['urgent_invoice_immutable', 'urgent_invoice_no_delete', 'urgent_sequence_no_reset'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        Schema::table('urgent_tax_invoices', fn (Blueprint $table) => $table->dropColumn(['source_owner_user_id', 'snapshot_version', 'content_sha256']));
    }
};
