<?php

namespace Tests\Tax;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Tax\Services\DraftService;
use Webkul\User\Models\Group;

class AuthorizationAndViewsTest extends TaxTestCase
{
    public function test_all_routes_have_acl_and_issuance_routes_do_not_exist(): void
    {
        $mapped = collect(config('acl'))->flatMap(fn ($entry) => (array) ($entry['route'] ?? []))->all();
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.tax_'));
        $this->assertCount(10, $routes);
        foreach ($routes as $route) {
            $this->assertContains($route->getName(), $mapped);
        }
        $this->assertFalse(Route::has('admin.tax_documents.issue'));
        $this->assertFalse(Route::has('admin.payments.store'));
    }

    public function test_guest_is_redirected_and_unprivileged_user_is_denied(): void
    {
        auth()->guard('user')->logout();
        $this->get(route('admin.tax_documents.index'))->assertRedirect(route('admin.session.create'));
        $viewer = $this->user('custom', ['tax_documents']);
        $this->actingAs($viewer, 'user');
        $this->get(route('admin.tax_documents.create'))->assertStatus(401);
        $this->postJson(route('admin.tax_documents.store'), [])->assertStatus(401);
        $this->get(route('admin.tax_sellers.index'))->assertStatus(401);
        $this->postJson(route('admin.tax_sellers.store'), [])->assertStatus(401);
    }

    public function test_view_permission_does_not_allow_edit_or_direct_service_create(): void
    {
        $input = $this->draftInput();
        $document = app(DraftService::class)->save($input, $this->actor);
        $viewer = $this->user('custom', ['tax_documents'], 'global');
        $this->actingAs($viewer, 'user');
        $this->get(route('admin.tax_documents.edit', $document->id))->assertStatus(401);
        $this->putJson(route('admin.tax_documents.update', $document->id), $input)->assertStatus(401);
        $this->expectException(HttpException::class);
        app(DraftService::class)->save($input, $viewer);
    }

    public function test_owner_and_group_visibility_applies_to_list_and_edit(): void
    {
        $input = $this->draftInput();
        $document = app(DraftService::class)->save($input, $this->actor);
        $other = $this->user('custom', ['tax_documents', 'tax_documents.edit'], 'own');
        $this->actingAs($other, 'user');
        $this->get(route('admin.tax_documents.index'))->assertOk()->assertDontSee('Catering draft');
        $this->get(route('admin.tax_documents.edit', $document->id))->assertNotFound();
        $this->putJson(route('admin.tax_documents.update', $document->id), array_replace($input, ['lock_version' => 1]))->assertNotFound();
        $group = Group::create(['name' => 'Finance']);
        $group->users()->attach([$other->id, $this->actor->id]);
        $other->update(['view_permission' => 'group']);
        $this->get(route('admin.tax_documents.index'))->assertOk()->assertSee('Catering draft');
        $this->get(route('admin.tax_documents.edit', $document->id))->assertOk();
    }

    public function test_actual_tax_forms_render_in_both_languages(): void
    {
        $input = $this->draftInput();
        $document = app(DraftService::class)->save($input, $this->actor);
        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);
            foreach ([
                route('admin.tax_documents.index'), route('admin.tax_documents.create'),
                route('admin.tax_documents.edit', $document->id), route('admin.tax_sellers.index'),
                route('admin.tax_sellers.create'), route('admin.tax_sellers.edit', $document->seller_id),
            ] as $url) {
                $this->get($url)->assertOk()->assertSee(trans('admin::tax.draft-notice'))->assertDontSee('admin::tax.');
            }
        }
    }

    public function test_form_submission_and_validation_redisplay(): void
    {
        $input = $this->draftInput();
        $this->post(route('admin.tax_documents.store'), $input)->assertRedirect();
        $id = DB::table('tax_documents')->value('id');
        $this->get(route('admin.tax_documents.edit', $id))->assertOk()->assertSee('0.100000');
        $input['items'][0]['quantity'] = '0';
        $this->from(route('admin.tax_documents.create'))->post(route('admin.tax_documents.store'), $input)->assertSessionHasErrors('items.0.quantity');
        $this->get(route('admin.tax_documents.create'))->assertOk()->assertSee(trans('admin::tax.errors'));
    }
}
