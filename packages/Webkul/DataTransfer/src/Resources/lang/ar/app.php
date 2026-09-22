<?php

return [
    'importers' => [
        'persons' => [
            'title'      => 'الأشخاص',
            'validation' => [
                'errors' => [
                    'duplicate-email' => 'البريد الإلكتروني: "%s" موجود أكثر من مرة في ملف الاستيراد.',
                    'duplicate-phone' => 'رقم الهاتف: "%s" موجود أكثر من مرة في ملف الاستيراد.',
                    'email-not-found' => 'البريد الإلكتروني: "%s" غير موجود في النظام.',
                ],
            ],
        ],
        'products' => [
            'title'      => 'المنتجات',
            'validation' => [
                'errors' => [
                    'sku-not-found' => 'لم يتم العثور على منتج برمز SKU المحدد.',
                ],
            ],
        ],
        'leads' => [
            'title'      => 'فرص المبيعات',
            'validation' => [
                'errors' => [
                    'id-not-found' => 'المعرّف: "%s" غير موجود في النظام.',
                ],
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'أرقام الأعمدة "%s" تحتوي على عناوين فارغة.',
            'column-name-invalid'  => 'أسماء الأعمدة غير صالحة: "%s".',
            'column-not-found'     => 'الأعمدة المطلوبة غير موجودة: %s.',
            'column-numbers'       => 'عدد الأعمدة لا يتوافق مع عدد الصفوف في الترويسة.',
            'invalid-attribute'    => 'تحتوي الترويسة على سمات غير صالحة: "%s".',
            'system'               => 'حدث خطأ غير متوقع في النظام.',
            'wrong-quotes'         => 'استُخدمت علامات اقتباس منحنية بدلاً من علامات الاقتباس المستقيمة.',
            'already-exists'       => 'قيمة :attribute موجودة مسبقاً.',
        ],
    ],
];
