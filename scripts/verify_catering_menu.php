<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Webkul\Admin\Http\Controllers\Products\CateringMenuController;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Product\Models\CateringPackage;
use Webkul\Product\Models\Product;
use Webkul\User\Models\User;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::query()->where('status', true)->firstOrFail();
$productIds = Product::query()->where('is_active', true)->limit(2)->pluck('id')->all();

assert(count($productIds) > 0, 'At least one active catering product is required.');

auth()->guard('user')->login($user);
app('view')->share('errors', new ViewErrorBag);

$controller = app(CateringMenuController::class);
$indexHtml = $controller->index()->render();
$createHtml = $controller->create()->render();

assert(str_contains($indexHtml, 'Catering Menus'));
assert(str_contains($createHtml, 'Create Catering Menu'));
assert(str_contains($createHtml, 'Choose Dishes'));
assert(str_contains($createHtml, 'product_ids[]'));

DB::beginTransaction();

try {
    $name = 'Verification Menu '.uniqid();
    $request = Request::create('/admin/catering-menus', 'POST', [
        'name'               => $name,
        'description'        => 'Reusable menu verification',
        'price_per_person'   => 283,
        'minimum_guests'     => 50,
        'setup_description'  => 'Buffet setup',
        'service_inclusions' => "Waiters\nServing equipment",
        'is_active'          => 1,
        'product_ids'        => $productIds,
    ]);
    $response = $controller->store($request);
    $menu = CateringPackage::query()->where('name', $name)->with('items')->firstOrFail();

    assert($response->isRedirect(route('admin.catering.menus.index')));
    assert($menu->items->count() === count($productIds));
    assert((float) $menu->price_per_person === 283.0);

    $quoteRoute = app('router')->getRoutes()->getByName('admin.quotes.create');
    $quoteRoute->bind(request());
    request()->setRouteResolver(fn () => $quoteRoute);
    $proposalHtml = app(QuoteController::class)->create()->render();
    assert(str_contains($proposalHtml, $name));
    assert(str_contains($proposalHtml, '+ Create Menu'));
    assert(str_contains($proposalHtml, Product::find($productIds[0])->getRawOriginal('name')));
    assert(str_contains($proposalHtml, 'Edit ${section.items.length} dishes'));

    echo json_encode([
        'index_rendered'       => true,
        'create_rendered'      => true,
        'menu_saved'           => true,
        'dishes_saved'         => $menu->items->count(),
        'proposal_integration' => true,
    ], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    DB::rollBack();
}
