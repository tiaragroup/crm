<?php

namespace Webkul\Tax\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Tax\Models\TaxAuditEventProxy;
use Webkul\Tax\Models\TaxDocumentProxy;
use Webkul\Tax\Models\TaxSellerProxy;
use Webkul\Tax\Validation\DraftRules;
use Webkul\Tax\Validation\FieldLabels;
use Webkul\User\Contracts\User;

class DraftService
{
    public function __construct(
        protected TaxAccess $access,
        protected DraftLineNormalizer $lines,
        protected SellerService $sellers,
        protected ProposalToDraft $proposals,
    ) {}

    public function save(array $input, User $actor, ?int $id = null)
    {
        $this->access->authorize($actor, $id ? 'tax_documents.edit' : 'tax_documents.create');
        $rules = DraftRules::rules();
        if ($id) {
            $rules['lock_version'] = ['required', 'integer', 'min:1'];
        }
        $data = Validator::make($input, $rules, [], FieldLabels::forRules($rules))->validate();
        $items = $this->lines->normalize($data['items']);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($data, $items, $hash, $actor, $id) {
                if (! $id && $existing = TaxDocumentProxy::query()->where('uuid', $data['uuid'])->first()) {
                    return $this->replay($existing, $actor, $hash);
                }
                $document = $id ? $this->access->scope(TaxDocumentProxy::query(), $actor)->lockForUpdate()->findOrFail($id)
                    : new (TaxDocumentProxy::modelClass());
                abort_if($id && ($document->status !== 'draft' || $document->lock_version !== (int) $data['lock_version']), 409, trans('admin::tax.stale'));
                abort_if($id && ($document->uuid !== $data['uuid'] || (int) $document->quote_id !== (int) ($data['quote_id'] ?? 0)), 422);

                $seller = TaxSellerProxy::query()->lockForUpdate()->findOrFail($data['seller_id']);
                abort_unless($seller->is_active, 422, trans('admin::tax.inactive-seller'));
                $this->checkCustomer($data, $actor);
                $before = $id ? $document->load('items')->toArray() : null;
                $source = $id ? $document->source_snapshot : null;
                if (! $id && ! empty($data['quote_id'])) {
                    [$quote, $source, $fingerprint] = $this->proposals->source($data['quote_id'], $actor, true);
                    abort_unless(hash_equals($fingerprint, $data['source_fingerprint'] ?? ''), 409, trans('admin::tax.source-changed'));
                    abort_unless((int) ($data['person_id'] ?? 0) === (int) $quote->person_id, 422);
                    $document->source_reference = $quote->proposal_reference;
                    $document->source_revision = $quote->revision;
                    $document->source_snapshot = $source;
                    $document->source_fingerprint = $fingerprint;
                }
                $sourceIds = array_column($source['items'] ?? [], 'id');
                foreach ($items as $item) {
                    abort_if(! empty($item['source_quote_item_id']) && ! in_array((int) $item['source_quote_item_id'], $sourceIds, true), 422);
                }

                $document->fill(Arr::only($data, ['seller_id', 'person_id', 'organization_id', 'subject', 'profile', 'purpose', 'supply_date', 'due_date', 'notes']));
                $document->seller_snapshot = $this->sellers->snapshot($seller);
                $document->buyer_snapshot = $data['buyer'];
                $document->currency_code = 'SAR';
                $document->timezone = 'Asia/Riyadh';
                $document->status = 'draft';
                $document->lock_version = $id ? ((int) $document->getRawOriginal('lock_version')) + 1 : 1;
                if (! $id) {
                    $document->uuid = $data['uuid'];
                    $document->create_request_hash = $hash;
                    $document->quote_id = $data['quote_id'] ?? null;
                    $document->created_by = $actor->id;
                }
                $document->save();
                // Only after locking and checking draft status; issued records never reach this path.
                $document->items()->delete();
                $document->items()->createMany($items);
                $document = $document->fresh('items');
                TaxAuditEventProxy::query()->create([
                    'seller_id' => $seller->id, 'tax_document_id' => $document->id, 'actor_id' => $actor->id,
                    'event'     => $id ? 'draft.updated' : 'draft.created',
                    'payload'   => ['before' => $before, 'after' => $document->toArray()],
                ]);

                return $document;
            });
        } catch (QueryException $exception) {
            // Concurrent create retries converge on the unique UUID; unrelated DB errors still fail.
            if (! $id && in_array((string) $exception->getCode(), ['23000', '23505'], true)
                && $existing = TaxDocumentProxy::query()->where('uuid', $data['uuid'])->first()) {
                return $this->replay($existing, $actor, $hash);
            }
            throw $exception;
        }
    }

    protected function replay($document, User $actor, string $hash)
    {
        abort_unless((int) $document->created_by === (int) $actor->id && hash_equals($document->create_request_hash, $hash), 409, trans('admin::tax.request-conflict'));

        return $document->load('items');
    }

    protected function checkCustomer(array $data, User $actor): void
    {
        if (! empty($data['organization_id'])) {
            $this->access->scope(OrganizationProxy::query(), $actor, 'user_id')->findOrFail($data['organization_id']);
        }
        if (! empty($data['person_id'])) {
            $person = $this->access->scope(PersonProxy::query(), $actor, 'user_id')->findOrFail($data['person_id']);
            if (! empty($data['organization_id']) && (int) $person->organization_id !== (int) $data['organization_id']) {
                throw ValidationException::withMessages(['organization_id' => trans('admin::tax.customer-mismatch')]);
            }
        }
    }
}
