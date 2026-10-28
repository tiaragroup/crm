<?php

namespace Tests\Tax;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Admin\Http\Middleware\Locale;
use Webkul\Tax\Services\SellerService;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

abstract class TaxTestCase extends TestCase
{
    protected $actor;

    protected function setUp(): void
    {
        parent::setUp();
        // Fail closed before ANY schema work. Never use RefreshDatabase against the CRM.
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Use phpunit.tax.xml: tax tests require SQLite :memory:.');
        }
        DB::statement('PRAGMA foreign_keys = ON');
        foreach ([
            'packages/Webkul/User/src/Database/Migrations/*create_roles_table.php',
            'packages/Webkul/User/src/Database/Migrations/*create_users_table.php',
            'packages/Webkul/User/src/Database/Migrations/*create_groups_table.php',
            'packages/Webkul/User/src/Database/Migrations/*create_user_groups_table.php',
            'packages/Webkul/Admin/src/Database/Migrations/*add_lead_view_permission_column_in_users_table.php',
            'packages/Webkul/Contact/src/Database/Migrations/*create_organizations_table.php',
            'packages/Webkul/Contact/src/Database/Migrations/*create_persons_table.php',
            'packages/Webkul/Contact/src/Database/Migrations/*add_user_id_column_in_persons_table.php',
            'packages/Webkul/Contact/src/Database/Migrations/*add_user_id_column_in_organizations_table.php',
            'packages/Webkul/Product/src/Database/Migrations/*create_products_table.php',
            'packages/Webkul/Quote/src/Database/Migrations/*create_quotes_table.php',
            'packages/Webkul/Quote/src/Database/Migrations/*create_quote_items_table.php',
            'database/migrations/2026_08_25_160000_create_catering_proposal_module.php',
            'packages/Webkul/Tax/src/Database/Migrations/*.php',
        ] as $pattern) {
            $paths = glob(base_path($pattern));
            $this->assertNotEmpty($paths, $pattern);
            foreach ($paths as $path) {
                (require $path)->up();
            }
        }
        // CustomAttribute lookups need the catalog, not production data.
        Schema::create('attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('entity_type');
        });
        $this->actor = $this->user('all', [], 'global');
        $this->actingAs($this->actor, 'user');
        $this->withoutMiddleware(Locale::class);
        // Exercise actual tax views without unrelated dashboard/mail database dependencies.
        View::prependNamespace('admin', __DIR__.'/Fixtures/views');
    }

    protected function user(string $permissionType = 'custom', array $permissions = [], string $scope = 'own'): User
    {
        $role = Role::create(['name' => 'Finance', 'permission_type' => $permissionType, 'permissions' => $permissions]);

        return User::create(['name' => 'Test user', 'email' => uniqid().'@example.test', 'password' => 'unused', 'status' => 1, 'role_id' => $role->id, 'view_permission' => $scope]);
    }

    protected function sellerInput(): array
    {
        return [
            'code'       => 'TIARA', 'legal_name_ar' => 'شركة تيارا', 'legal_name_en' => 'Tiara',
            'vat_number' => '300000000000003', 'is_active' => '1',
            'address'    => ['street' => 'شارع الاختبار', 'building_number' => '1234', 'district' => 'الحي', 'city' => 'الرياض', 'postal_code' => '12345', 'country_code' => 'SA'],
        ];
    }

    protected function draftInput(): array
    {
        $seller = app(SellerService::class)->save($this->sellerInput(), $this->actor);

        return [
            'uuid'    => (string) Str::uuid(), 'seller_id' => $seller->id,
            'subject' => 'Catering draft', 'profile' => 'standard', 'purpose' => 'ordinary',
            'buyer'   => ['name_ar' => 'عميل الاختبار'],
            'items'   => [['description_ar' => 'خدمة الضيافة', 'quantity' => '3', 'unit_price' => '0.10', 'unit_code' => 'C62', 'price_basis' => 'exclusive', 'tax_category' => 'S', 'tax_rate' => '15']],
        ];
    }
}
