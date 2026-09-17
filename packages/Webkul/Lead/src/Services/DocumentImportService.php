<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Contact\Repositories\OrganizationRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Models\DocumentImport;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Type;
use Webkul\Lead\Repositories\LeadRepository;

class DocumentImportService
{
    public function __construct(
        protected OrganizationRepository $organizations,
        protected PersonRepository $persons,
        protected LeadRepository $leads,
    ) {}

    public function import(DocumentImport $documentImport, array $reviewed, int $ownerId): DocumentImport
    {
        return DB::transaction(function () use ($documentImport, $reviewed, $ownerId) {
            $locked = DocumentImport::query()->lockForUpdate()->findOrFail($documentImport->id);
            if ($locked->status === DocumentImport::STATUS_IMPORTED) {
                throw ValidationException::withMessages(['import' => trans('admin::app.document-imports.messages.already-imported')]);
            }

            $records = ($reviewed['mode'] ?? 'single') === 'bulk' ? ($reviewed['rows'] ?? []) : [$reviewed];
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->importRecord($record, $ownerId);
            }
            if (! $results) {
                throw ValidationException::withMessages(['import' => trans('admin::app.document-imports.messages.no-records')]);
            }

            $reviewed['_imported_records'] = $results;
            $first = $results[0];
            $locked->update([
                'reviewed_data'   => $reviewed, 'status' => DocumentImport::STATUS_IMPORTED,
                'organization_id' => $first['organization_id'], 'person_id' => $first['person_id'],
                'lead_id'         => $first['lead_id'], 'imported_at' => now(), 'error_message' => null,
            ]);

            return $locked->refresh();
        });
    }

    private function importRecord(array $record, int $ownerId): array
    {
        $company = $record['company'] ?? [];
        $contact = $record['contact'] ?? [];
        $event = $record['event'] ?? [];
        $organization = null;

        if (! empty($record['organization_id'])) {
            $organization = $this->organizations->findOrFail($record['organization_id']);
        } elseif (! empty($company['name'])) {
            $organization = $this->organizations->create([
                'entity_type' => 'organizations', 'name' => $company['name'], 'user_id' => $ownerId,
                'address'     => $company['address'] ? ['address' => $company['address']] : null,
                'name_ar'     => $company['name_ar'] ?? null, 'email' => $company['email'] ?? null,
                'phone'       => $company['phone'] ?? null, 'website' => $company['website'] ?? null,
                'cr_number'   => $company['cr_number'] ?? null, 'vat_number' => $company['vat_number'] ?? null,
            ]);
        }

        if (! empty($record['person_id'])) {
            $person = $this->persons->findOrFail($record['person_id']);
        } elseif (! empty($contact['name']) || ! empty($contact['email']) || ! empty($contact['phone'])) {
            $person = $this->persons->create([
                'entity_type'     => 'persons', 'name' => $contact['name'] ?: $contact['email'] ?: $contact['phone'],
                'job_title'       => $contact['job_title'] ?? null, 'user_id' => $ownerId,
                'organization_id' => $organization?->id,
                'emails'          => ! empty($contact['email']) ? [['value' => $contact['email'], 'label' => 'work']] : [],
                'contact_numbers' => ! empty($contact['phone']) ? [['value' => $contact['phone'], 'label' => 'work']] : [],
            ]);
        } else {
            $person = null;
        }

        $leadTitle = $event['title'] ?: ($company['name'] ?? null);
        if (! $leadTitle) {
            throw ValidationException::withMessages(['rows' => trans('admin::app.document-imports.messages.title-required')]);
        }

        $pipeline = Pipeline::query()->where('name', 'like', '%Event Sales%')->first()
            ?? Pipeline::query()->where('is_default', 1)->first()
            ?? Pipeline::query()->firstOrFail();
        $stage = $pipeline->stages()->whereIn('code', ['new-inquiry', 'new'])->first() ?? $pipeline->stages()->firstOrFail();
        $lead = $this->leads->create([
            'entity_type'         => 'leads', 'title' => $leadTitle,
            'description'         => $event['requirements'] ?? null, 'lead_value' => is_numeric($event['budget'] ?? null) ? $event['budget'] : 0,
            'status'              => 1, 'user_id' => $ownerId, 'person_id' => $person?->id,
            'lead_pipeline_id'    => $pipeline->id, 'lead_pipeline_stage_id' => $stage->id,
            'lead_source_id'      => Source::query()->where('name', 'like', '%Document%')->value('id') ?? Source::query()->value('id'),
            'lead_type_id'        => Type::query()->where('name', 'like', '%Event%')->value('id') ?? Type::query()->value('id'),
            'expected_close_date' => $event['event_date'] ?? null,
            'event_date'          => $event['event_date'] ?? null, 'guest_count' => $event['guest_count'] ?? null,
            'venue_location'      => $event['venue'] ?? null,
        ]);

        return ['organization_id' => $organization?->id, 'person_id' => $person?->id, 'lead_id' => $lead->id];
    }
}
