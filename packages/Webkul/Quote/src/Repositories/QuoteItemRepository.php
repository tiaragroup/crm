<?php

namespace Webkul\Quote\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Quote\Contracts\QuoteItem;

class QuoteItemRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Quote\Contracts\QuoteItem';
    }

    /**
     * @return mixed
     */
    public function create(array $data)
    {
        $product = ! empty($data['product_id'])
            ? $this->productRepository->findOrFail($data['product_id'])
            : null;

        $quoteItem = parent::create(array_merge($data, [
            'sku'  => $product?->sku ?? ($data['sku'] ?? null),
            'name' => $product?->name ?? ($data['name'] ?? null),
        ]));

        return $quoteItem;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return QuoteItem
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = ! empty($data['product_id'])
            ? $this->productRepository->findOrFail($data['product_id'])
            : null;

        $quoteItem = parent::update(array_merge($data, [
            'sku'  => $product?->sku ?? ($data['sku'] ?? null),
            'name' => $product?->name ?? ($data['name'] ?? null),
        ]), $id);

        return $quoteItem;
    }
}
