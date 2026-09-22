<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
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
use Webkul\Product\Models\Product;
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
            $this->proposalFormData($lead?->person_id)
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
            $this->proposalFormData($quote->person_id)
        ));
    }

    /**
     * Shared data for the catering proposal builder.
     */
    protected function proposalFormData(?int $selectedPersonId = null): array
    {
        $cateringPackages = CateringPackage::query()
            ->with(['items.product.cateringMenuCategory'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (CateringPackage $package) => [
                'id'                    => $package->id,
                'name'                  => $package->name,
                'name_ar'               => $package->name_ar,
                'description'           => $package->description,
                'description_ar'        => $package->description_ar,
                'setup_description'     => $package->setup_description,
                'setup_description_ar'  => $package->setup_description_ar,
                'service_inclusions'    => $package->service_inclusions,
                'service_inclusions_ar' => $package->service_inclusions_ar,
                'price_per_person'      => (float) $package->price_per_person,
                'minimum_guests'        => $package->minimum_guests,
                'items'                 => $package->items
                    ->filter(fn ($item) => $item->product)
                    ->map(fn ($item) => [
                        'product' => [
                            'id'                     => $item->product->id,
                            'name'                   => $item->product->getRawOriginal('name') ?: $item->product->name,
                            'name_ar'                => $item->product->getRawOriginal('name_ar') ?: $item->product->name_ar,
                            'description'            => $item->product->getRawOriginal('description') ?: $item->product->description,
                            'description_ar'         => $item->product->getRawOriginal('description_ar') ?: $item->product->description_ar,
                            'catering_menu_category' => $item->product->cateringMenuCategory ? [
                                'id'      => $item->product->cateringMenuCategory->id,
                                'name'    => $item->product->cateringMenuCategory->name,
                                'name_ar' => $item->product->cateringMenuCategory->name_ar,
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
                'name_ar'  => $category->name_ar,
                'products' => $category->products->map(fn ($product) => [
                    'id'             => $product->id,
                    'name'           => $product->getRawOriginal('name') ?: $product->name,
                    'name_ar'        => $product->getRawOriginal('name_ar') ?: $product->name_ar,
                    'description'    => $product->getRawOriginal('description') ?: $product->description,
                    'description_ar' => $product->getRawOriginal('description_ar') ?: $product->description_ar,
                ])->values(),
            ])
            ->values();

        return [
            'proposalSettings' => ProposalSetting::query()->first(),
            'cateringPackages' => $cateringPackages,
            'menuCategories'   => $menuCategories,
            'people'           => Person::query()
                ->with('organization:id,name')
                ->when($selectedPersonId, fn ($query) => $query->whereKey($selectedPersonId), fn ($query) => $query->whereRaw('1 = 0'))
                ->get(['id', 'name', 'emails', 'contact_numbers', 'organization_id'])
                ->map(fn (Person $person) => $this->formatContact($person))
                ->values(),
            'users'  => User::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Search contacts on demand without loading the full CRM contact table.
     */
    public function searchContacts(): JsonResponse
    {
        $term = trim((string) request()->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $escapedTerm = addcslashes($term, '\\%_');
        $like = '%'.$escapedTerm.'%';

        $contacts = Person::query()
            ->with('organization:id,name')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('emails', 'like', $like)
                    ->orWhere('contact_numbers', 'like', $like)
                    ->orWhereHas('organization', fn ($organizationQuery) => $organizationQuery->where('name', 'like', $like));
            })
            ->when(
                bouncer()->getAuthorizedUserIds(),
                fn ($query, $userIds) => $query->whereIn('user_id', $userIds)
            )
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'emails', 'contact_numbers', 'organization_id'])
            ->map(fn (Person $person) => $this->formatContact($person))
            ->values();

        return response()->json(['data' => $contacts]);
    }

    /**
     * Return only the contact fields needed by the proposal form.
     */
    protected function formatContact(Person $person): array
    {
        return [
            'id'      => $person->id,
            'name'    => $person->name,
            'company' => $person->organization?->name,
            'email'   => data_get($person->emails, '0.value'),
            'mobile'  => data_get($person->contact_numbers, '0.value'),
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
        $locale = request()->query('locale', 'en');

        abort_unless(in_array($locale, ['en', 'ar'], true), 422, trans('admin::app.service-errors.unsupported-proposal-language'));

        $quote = $this->quoteRepository->with([
            'items',
            'menuSections.category',
            'menuSections.items.product',
            'person.organization',
            'user',
        ])->findOrFail($id);
        $snapshot = $quote->document_snapshot ?: app(ProposalSnapshot::class)->build($quote);
        $snapshot['sales_contact'] = [
            'name'  => $quote->user?->name,
            'email' => $quote->user?->email,
            'phone' => $quote->user?->phone,
        ];

        $snapshot = $this->withArabicProposalContent($quote, $snapshot);
        $previousLocale = App::getLocale();
        App::setLocale($locale);

        try {
            return $this->downloadPDF(
                view('admin::quotes.proposal-pdf', compact('snapshot', 'locale'))->render(),
                'Proposal_'.$quote->proposal_reference.($locale === 'ar' ? '_AR' : '_EN'),
                'letter'
            );
        } finally {
            App::setLocale($previousLocale);
        }
    }

    /**
     * Add current Arabic catalog translations to old and new proposal snapshots.
     */
    private function withArabicProposalContent($quote, array $snapshot): array
    {
        $sectionModels = $quote->menuSections->values();
        $menuItemNames = collect($snapshot['menu_sections'] ?? [])
            ->flatMap(fn ($section) => collect($section['items'] ?? [])->pluck('name'))
            ->filter()
            ->unique()
            ->values();
        $productsByName = Product::query()
            ->whereIn('name', $menuItemNames)
            ->get()
            ->keyBy('name');

        $snapshot['menu_sections'] = collect($snapshot['menu_sections'] ?? [])
            ->map(function (array $section, int $sectionIndex) use ($sectionModels, $productsByName) {
                $sectionModel = $sectionModels->get($sectionIndex);
                $itemModels = $sectionModel?->items?->values() ?? collect();
                $section['name_ar'] = $sectionModel?->category?->name_ar ?: ($section['name_ar'] ?? $section['name'] ?? null);
                $section['items'] = collect($section['items'] ?? [])
                    ->map(function (array $item, int $itemIndex) use ($itemModels, $productsByName) {
                        $product = $itemModels->get($itemIndex)?->product ?: $productsByName->get($item['name'] ?? '');
                        $item['name_ar'] = $product?->name_ar ?: ($item['name_ar'] ?? $item['name'] ?? null);
                        $item['description_ar'] = $product?->description_ar ?: ($item['description_ar'] ?? $item['description'] ?? null);

                        return $item;
                    })
                    ->values()
                    ->all();

                return $section;
            })
            ->values()
            ->all();

        $pricingNames = collect($snapshot['pricing_items'] ?? [])->pluck('name')->filter()->unique();
        $packagesByName = CateringPackage::query()->whereIn('name', $pricingNames)->get()->keyBy('name');
        $productIds = $quote->items->pluck('product_id')->filter()->unique();
        $productsById = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $snapshot['pricing_items'] = collect($snapshot['pricing_items'] ?? [])
            ->map(function (array $item, int $index) use ($quote, $packagesByName, $productsById) {
                $itemModel = $quote->items->values()->get($index);
                $translation = $itemModel?->product_id
                    ? $productsById->get($itemModel->product_id)
                    : $packagesByName->get($item['name'] ?? '');
                $item['name_ar'] = $translation?->name_ar ?: ($item['name_ar'] ?? $item['name'] ?? null);
                $item['description_ar'] = $translation?->description_ar ?: ($item['description_ar'] ?? $item['description'] ?? null);

                return $item;
            })
            ->values()
            ->all();

        $primaryPackage = $packagesByName->first();
        $proposal = $snapshot['proposal'] ?? [];
        $eventTypeTranslations = [
            'Corporate Event'            => 'فعالية شركات',
            'Wedding'                    => 'حفل زفاف',
            'Private Party'              => 'مناسبة خاصة',
            'Government / Institutional' => 'فعالية حكومية أو مؤسسية',
            'Conference / Exhibition'    => 'مؤتمر أو معرض',
            'Finger Food Reception'      => 'حفل استقبال بالمأكولات الخفيفة',
            'Catering Function'          => 'فعالية ضيافة',
        ];
        $proposal['subject_ar'] = 'عرض خدمات الضيافة';
        $proposal['event_type_ar'] = $eventTypeTranslations[$proposal['event_type'] ?? '']
            ?? $primaryPackage?->name_ar
            ?? ($proposal['event_type'] ?? null);
        $proposal['venue_ar'] = $proposal['venue_ar'] ?? ($proposal['venue'] ?? null);
        $proposal['setup_description_ar'] = $primaryPackage?->setup_description_ar
            ?: ($proposal['setup_description_ar'] ?? $proposal['setup_description'] ?? null);
        $proposal['service_inclusions_ar'] = $primaryPackage?->service_inclusions_ar
            ? preg_split('/\r\n|\r|\n/', $primaryPackage->service_inclusions_ar)
            : ($proposal['service_inclusions_ar'] ?? $proposal['service_inclusions'] ?? []);
        $snapshot['proposal'] = $proposal;
        $snapshot['company']['company_name_ar'] = $snapshot['company']['company_name_ar'] ?? 'تيارا للضيافة';
        $snapshot['company']['tagline_ar'] = $snapshot['company']['tagline_ar'] ?? 'خدمات ضيافة راقية - المملكة العربية السعودية';
        $snapshot['company']['proposal_title_ar'] = $snapshot['company']['proposal_title_ar'] ?? 'عرض خدمات الضيافة';

        return $snapshot;
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
