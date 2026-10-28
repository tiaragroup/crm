<?php

namespace Webkul\Admin\Http\Controllers\Tax;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Tax\Models\TaxDocumentProxy;
use Webkul\Tax\Models\TaxSellerProxy;
use Webkul\Tax\Repositories\TaxDocumentRepository;
use Webkul\Tax\Services\DraftService;
use Webkul\Tax\Services\ProposalToDraft;
use Webkul\Tax\Services\TaxAccess;

class DraftController extends Controller
{
    public function __construct(protected TaxAccess $access, protected DraftService $drafts) {}

    public function index(Request $request, TaxDocumentRepository $repository)
    {
        $actor = auth()->guard('user')->user();
        $this->access->authorize($actor, 'tax_documents');
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = $this->access->scope($repository->getModel()->newQuery(), $actor)->with('seller');
        if ($term = trim((string) $request->query('q'))) {
            $query->where('subject', 'like', '%'.addcslashes($term, '\\%_').'%');
        }
        $documents = $query->latest('id')->paginate(20)->withQueryString();

        return view('admin::tax.documents.index', compact('documents'));
    }

    public function create(Request $request, ProposalToDraft $converter)
    {
        $actor = auth()->guard('user')->user();
        $this->access->authorize($actor, 'tax_documents.create');
        $request->validate(['quote_id' => ['nullable', 'integer']]);
        $form = $request->filled('quote_id') ? $converter->preview((int) $request->query('quote_id'), $actor) : [
            'uuid'  => (string) Str::uuid(), 'profile' => 'standard', 'purpose' => 'ordinary',
            'items' => [['quantity' => '1', 'unit_price' => '0', 'discount_amount' => '0', 'unit_code' => 'C62', 'price_basis' => 'exclusive']],
        ];

        return $this->form($form);
    }

    public function edit(int $id)
    {
        $actor = auth()->guard('user')->user();
        $this->access->authorize($actor, 'tax_documents.edit');
        $document = $this->access->scope(TaxDocumentProxy::query(), $actor)->with('items')->findOrFail($id);
        abort_unless($document->status === 'draft', 409);
        $form = $document->toArray();
        $form['buyer'] = $document->buyer_snapshot;
        $form['supply_date'] = $document->supply_date?->format('Y-m-d');
        $form['due_date'] = $document->due_date?->format('Y-m-d');

        return $this->form($form, $id);
    }

    protected function form(array $form, ?int $id = null)
    {
        $actor = auth()->guard('user')->user();
        $sellers = TaxSellerProxy::query()->where('is_active', true)->orderBy('code')->get();
        $proposals = collect();
        if (! $id && bouncer()->hasPermission('quotes')) {
            $proposals = $this->access->scope(QuoteProxy::query(), $actor, 'user_id')->where('status', 'accepted')->latest('id')->limit(100)->get(['id', 'proposal_reference', 'subject']);
        }

        return view('admin::tax.documents.form', compact('form', 'id', 'sellers', 'proposals'));
    }

    public function store(Request $request)
    {
        $document = $this->drafts->save($request->all(), auth()->guard('user')->user());

        return redirect()->route(bouncer()->hasPermission('tax_documents.edit') ? 'admin.tax_documents.edit' : 'admin.tax_documents.index', ['id' => $document->id])
            ->with('success', trans('admin::tax.saved'));
    }

    public function update(Request $request, int $id)
    {
        $this->drafts->save($request->all(), auth()->guard('user')->user(), $id);

        return redirect()->route('admin.tax_documents.edit', $id)->with('success', trans('admin::tax.saved'));
    }
}
