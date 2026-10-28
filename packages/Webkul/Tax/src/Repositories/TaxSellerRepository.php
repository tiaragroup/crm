<?php

namespace Webkul\Tax\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Tax\Contracts\TaxSeller;

class TaxSellerRepository extends Repository
{
    public function model()
    {
        return TaxSeller::class;
    }

    public function create(array $attributes)
    {
        throw new \LogicException('Use SellerService.');
    }

    public function update(array $attributes, $id)
    {
        throw new \LogicException('Use SellerService.');
    }

    public function delete($id)
    {
        throw new \LogicException('Deactivate seller settings instead.');
    }
}
