<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Traits\CustomAttribute;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Quote\Contracts\Quote as QuoteContract;
use Webkul\User\Models\UserProxy;

class Quote extends Model implements QuoteContract
{
    use CustomAttribute;

    protected $table = 'quotes';

    protected $casts = [
        'billing_address'  => 'array',
        'shipping_address' => 'array',
        'expired_at'       => 'datetime',
        'issued_at'        => 'date',
        'event_at'         => 'datetime',
        'document_snapshot'=> 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject',
        'description',
        'proposal_reference',
        'status',
        'revision',
        'issued_at',
        'client_company',
        'attention_name',
        'client_mobile',
        'client_email',
        'greeting',
        'event_type',
        'event_at',
        'venue',
        'setup_description',
        'guest_count',
        'service_inclusions',
        'vat_percent',
        'pricing_terms',
        'payment_terms',
        'changes_terms',
        'cancellation_terms',
        'bank_account_name',
        'bank_name',
        'iban',
        'company_signatory_name',
        'company_signatory_title',
        'client_signatory_name',
        'client_signatory_title',
        'document_snapshot',
        'billing_address',
        'shipping_address',
        'discount_percent',
        'discount_amount',
        'tax_amount',
        'adjustment_amount',
        'sub_total',
        'grand_total',
        'expired_at',
        'user_id',
        'person_id',
    ];

    /**
     * Treat legacy MySQL zero dates as an unconfirmed event date.
     */
    public function getEventAtAttribute($value)
    {
        if (blank($value) || str_starts_with((string) $value, '0000-00-00')) {
            return null;
        }

        return $this->asDateTime($value);
    }

    /**
     * Get the quote items record associated with the quote.
     */
    public function items()
    {
        return $this->hasMany(QuoteItemProxy::modelClass())->orderBy('sort_order');
    }

    public function menuSections()
    {
        return $this->hasMany(QuoteMenuSection::class)->orderBy('sort_order');
    }

    /**
     * Get the user that owns the quote.
     */
    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    /**
     * Get the person that owns the quote.
     */
    public function person()
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    /**
     * The leads that belong to the quote.
     */
    public function leads()
    {
        return $this->belongsToMany(LeadProxy::modelClass(), 'lead_quotes');
    }
}
