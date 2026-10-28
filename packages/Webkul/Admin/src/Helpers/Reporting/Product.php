<?php

namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Repositories\ProductRepository;

class Product extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    /**
     * Gets top-selling products by revenue.
     *
     * @param  int  $limit
     */
    public function getTopSellingProductsByRevenue($limit = null): Collection
    {
        $items = DB::table('quote_menu_items as quote_menu_items')
            ->join(
                'quote_menu_sections as quote_menu_sections',
                'quote_menu_sections.id',
                '=',
                'quote_menu_items.quote_menu_section_id'
            )
            ->join(
                'quotes as quotes',
                'quotes.id',
                '=',
                'quote_menu_sections.quote_id'
            )
            ->leftJoin(
                'products as products',
                'products.id',
                '=',
                'quote_menu_items.product_id'
            )
            ->whereNotNull('quote_menu_items.product_id')
            ->whereBetween('quotes.created_at', [
                $this->startDate,
                $this->endDate,
            ])
            ->select([
                'products.id',
                'products.name',
                'products.name_ar',
                'products.price',
            ])
            ->selectRaw(
                'COUNT(DISTINCT quotes.id) as proposal_count'
            )
            ->selectRaw(
                'SUM(COALESCE(quotes.guest_count, 0)) as guest_count'
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.name_ar',
                'products.price'
            )
            ->orderByDesc('proposal_count')
            ->orderByDesc('guest_count')
            ->limit($limit)
            ->get();

        return $items->map(function ($item) {
            return [
                'id'              => $item->id,

                'name'            => app()->getLocale() === 'ar'
                    && ! empty($item->name_ar)
                        ? $item->name_ar
                        : $item->name,

                'price'           => $item->price,

                'formatted_price' => core()->formatBasePrice(
                    $item->price
                ),

                'proposal_count'  => (int) $item->proposal_count,

                'guest_count'     => (int) $item->guest_count,
            ];
        });
    }

    /**
     * Gets top-selling products by quantity.
     *
     * @param  int  $limit
     */
    public function getTopSellingProductsByQuantity($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        $items = $this->productRepository
            ->resetModel()
            ->with('product')
            ->leftJoin('leads', 'lead_products.lead_id', '=', 'leads.id')
            ->leftJoin('products', 'lead_products.product_id', '=', 'products.id')
            ->select('*')
            ->addSelect(DB::raw('SUM('.$tablePrefix.'lead_products.quantity) as total_qty_ordered'))
            ->whereBetween('leads.closed_at', [$this->startDate, $this->endDate])
            ->having(DB::raw('SUM('.$tablePrefix.'lead_products.quantity)'), '>', 0)
            ->groupBy('product_id')
            ->orderBy('total_qty_ordered', 'DESC')
            ->limit($limit)
            ->get();

        $items = $items->map(function ($item) {
            return [
                'id'                => $item->product_id,
                'name'              => $item->name,
                'price'             => $item->product?->price,
                'formatted_price'   => core()->formatBasePrice($item->price),
                'total_qty_ordered' => $item->total_qty_ordered,
            ];
        });

        return $items;
    }
}
