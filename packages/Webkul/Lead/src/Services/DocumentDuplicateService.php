<?php

namespace Webkul\Lead\Services;

use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;

class DocumentDuplicateService
{
    public function suggestions(array $record): array
    {
        $company = $record['company'] ?? [];
        $contact = $record['contact'] ?? [];

        $organizations = empty($company['name']) ? [] : Organization::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($company['name']))])
            ->limit(5)->get(['id', 'name'])->toArray();

        $persons = [];
        if (! empty($contact['email']) || ! empty($contact['phone'])) {
            $persons = Person::query()->where(function ($q) use ($contact) {
                if ($email = $contact['email'] ?? null) {
                    $q->orWhereJsonContains('emails', ['value' => $email]);
                }
                if ($phone = $contact['phone'] ?? null) {
                    $q->orWhereJsonContains('contact_numbers', ['value' => $phone]);
                }
            })->limit(5)->get(['id', 'name', 'emails', 'contact_numbers'])->toArray();
        }

        return ['organizations' => $organizations, 'persons' => $persons];
    }
}
