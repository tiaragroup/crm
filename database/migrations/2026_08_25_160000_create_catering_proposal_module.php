<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_menu_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('catering_menu_category_id')
                ->nullable()
                ->after('description')
                ->constrained('catering_menu_categories')
                ->nullOnDelete();
            $table->string('unit_type')->default('menu_item')->after('catering_menu_category_id');
            $table->text('allergens')->nullable()->after('unit_type');
            $table->unsignedInteger('sort_order')->default(0)->after('allergens');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        Schema::create('catering_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->text('setup_description')->nullable();
            $table->text('service_inclusions')->nullable();
            $table->decimal('price_per_person', 12, 4)->default(0);
            $table->unsignedInteger('minimum_guests')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('catering_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catering_package_id')->constrained('catering_packages')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catering_package_id', 'product_id'], 'catering_package_product_unique');
        });

        Schema::create('catering_proposal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Tiara Catering');
            $table->string('tagline')->nullable();
            $table->string('proposal_title')->default('Catering Function Proposal');
            $table->string('reference_prefix')->default('TC');
            $table->unsignedInteger('validity_days')->default(30);
            $table->decimal('vat_percent', 5, 2)->default(15);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#D7A052');
            $table->string('secondary_color')->default('#356D24');
            $table->text('greeting_template')->nullable();
            $table->text('pricing_terms')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('changes_terms')->nullable();
            $table->text('cancellation_terms')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->timestamps();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('proposal_reference')->nullable()->unique()->after('id');
            $table->string('status')->default('draft')->after('proposal_reference');
            $table->unsignedInteger('revision')->default(1)->after('status');
            $table->date('issued_at')->nullable()->after('revision');
            $table->string('client_company')->nullable()->after('description');
            $table->string('attention_name')->nullable()->after('client_company');
            $table->string('client_mobile')->nullable()->after('attention_name');
            $table->string('client_email')->nullable()->after('client_mobile');
            $table->text('greeting')->nullable()->after('client_email');
            $table->string('event_type')->nullable()->after('greeting');
            $table->dateTime('event_at')->nullable()->after('event_type');
            $table->string('venue')->nullable()->after('event_at');
            $table->text('setup_description')->nullable()->after('venue');
            $table->unsignedInteger('guest_count')->nullable()->after('setup_description');
            $table->text('service_inclusions')->nullable()->after('guest_count');
            $table->decimal('vat_percent', 5, 2)->default(15)->after('service_inclusions');
            $table->text('pricing_terms')->nullable()->after('vat_percent');
            $table->text('payment_terms')->nullable()->after('pricing_terms');
            $table->text('changes_terms')->nullable()->after('payment_terms');
            $table->text('cancellation_terms')->nullable()->after('changes_terms');
            $table->string('bank_account_name')->nullable()->after('cancellation_terms');
            $table->string('bank_name')->nullable()->after('bank_account_name');
            $table->string('iban')->nullable()->after('bank_name');
            $table->string('company_signatory_name')->nullable()->after('iban');
            $table->string('company_signatory_title')->nullable()->after('company_signatory_name');
            $table->string('client_signatory_name')->nullable()->after('company_signatory_title');
            $table->string('client_signatory_title')->nullable()->after('client_signatory_name');
            $table->json('document_snapshot')->nullable()->after('client_signatory_title');
        });

        Schema::create('quote_menu_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quote_id');
            $table->foreign('quote_id')->references('id')->on('quotes')->cascadeOnDelete();
            $table->foreignId('catering_menu_category_id')
                ->nullable()
                ->constrained('catering_menu_categories')
                ->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quote_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_menu_section_id')->constrained('quote_menu_sections')->cascadeOnDelete();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->change();
            $table->text('description')->nullable()->after('name');
            $table->string('pricing_type')->default('per_person')->after('description');
            $table->unsignedInteger('guest_count')->nullable()->after('quantity');
            $table->boolean('is_included')->default(false)->after('guest_count');
            $table->unsignedInteger('sort_order')->default(0)->after('is_included');
        });

        $this->seedProposalSettings();
        $this->seedMenuCatalog();
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable(false)->change();
            $table->dropColumn(['description', 'pricing_type', 'guest_count', 'is_included', 'sort_order']);
        });

        Schema::dropIfExists('quote_menu_items');
        Schema::dropIfExists('quote_menu_sections');

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['proposal_reference']);
            $table->dropColumn([
                'proposal_reference',
                'status',
                'revision',
                'issued_at',
                'client_company',
                'attention_name',
                'client_mobile',
                'client_email',
                'greeting',
                'event_type',
                'event_at',
                'venue',
                'setup_description',
                'guest_count',
                'service_inclusions',
                'vat_percent',
                'pricing_terms',
                'payment_terms',
                'changes_terms',
                'cancellation_terms',
                'bank_account_name',
                'bank_name',
                'iban',
                'company_signatory_name',
                'company_signatory_title',
                'client_signatory_name',
                'client_signatory_title',
                'document_snapshot',
            ]);
        });

        Schema::dropIfExists('catering_proposal_settings');
        Schema::dropIfExists('catering_package_items');
        Schema::dropIfExists('catering_packages');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catering_menu_category_id');
            $table->dropColumn(['unit_type', 'allergens', 'sort_order', 'is_active']);
        });

        Schema::dropIfExists('catering_menu_categories');
    }

    private function seedProposalSettings(): void
    {
        DB::table('catering_proposal_settings')->insert([
            'company_name'       => 'Tiara Catering',
            'tagline'            => 'Premier Catering Services - Saudi Arabia',
            'proposal_title'     => 'Catering Function Proposal',
            'reference_prefix'   => 'TC',
            'validity_days'      => 30,
            'vat_percent'        => 15,
            'phone'              => '+966 920 005 600',
            'email'              => 'mohammed@tiaracatering.com',
            'website'            => 'www.tiaracatering.com',
            'primary_color'      => '#D7A052',
            'secondary_color'    => '#356D24',
            'greeting_template'  => 'Thank you for your interest in Tiara Catering to cater for your upcoming event. With our signature catering and fine-dining services, we are pleased to present this proposal in response to our understanding of your catering requirements.',
            'pricing_terms'      => 'Proposed rates and amounts are in Saudi Riyals (SAR). VAT is applied at 15% as shown in the pricing table.',
            'payment_terms'      => 'One hundred percent (100%) advance payment is required and payable upon acceptance and approval of this proposal. An invoice will be issued accordingly ahead of the event.',
            'changes_terms'      => 'Any change to the event date or venue is subject to the availability of Tiara Catering, and costs are also subject to change.',
            'cancellation_terms' => 'If the client cancels six (6) days before the event, fifty percent (50%) of the total proposed amount (including VAT) becomes due and payable. If the client cancels five (5) days or fewer before the event, one hundred percent (100%) becomes due and payable.',
            'bank_account_name'  => 'Tiara Catering',
            'bank_name'          => 'Al Rajhi Bank',
            'iban'               => 'SA8380000355608013235354',
            'signatory_name'     => 'Mohammed Alolyan',
            'signatory_title'    => 'Tiara Group / Tiara Catering',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function seedMenuCatalog(): void
    {
        $catalog = [
            'Sandwiches & Canapes' => [
                'Muhammara Canape with Walnut & Pomegranate',
                'Chicken Caesar Pita',
                'Feta, Rocca & Red Bell Pepper Vegetable Tortilla Roll',
                'Labneh Sliced Bagel',
                'Tuna Mayo Pesto with Rocca & Jalapeno Pita',
                'Halloumi Bagel',
                'Beetroot Hummus on Multiseed Bread',
            ],
            'Hot Mini Bites' => [
                'Cheese Arancini',
                'Cheese Sambousek',
                'Potato Samosa',
                'Mini Pizza',
                'Mini Zaatar',
            ],
            'Bakery'       => ['Assorted Danish', 'Assorted Croissant'],
            'Desserts'     => ['Carrot Cake', 'Chocolate Brownies', 'Banana Bread', 'Marble Cake', 'Date Pudding'],
            'Fresh Fruits' => ['Watermelon', 'Sweet Melon', 'Seasonal Fruits'],
            'Beverages'    => ['Soft Drinks', 'Coffee', 'Tea', 'Water'],
        ];

        $productIds = [];
        $categorySort = 1;
        $skuSequence = 1;

        foreach ($catalog as $categoryName => $products) {
            $categoryId = DB::table('catering_menu_categories')->insertGetId([
                'name'       => $categoryName,
                'sort_order' => $categorySort++,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($products as $productSort => $name) {
                $sku = sprintf('CAT-%03d', $skuSequence++);

                DB::table('products')->insertOrIgnore([
                    'sku'                       => $sku,
                    'name'                      => $name,
                    'description'               => null,
                    'catering_menu_category_id' => $categoryId,
                    'unit_type'                 => 'menu_item',
                    'allergens'                 => null,
                    'sort_order'                => $productSort + 1,
                    'is_active'                 => true,
                    'quantity'                  => 0,
                    'price'                     => 0,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);

                $productId = DB::table('products')->where('sku', $sku)->value('id');

                if ($productId) {
                    $productIds[] = $productId;
                }
            }
        }

        $packageId = DB::table('catering_packages')->insertGetId([
            'name'               => 'Finger Food Reception',
            'description'        => 'Sandwiches and canapes, hot mini bites, bakery, desserts, fresh fruits and beverages.',
            'setup_description'  => 'Finger food reception - no buffet tables required. Items presented on serving platters, ready to serve, with on-site service team, delivery and setup.',
            'service_inclusions' => "Finger food presented on serving platters - no buffet tables required\nDisposable plates, napkins and cups\nProfessional service team, delivery, setup and clearing",
            'price_per_person'   => 150,
            'minimum_guests'     => null,
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        foreach ($productIds as $sortOrder => $productId) {
            DB::table('catering_package_items')->insert([
                'catering_package_id' => $packageId,
                'product_id'          => $productId,
                'sort_order'          => $sortOrder + 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
};
