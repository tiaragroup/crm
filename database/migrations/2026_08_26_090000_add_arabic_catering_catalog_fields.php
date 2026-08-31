<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'name_ar')) {
                    $table->string('name_ar')->nullable()->after('name');
                }

                if (! Schema::hasColumn('products', 'description_ar')) {
                    $table->text('description_ar')->nullable()->after('description');
                }

                if (! Schema::hasColumn('products', 'allergens_ar')) {
                    $table->text('allergens_ar')->nullable()->after('allergens');
                }
            });
        }

        if (Schema::hasTable('catering_menu_categories')) {
            Schema::table('catering_menu_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('catering_menu_categories', 'name_ar')) {
                    $table->string('name_ar')->nullable()->after('name');
                }

                if (! Schema::hasColumn('catering_menu_categories', 'description_ar')) {
                    $table->text('description_ar')->nullable()->after('description');
                }
            });

            foreach ([
                'Sandwiches & Canapes'  => 'السندويشات والمقبلات الصغيرة',
                'Hot Mini Bites'        => 'المقبلات الساخنة الصغيرة',
                'Bakery'                => 'المخبوزات',
                'Desserts'              => 'الحلويات',
                'Fresh Fruits'          => 'الفواكه الطازجة',
                'Beverages'             => 'المشروبات',
            ] as $english => $arabic) {
                DB::table('catering_menu_categories')
                    ->where('name', $english)
                    ->whereNull('name_ar')
                    ->update(['name_ar' => $arabic]);
            }
        }

        if (Schema::hasTable('catering_packages')) {
            Schema::table('catering_packages', function (Blueprint $table) {
                if (! Schema::hasColumn('catering_packages', 'name_ar')) {
                    $table->string('name_ar')->nullable()->after('name');
                }

                if (! Schema::hasColumn('catering_packages', 'description_ar')) {
                    $table->text('description_ar')->nullable()->after('description');
                }

                if (! Schema::hasColumn('catering_packages', 'setup_description_ar')) {
                    $table->text('setup_description_ar')->nullable()->after('setup_description');
                }

                if (! Schema::hasColumn('catering_packages', 'service_inclusions_ar')) {
                    $table->text('service_inclusions_ar')->nullable()->after('service_inclusions');
                }
            });

            DB::table('catering_packages')
                ->where('name', 'Finger Food Reception')
                ->whereNull('name_ar')
                ->update(['name_ar' => 'حفل استقبال بالمأكولات الخفيفة']);
        }
    }

    public function down(): void
    {
        $this->dropColumnsIfPresent('catering_packages', [
            'name_ar',
            'description_ar',
            'setup_description_ar',
            'service_inclusions_ar',
        ]);
        $this->dropColumnsIfPresent('catering_menu_categories', ['name_ar', 'description_ar']);
        $this->dropColumnsIfPresent('products', ['name_ar', 'description_ar', 'allergens_ar']);
    }

    private function dropColumnsIfPresent(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));

        if ($existingColumns) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existingColumns));
        }
    }
};
