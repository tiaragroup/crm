<?php

namespace Webkul\Admin\Http\Controllers\Products;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\CateringMenuCategory;
use Webkul\Product\Models\CateringPackage;
use Webkul\Product\Models\CateringPackageItem;

class CateringMenuController extends Controller
{
    public function index(): View
    {
        $menus = CateringPackage::query()
            ->withCount('items')
            ->latest()
            ->get();

        return view('admin::catering-menus.index', compact('menus'));
    }

    public function create(): View
    {
        return $this->formView(new CateringPackage);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $menu = CateringPackage::create($this->menuAttributes($data));
            $this->replaceItems($menu, $data['product_ids']);
        });

        session()->flash('success', 'Catering menu created successfully.');

        return redirect()->route('admin.catering.menus.index');
    }

    public function edit(CateringPackage $menu): View
    {
        $menu->load('items');

        return $this->formView($menu);
    }

    public function update(Request $request, CateringPackage $menu): RedirectResponse
    {
        $data = $this->validated($request, $menu);

        DB::transaction(function () use ($data, $menu) {
            $menu->update($this->menuAttributes($data));
            $this->replaceItems($menu, $data['product_ids']);
        });

        session()->flash('success', 'Catering menu updated successfully.');

        return redirect()->route('admin.catering.menus.index');
    }

    public function destroy(CateringPackage $menu): RedirectResponse
    {
        $menu->delete();

        session()->flash('success', 'Catering menu deleted successfully.');

        return redirect()->route('admin.catering.menus.index');
    }

    private function formView(CateringPackage $menu): View
    {
        $categories = CateringMenuCategory::query()
            ->where('is_active', true)
            ->with(['products' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        $selectedProductIds = old(
            'product_ids',
            $menu->exists ? $menu->items->pluck('product_id')->map(fn ($id) => (int) $id)->all() : []
        );

        return view('admin::catering-menus.form', compact('menu', 'categories', 'selectedProductIds'));
    }

    private function validated(Request $request, ?CateringPackage $menu = null): array
    {
        return $request->validate([
            'name'                 => ['required', 'string', 'max:255', Rule::unique('catering_packages', 'name')->ignore($menu?->id)],
            'description'          => ['nullable', 'string'],
            'setup_description'    => ['nullable', 'string'],
            'service_inclusions'   => ['nullable', 'string'],
            'price_per_person'     => ['required', 'numeric', 'min:0'],
            'minimum_guests'       => ['nullable', 'integer', 'min:1'],
            'is_active'            => ['required', 'boolean'],
            'product_ids'          => ['required', 'array', 'min:1'],
            'product_ids.*'        => ['required', 'integer', 'distinct', 'exists:products,id'],
        ], [
            'product_ids.required' => 'Select at least one dish for this menu.',
            'product_ids.min'      => 'Select at least one dish for this menu.',
        ]);
    }

    private function menuAttributes(array $data): array
    {
        return [
            'name'               => $data['name'],
            'description'        => $data['description'] ?? null,
            'setup_description'  => $data['setup_description'] ?? null,
            'service_inclusions' => $data['service_inclusions'] ?? null,
            'price_per_person'   => $data['price_per_person'],
            'minimum_guests'     => $data['minimum_guests'] ?? null,
            'is_active'          => (bool) $data['is_active'],
        ];
    }

    private function replaceItems(CateringPackage $menu, array $productIds): void
    {
        $menu->items()->delete();

        foreach (array_values($productIds) as $index => $productId) {
            CateringPackageItem::create([
                'catering_package_id' => $menu->id,
                'product_id'          => $productId,
                'sort_order'          => $index + 1,
            ]);
        }
    }
}
