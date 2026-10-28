<?php

namespace Tests\Tax;

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Tax\Models\Payment;
use Webkul\Tax\Models\PaymentAllocation;
use Webkul\Tax\Models\TaxDocumentArtifact;
use Webkul\Tax\Models\TaxDocumentLink;
use Webkul\Tax\Models\TaxDocumentSequence;

class MigrationSafetyTest extends TaxTestCase
{
    public function test_mysql_ddl_compiles_without_opening_a_connection(): void
    {
        $connection = new MySqlConnection(function () {
            throw new \RuntimeException('No MySQL connection is allowed in this test.');
        }, 'isolated_tax_ddl', '', ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'engine' => 'InnoDB']);
        $schema = Schema::getFacadeRoot();
        try {
            Schema::swap($connection->getSchemaBuilder());
            $queries = $connection->pretend(function () {
                (require base_path('packages/Webkul/Tax/src/Database/Migrations/2026_10_28_000001_create_tax_foundation_tables.php'))->up();
            });
            $sql = implode("\n", array_column($queries, 'query'));
            $this->assertSame(9, substr_count($sql, 'create table'));
            $this->assertStringContainsString('`quote_id` int unsigned null', $sql);
            $this->assertStringContainsString('`seller_id` bigint unsigned not null', $sql);
            $this->assertStringContainsString('decimal(20, 6)', $sql);
            $this->assertStringContainsString('on delete restrict', $sql);
            $this->assertStringNotContainsString('on delete cascade', $sql);
            $this->assertStringContainsString('engine = InnoDB', $sql);
        } finally {
            Schema::swap($schema);
        }
    }

    public function test_empty_installation_rolls_back_and_migrates_again(): void
    {
        $migration = require base_path('packages/Webkul/Tax/src/Database/Migrations/2026_10_28_000001_create_tax_foundation_tables.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('tax_documents'));
        $this->assertTrue(Schema::hasTable('quotes'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('tax_documents'));
    }

    public function test_all_financial_foreign_keys_restrict_deletion(): void
    {
        foreach (['tax_sellers', 'tax_documents', 'tax_document_items', 'tax_document_sequences', 'payments', 'payment_allocations', 'tax_document_links', 'tax_document_artifacts', 'tax_audit_events'] as $table) {
            foreach (DB::select('PRAGMA foreign_key_list('.$table.')') as $foreign) {
                $this->assertSame('RESTRICT', $foreign->on_delete, $table.'.'.$foreign->from);
            }
        }
    }

    public function test_deferred_models_have_no_write_workflow(): void
    {
        foreach ([Payment::class, PaymentAllocation::class, TaxDocumentSequence::class, TaxDocumentLink::class, TaxDocumentArtifact::class] as $class) {
            try {
                (new $class)->save();
                $this->fail('Deferred write unexpectedly allowed: '.$class);
            } catch (\DomainException $exception) {
                $this->assertStringContainsString('unavailable', $exception->getMessage());
            }
        }
    }
}
