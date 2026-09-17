<?php

namespace Webkul\Admin\Http\Requests\DocumentImport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewDocumentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['mode' => ['required', Rule::in(['single', 'bulk'])], 'rows' => ['required', 'array', 'max:'.config('document_imports.max_rows')]];
        foreach (['company.name', 'company.name_ar', 'company.email', 'company.phone', 'company.website', 'company.cr_number', 'company.vat_number', 'company.address', 'contact.name', 'contact.job_title', 'contact.email', 'contact.phone', 'event.title', 'event.event_type', 'event.event_date', 'event.venue', 'event.requirements'] as $field) {
            $rules['rows.*.'.$field] = str_ends_with($field, 'email') ? ['nullable', 'email', 'max:255'] : ['nullable', 'string', 'max:5000'];
        }
        $rules['rows.*.event.event_date'] = ['nullable', 'date_format:Y-m-d'];
        $rules['rows.*.event.guest_count'] = ['nullable', 'integer', 'min:0'];
        $rules['rows.*.event.budget'] = ['nullable', 'numeric', 'min:0'];
        $rules['rows.*.organization_id'] = ['nullable', 'integer', 'exists:organizations,id'];
        $rules['rows.*.person_id'] = ['nullable', 'integer', 'exists:persons,id'];
        $rules['owner_id'] = ['nullable', 'integer', 'exists:users,id'];

        return $rules;
    }

    public function reviewedData(): array
    {
        $rows = array_values($this->validated('rows', []));

        return $this->validated('mode') === 'bulk' ? ['mode' => 'bulk', 'rows' => $rows] : array_merge(['mode' => 'single'], $rows[0] ?? []);
    }
}
