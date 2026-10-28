<?php

namespace Webkul\Admin\Http\Controllers\Tax;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Tax\Models\TaxSellerProxy;
use Webkul\Tax\Repositories\TaxSellerRepository;
use Webkul\Tax\Services\SellerService;
use Webkul\Tax\Services\TaxAccess;

class SellerController extends Controller
{
    public function __construct(protected TaxAccess $access, protected SellerService $sellers) {}

    public function index(TaxSellerRepository $repository)
    {
        $this->access->authorize(auth()->guard('user')->user(), 'tax_settings');
        $sellers = $repository->getModel()->newQuery()->orderBy('code')->paginate(20);

        return view('admin::tax.sellers.index', compact('sellers'));
    }

    public function create()
    {
        $this->access->authorize(auth()->guard('user')->user(), 'tax_settings.manage');

        return view('admin::tax.sellers.form', ['seller' => null]);
    }

    public function edit(int $id)
    {
        $this->access->authorize(auth()->guard('user')->user(), 'tax_settings.manage');

        return view('admin::tax.sellers.form', ['seller' => TaxSellerProxy::query()->findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $seller = $this->sellers->save($request->all(), auth()->guard('user')->user());

        return redirect()->route('admin.tax_sellers.edit', $seller->id)->with('success', trans('admin::tax.saved'));
    }

    public function update(Request $request, int $id)
    {
        $this->sellers->save($request->all(), auth()->guard('user')->user(), $id);

        return redirect()->route('admin.tax_sellers.edit', $id)->with('success', trans('admin::tax.saved'));
    }
}
