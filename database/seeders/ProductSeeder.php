<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouseId = 2;
        $createdBy = 1; // المدير

        // أنواع المنتجات
        $genderTypes = ['boys', 'girls', 'accessories', 'boys_girls'];

        // أسماء المنتجات المحتملة
        $productNames = [
            'قميص', 'بنطلون', 'جاكيت', 'فستان', 'تنورة', 'بلوزة', 'تيشيرت', 'شورت',
            'جينز', 'كارديجان', 'هودي', 'سويتر', 'معطف', 'كاب', 'قفازات', 'جوارب',
            'حذاء', 'حقيبة', 'ساعة', 'نظارة', 'سلسلة', 'خاتم', 'أقراط', 'سوار',
            'حزام', 'وشاح', 'قبعة', 'شال', 'جاكيت رياضي', 'بنطلون رياضي', 'قميص رياضي'
        ];

        // القياسات المحتملة
        $possibleSizes = [
            'XS', 'S', 'M', 'L', 'XL', 'XXL',
            '28', '30', '32', '34', '36', '38', '40', '42', '44',
            '2', '4', '6', '8', '10', '12', '14', '16',
            '100', '110', '120', '130', '140', '150', '160'
        ];

        $this->command->info('بدء إنشاء 500 منتج عشوائي...');

        for ($i = 1; $i <= 500; $i++) {
            // اختيار نوع المنتج عشوائياً
            $genderType = $genderTypes[array_rand($genderTypes)];

            // اختيار اسم المنتج عشوائياً
            $productName = $productNames[array_rand($productNames)];
            $name = $productName . ' ' . Str::random(4);

            // إنشاء كود فريد
            $code = 'PRD-' . str_pad($i, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4));

            // أسعار عشوائية (بالدينار العراقي - بدون فاصلة عشرية)
            $purchasePrice = rand(5000, 50000); // من 5000 إلى 50000
            $sellingPrice = $purchasePrice + rand(10000, 30000); // سعر البيع أكبر من سعر الشراء

            // وصف عشوائي (اختياري)
            $description = rand(0, 1) ? 'منتج عالي الجودة' : null;

            // رابط 1688 (اختياري)
            $link1688 = rand(0, 1) ? 'https://detail.1688.com/offer/' . rand(1000000, 9999999) . '.html' : null;

            // إنشاء المنتج
            $product = Product::create([
                'warehouse_id' => $warehouseId,
                'name' => $name,
                'code' => $code,
                'gender_type' => $genderType,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'description' => $description,
                'link_1688' => $link1688,
                'created_by' => $createdBy,
                'is_hidden' => false,
                'discount_type' => 'none',
                'discount_value' => null,
                'discount_start_date' => null,
                'discount_end_date' => null,
            ]);

            // إضافة قياسات عشوائية (من 1 إلى 5 قياسات لكل منتج)
            $numberOfSizes = rand(1, 5);
            $selectedSizeIndices = [];

            // اختيار قياسات عشوائية بدون تكرار
            $availableIndices = array_keys($possibleSizes);
            shuffle($availableIndices);
            $selectedSizeIndices = array_slice($availableIndices, 0, min($numberOfSizes, count($possibleSizes)));

            foreach ($selectedSizeIndices as $sizeIndex) {
                $sizeName = $possibleSizes[$sizeIndex];
                $quantity = rand(0, 100); // كمية عشوائية من 0 إلى 100

                $size = ProductSize::create([
                    'product_id' => $product->id,
                    'size_name' => $sizeName,
                    'quantity' => $quantity,
                ]);

                // تسجيل حركة الإضافة
                ProductMovement::create([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $createdBy,
                    'movement_type' => 'add',
                    'quantity' => $quantity,
                    'balance_after' => $quantity,
                    'notes' => "إضافة منتج جديد: {$name}",
                ]);
            }

            // عرض التقدم كل 50 منتج
            if ($i % 50 == 0) {
                $this->command->info("تم إنشاء {$i} منتج...");
            }
        }

        $this->command->info('تم إنشاء 500 منتج بنجاح!');
    }
}

