<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalSetting extends Model
{
    protected $table = 'catering_proposal_settings';

    protected $guarded = [];

    protected $casts = [
        'vat_percent' => 'decimal:2',
    ];
}
