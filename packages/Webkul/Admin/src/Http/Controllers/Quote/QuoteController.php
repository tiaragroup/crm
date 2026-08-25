<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Prettus\Repository\Criteria\RequestCriteria;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\DataGrids\Quote\QuoteDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\ProposalForm;
use Webkul\Admin\Http\Resources\QuoteResource;
use Webkul\Contact\Models\Person;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Product\Models\CateringMenuCategory;
use Webkul\Product\Models\CateringPackage;
use Webkul\Quote\Models\ProposalSetting;
use Webkul\Quote\Repositories\QuoteRepository;
use Webkul\Quote\Services\ProposalSnapshot;
use Webkul\Quote\Services\ProposalWordExporter;
use Webkul\User\Models\User;

class QuoteController extends Controller
{
    use PDFHandler;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected QuoteRepository $quoteRepository,
        protected LeadRepository $leadRepository
    ) {
        request()->request->add(['entity_type' => 'quotes']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(QuoteDataGrid::class)->process();
        }

        return view('admin::quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $lead = $this->leadRepository->find(request('id'));

        return view('admin::quotes.create', array_merge(
            compact('lead'),
            $this->proposalFormData()
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProposalForm $request): RedirectResponse
    {
        Event::dispatch('quote.create.before');

        $quote = $this->quoteRepository->create($request->all());

        $leadId = request('lead_id');

        if ($leadId) {
            $lead = $this->leadRepository->find($leadId);

            $lead->quotes()->attach($quote->id);
        }

        Event::dispatch('quote.create.after', $quote);

        session()->flash('success', trans('admin::app.quotes.index.create-success'));

        return request()->query('from') === 'lead' && $leadId
            ? redirect()->route('admin.leads.view', ['id' => $leadId, 'from' => 'quotes'])
            : redirect()->route('admin.quotes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $quote = $this->quoteRepository->with([
            'items',
            'menuSections.items',
            'person',
            'user',
        ])->findOrFail($id);

        return view('admin::quotes.edit', array_merge(
            compact('quote'),
            $this->proposalFormData()
        ));
    }

    /**
     * Shared data for the catering proposal builder.
     */
    protected function proposalFormData(): array
    {
        $cateringPackages = CateringPackage::query()
            ->with(['items.product.cateringMenuCategory'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (CateringPackage $package) => [
                'id'                 => $package->id,
                'name'               => $package->name,
                'description'        => $package->description,
                'setup_description'  => $package->setup_description,
                'service_inclusions' => $package->service_inclusions,
                'price_per_person'   => (float) $package->price_per_person,
                'minimum_guests'     => $package->minimum_guests,
                'items'              => $package->items
                    ->filter(fn ($item) => $item->product)
                    ->map(fn ($item) => [
                        'product' => [
                            'id'          => $item->product->id,
                            'name'        => $item->product->getRawOriginal('name') ?: $item->product->name,
                            'description' => $item->product->getRawOriginal('description') ?: $item->product->description,
                            'catering_menu_category' => $item->product->cateringMenuCategory ? [
                                'id'   => $item->product->cateringMenuCategory->id,
                                'name' => $item->product->cateringMenuCategory->name,
                            ] : null,
                        ],
                    ])
                    ->values(),
            ])
            ->values();

        $menuCategories = CateringMenuCategory::query()
            ->with(['products' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CateringMenuCategory $category) => [
                'id'       => $category->id,
                'name'     => $category->name,
                'products' => $category->products->map(fn ($product) => [
                    'id'          => $product->id,
                    'name'        => $product->getRawOriginal('name') ?: $product->name,
                    'description' => $product->getRawOriginal('description') ?: $product->description,
                ])->values(),
            ])
            ->values();

        return [
            'proposalSettings' => ProposalSetting::query()->first(),
            'cateringPackages' => $cateringPackages,
            'menuCategories'   => $menuCategories,
            'people' => Person::query()
                ->with('organization:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'emails', 'contact_numbers', 'organization_id'])
                ->map(fn (Person $person) => [
                    'id'      => $person->id,
                    'name'    => $person->name,
                    'company' => $person->organization?->name,
                    'email'   => data_get($person->emails, '0.value'),
                    'mobile'  => data_get($person->contact_numbers, '0.value'),
                ])
                ->values(),
            'users'  => User::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProposalForm $request, int $id): RedirectResponse
    {
        Event::dispatch('quote.update.before', $id);

        $quote = $this->quoteRepository->update($request->all(), $id);

        $quote->leads()->detach();

        $leadId = request('lead_id');

        if ($leadId) {
            $lead = $this->leadRepository->find($leadId);

            $lead->quotes()->attach($quote->id);
        }

        Event::dispatch('quote.update.after', $quote);

        session()->flash('success', trans('admin::app.quotes.index.update-success'));

        return request()->query('from') === 'lead' && $leadId
            ? redirect()->route('admin.leads.view', ['id' => $leadId, 'from' => 'quotes'])
            : redirect()->route('admin.quotes.index');
    }

    /**
     * Search the quotes.
     */
    public function search(): AnonymousResourceCollection
    {
        $quotes = $this->quoteRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->all();

        return QuoteResource::collection($quotes);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->quoteRepository->findOrFail($id);

        try {
            Event::dispatch('quote.delete.before', $id);

            $this->quoteRepository->delete($id);

            Event::dispatch('quote.delete.after', $id);

            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $quotes = $this->quoteRepository->findWhereIn('id', $massDestroyRequest->input('indices'));

        try {
            foreach ($quotes as $quotes) {
                Event::dispatch('quote.delete.before', $quotes->id);

                $this->quoteRepository->delete($quotes->id);

                Event::dispatch('quote.delete.after', $quotes->id);
            }

            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-success'),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Print and download the for the specified resource.
     */
    public function print($id): Response|StreamedResponse
    {
        $quote = $this->quoteRepository->with(['items', 'menuSections.items', 'person.organization', 'user'])->findOrFail($id);
        $snapshot = $quote->document_snapshot ?: app(ProposalSnapshot::class)->build($quote);
        $snapshot['sales_contact'] = [
            'name'  => $quote->user?->name,
            'email' => $quote->user?->email,
            'phone' => $quote->user?->phone,
        ];

        return $this->downloadPDF(
            view('admin::quotes.proposal-pdf', compact('snapshot'))->render(),
            'Proposal_'.$quote->proposal_reference,
            'letter'
        );
    }

    /**
     * Download an editable Word version of the proposal.
     */
    public function word(int $id, ProposalWordExporter $exporter): BinaryFileResponse
    {
        $quote = $this->quoteRepository->with(['items', 'menuSections.items', 'person.organization', 'user'])->findOrFail($id);

        return $exporter->download($quote);
    }
}
