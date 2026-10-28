<?php

namespace Webkul\Tax\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Webkul\Tax\Models\TaxAuditEventProxy;
use Webkul\Tax\Models\TaxSellerProxy;
use Webkul\Tax\Validation\FieldLabels;
use Webkul\Tax\Validation\SellerRules;
use Webkul\User\Contracts\User;

class SellerService
{
    public function __construct(protected TaxAccess $access) {}

    public function save(array $input, User $actor, ?int $id = null)
    {
        $this->access->authorize($actor, 'tax_settings.manage');
        $rules = SellerRules::rules();
        $rules['code'][] = Rule::unique('tax_sellers', 'code')->ignore($id);
        if ($id) {
            $rules['lock_version'] = ['required', 'integer', 'min:1'];
        }
        $data = Validator::make($input, $rules, [], FieldLabels::forRules($rules))->validate();

        return DB::transaction(function () use ($data, $actor, $id) {
            $seller = $id ? TaxSellerProxy::query()->lockForUpdate()->findOrFail($id) : new (TaxSellerProxy::modelClass());
            abort_if($id && $seller->lock_version !== (int) $data['lock_version'], 409, trans('admin::tax.stale'));
            $before = $seller->exists ? $seller->toArray() : null;
            $seller->fill($data);
            $seller->currency_code = 'SAR';
            $seller->timezone = 'Asia/Riyadh';
            $seller->lock_version = $id ? ((int) $seller->getRawOriginal('lock_version')) + 1 : 1;
            if (! $id) {
                $seller->created_by = $actor->id;
            }
            $seller->save();
            TaxAuditEventProxy::query()->create([
                'seller_id' => $seller->id, 'actor_id' => $actor->id,
                'event'     => $id ? 'seller.updated' : 'seller.created',
                'payload'   => ['before' => $before, 'after' => $seller->fresh()->toArray()],
            ]);

            return $seller;
        });
    }

    public function snapshot($seller): array
    {
        return $seller->only(['code', 'legal_name_ar', 'legal_name_en', 'vat_number', 'registration_scheme', 'registration_number', 'branch_name', 'address', 'currency_code', 'timezone']);
    }
}
