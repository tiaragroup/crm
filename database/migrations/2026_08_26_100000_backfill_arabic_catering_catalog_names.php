<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fill only missing Arabic names in the original Tiara catering catalog.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'name_ar')) {
            return;
        }

        $translations = [
            'Muhammara Canape with Walnut & Pomegranate'             => 'كانابيه محمرة بالجوز والرمان',
            'Chicken Caesar Pita'                                    => 'خبز بيتا بالدجاج والسيزر',
            'Feta, Rocca & Red Bell Pepper Vegetable Tortilla Roll'  => 'تورتيلا بالخضار وجبنة الفيتا والجرجير والفلفل الأحمر',
            'Labneh Sliced Bagel'                                    => 'بيغل باللبنة',
            'Tuna Mayo Pesto with Rocca & Jalapeno Pita'             => 'خبز بيتا بالتونة والمايونيز والبيستو والجرجير والهالبينو',
            'Halloumi Bagel'                                         => 'بيغل بجبنة الحلوم',
            'Beetroot Hummus on Multiseed Bread'                     => 'حمص الشمندر على خبز متعدد الحبوب',
            'Cheese Arancini'                                        => 'أرانشيني بالجبن',
            'Cheese Sambousek'                                       => 'سمبوسك بالجبن',
            'Potato Samosa'                                          => 'سمبوسة بالبطاطس',
            'Mini Pizza'                                             => 'ميني بيتزا',
            'Mini Zaatar'                                            => 'ميني مناقيش زعتر',
            'Assorted Danish'                                        => 'تشكيلة معجنات دنماركية',
            'Assorted Croissant'                                     => 'تشكيلة كرواسون',
            'Carrot Cake'                                            => 'كيك الجزر',
            'Chocolate Brownies'                                     => 'براونيز الشوكولاتة',
            'Banana Bread'                                           => 'خبز الموز',
            'Marble Cake'                                            => 'كيك رخامي',
            'Date Pudding'                                           => 'بودينغ التمر',
            'Watermelon'                                             => 'بطيخ',
            'Sweet Melon'                                            => 'شمام',
            'Seasonal Fruits'                                        => 'فواكه موسمية',
            'Soft Drinks'                                            => 'مشروبات غازية',
            'Coffee'                                                 => 'قهوة',
            'Tea'                                                    => 'شاي',
            'Water'                                                  => 'مياه',
        ];

        foreach ($translations as $english => $arabic) {
            DB::table('products')
                ->where('name', $english)
                ->where(function ($query) {
                    $query->whereNull('name_ar')->orWhere('name_ar', '');
                })
                ->update(['name_ar' => $arabic]);
        }
    }

    /**
     * Keep translations on rollback because they may have been edited after deployment.
     */
    public function down(): void
    {
        // Intentionally preserved.
    }
};
