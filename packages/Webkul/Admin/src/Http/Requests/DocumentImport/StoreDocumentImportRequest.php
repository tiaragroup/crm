<?php

namespace Webkul\Admin\Http\Requests\DocumentImport;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['document' => [
            'required', 'file', 'max:'.config('document_imports.max_upload_kb'),
            'mimes:pdf,docx,xlsx,xls,csv',
            'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv',
        ]];
    }
}
