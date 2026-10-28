<?php

namespace Tests\Tax;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Quote\Models\Quote;
use Webkul\Tax\Services\DraftService;
use Webkul\Tax\Services\ProposalToDraft;

class ProposalConversionTest extends TaxTestCase
{
    protected function proposal(string $status = 'accepted'): int
    {
        $organization = DB::table('organizations')->insertGetId(['name' => 'Customer Ltd', 'user_id' => $this->actor->id]);
        $person = DB::table('persons')->insertGetId(['name' => 'Contact', 'emails' => '[]', 'organization_id' => $organization, 'user_id' => $this->actor->id]);
        $id = DB::table('quotes')->insertGetId([
            'subject'           => 'Accepted catering', 'status' => $status, 'proposal_reference' => 'TC-TEST-1', 'revision' => 2,
            'person_id'         => $person, 'user_id' => $this->actor->id, 'client_company' => 'Customer Ltd',
            'grand_total'       => '115.00', 'tax_amount' => '15.00', 'adjustment_amount' => '5.00',
            'document_snapshot' => '{"original":true}',
        ]);
        DB::table('quote_items')->insert([
            ['quote_id' => $id, 'name' => 'Catering package', 'price' => '50.00', 'quantity' => 2, 'is_included' => false, 'pricing_type' => 'per_person'],
            ['quote_id' => $id, 'name' => 'Included service', 'price' => '0', 'quantity' => 1, 'is_included' => true, 'pricing_type' => 'included'],
        ]);
        $section = DB::table('quote_menu_sections')->insertGetId(['quote_id' => $id, 'name' => 'Menu']);
        DB::table('quote_menu_items')->insert(['quote_menu_section_id' => $section, 'name' => 'Canapes']);

        return $id;
    }

    public function test_accepted_conversion_uses_commercial_lines_and_preserves_proposal(): void
    {
        $input = $this->draftInput();
        $id = $this->proposal();
        $before = (array) DB::table('quotes')->find($id);
        $itemsBefore = DB::table('quote_items')->where('quote_id', $id)->get()->toJson();
        $preview = app(ProposalToDraft::class)->preview($id, $this->actor);
        $this->assertCount(1, $preview['items']);
        $this->assertNull($preview['items'][0]['tax_category']);
        $document = app(DraftService::class)->save(array_merge($preview, ['seller_id' => $input['seller_id']]), $this->actor);
        $this->assertSame($id, $document->quote->id);
        $this->assertSame(1, Quote::findOrFail($id)->taxDocuments()->count());
        $this->assertSame('TC-TEST-1', $document->source_reference);
        $this->assertSame(2, $document->source_revision);
        $this->assertSame($before, (array) DB::table('quotes')->find($id));
        $this->assertSame($itemsBefore, DB::table('quote_items')->where('quote_id', $id)->get()->toJson());
        $this->assertSame(1, DB::table('quote_menu_items')->count());
        $this->assertNull($document->tax_inclusive_total);
    }

    public function test_unaccepted_proposal_and_stale_preview_are_rejected(): void
    {
        $input = $this->draftInput();
        $id = $this->proposal('draft');
        $this->getJson(route('admin.tax_documents.create', ['quote_id' => $id]))->assertUnprocessable();
        DB::table('quotes')->where('id', $id)->update(['status' => 'accepted']);
        $preview = app(ProposalToDraft::class)->preview($id, $this->actor);
        DB::table('quote_items')->where('quote_id', $id)->update(['price' => '60']);
        $this->postJson(route('admin.tax_documents.store'), array_merge($preview, ['seller_id' => $input['seller_id']]))->assertStatus(409);
        $this->assertSame(0, DB::table('tax_documents')->count());
    }

    public function test_proposal_conversion_respects_owner_access(): void
    {
        $id = $this->proposal();
        $other = $this->user('custom', ['tax_documents.create', 'quotes'], 'own');
        $this->actingAs($other, 'user');
        $this->getJson(route('admin.tax_documents.create', ['quote_id' => $id]))->assertNotFound();
    }

    public function test_crm_deletion_cannot_cascade_to_tax_documents(): void
    {
        $input = $this->draftInput();
        $id = $this->proposal();
        $preview = app(ProposalToDraft::class)->preview($id, $this->actor);
        app(DraftService::class)->save(array_merge($preview, ['seller_id' => $input['seller_id']]), $this->actor);
        $this->expectException(QueryException::class);
        DB::table('quotes')->where('id', $id)->delete();
    }
}
