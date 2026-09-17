<?php

namespace Webkul\Lead\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Lead\Models\DocumentImport;

class DocumentImportRepository extends Repository
{
    public function model()
    {
        return DocumentImport::class;
    }
}
