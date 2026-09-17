<?php

namespace Webkul\Admin\DataGrids\DocumentImport;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DocumentImportDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $query = DB::table('document_imports')->leftJoin('users', 'document_imports.user_id', '=', 'users.id')
            ->select('document_imports.*', 'users.name as uploader');
        if ($ids = bouncer()->getAuthorizedUserIds()) {
            $query->whereIn('document_imports.user_id', $ids);
        }
        foreach (['id', 'status', 'document_type', 'created_at'] as $column) {
            $this->addFilter($column, 'document_imports.'.$column);
        }
        $this->addFilter('uploader', 'users.name');
        $this->setQueryBuilder($query);

        return $query;
    }

    public function prepareColumns(): void
    {
        $columns = [
            ['id', 'id', 'integer', true], ['original_filename', 'filename', 'string', true],
            ['document_type', 'type', 'string', true], ['uploader', 'uploaded-by', 'string', true],
            ['status', 'status', 'string', true], ['created_at', 'created-at', 'date', true], ['imported_at', 'imported-at', 'date', true],
        ];
        foreach ($columns as [$index,$label,$type,$filterable]) {
            $this->addColumn([
                'index'      => $index, 'label' => trans('admin::app.document-imports.datagrid.'.$label), 'type' => $type,
                'searchable' => in_array($index, ['original_filename', 'uploader']), 'sortable' => true, 'filterable' => $filterable,
                'closure'    => match ($index) {
                    'created_at', 'imported_at' => fn ($row) => $row->{$index} ? core()->formatDate($row->{$index}, 'd M Y H:i') : '—',
                    'status'                    => fn ($row) => trans('admin::app.document-imports.status.'.$row->status),
                    default                     => null,
                },
            ]);
        }
        foreach (['company' => 'company.name', 'contact' => 'contact.name'] as $index => $path) {
            $this->addColumn([
                'index'      => $index, 'label' => trans('admin::app.document-imports.datagrid.'.$index), 'type' => 'string',
                'searchable' => false, 'filterable' => false,
                'closure'    => fn ($row) => data_get(json_decode($row->reviewed_data ?: $row->extracted_data ?: '{}', true), $path, '—') ?: '—',
            ]);
        }
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('document_imports.edit')) {
            $this->addAction(['index'=>'review', 'icon'=>'icon-edit', 'title'=>trans('admin::app.document-imports.actions.review'), 'method'=>'GET', 'url'=>fn ($row) =>route('admin.document_imports.review', $row->id)]);
        }
        if (bouncer()->hasPermission('document_imports.view')) {
            $this->addAction(['index'=>'download', 'icon'=>'icon-download', 'title'=>trans('admin::app.document-imports.actions.download'), 'method'=>'GET', 'url'=>fn ($row) =>route('admin.document_imports.download', $row->id)]);
        }
        if (bouncer()->hasPermission('document_imports.delete')) {
            $this->addAction(['index'=>'delete', 'icon'=>'icon-delete', 'title'=>trans('admin::app.document-imports.actions.delete'), 'method'=>'DELETE', 'url'=>fn ($row) =>route('admin.document_imports.destroy', $row->id)]);
        }
    }
}
