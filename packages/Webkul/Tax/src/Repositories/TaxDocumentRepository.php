<?php

namespace Webkul\Tax\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Tax\Contracts\TaxDocument;

class TaxDocumentRepository extends Repository
{
    public function model()
    {
        return TaxDocument::class;
    }

    public function create(array $attributes)
    {
        throw new \LogicException('Use DraftService.');
    }

    public function update(array $attributes, $id)
    {
        throw new \LogicException('Use DraftService.');
    }

    public function delete($id)
    {
        throw new \LogicException('Tax documents are retained.');
    }
}
