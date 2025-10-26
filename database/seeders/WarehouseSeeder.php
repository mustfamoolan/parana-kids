<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSize;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users
        $admin = User::where('role', 'admin')->first();
        $supplier = User::where('role', 'supplier')->first();
        $delegates = User::where('role', 'delegate')->get();

        // Create warehouses
        $warehouse1 = Warehouse::create([
            'name' => 'مخزن الرياض الرئيسي',
            'location' => 'الرياض، المملكة العربية السعودية',
            'created_by' => $admin->id,
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'مخزن جدة',
            'location' => 'جدة، المملكة العربية السعودية',
            'created_by' => $admin->id,
        ]);

        $warehouse3 = Warehouse::create([
            'name' => 'مخزن الدمام',
            'location' => 'الدمام، المملكة العربية السعودية',
            'created_by' => $admin->id,
        ]);

        // Assign users to warehouses
        // Admin can access all warehouses
        $warehouse1->users()->attach($admin->id, ['can_manage' => true]);
        $warehouse2->users()->attach($admin->id, ['can_manage' => true]);
        $warehouse3->users()->attach($admin->id, ['can_manage' => true]);

        // Supplier can manage warehouse 1 and 2
        $warehouse1->users()->attach($supplier->id, ['can_manage' => true]);
        $warehouse2->users()->attach($supplier->id, ['can_manage' => true]);

        // Delegates can access different warehouses
        $warehouse1->users()->attach($delegates[0]->id, ['can_manage' => false]);
        $warehouse2->users()->attach($delegates[0]->id, ['can_manage' => false]);

        $warehouse2->users()->attach($delegates[1]->id, ['can_manage' => false]);
        $warehouse3->users()->attach($delegates[1]->id, ['can_manage' => false]);

        $warehouse3->users()->attach($delegates[2]->id, ['can_manage' => false]);

        // Create sample products
        $products = [
            [
                'name' => 'قميص قطني أزرق',
                'code' => 'SHIRT001',
                'purchase_price' => 25.00,
                'selling_price' => 45.00,
                'description' => 'قميص قطني عالي الجودة باللون الأزرق',
                'sizes' => [
                    ['size_name' => 'S', 'quantity' => 20],
                    ['size_name' => 'M', 'quantity' => 30],
                    ['size_name' => 'L', 'quantity' => 25],
                    ['size_name' => 'XL', 'quantity' => 15],
                ]
            ],
            [
                'name' => 'بنطلون جينز أسود',
                'code' => 'JEANS001',
                'purchase_price' => 35.00,
                'selling_price' => 65.00,
                'description' => 'بنطلون جينز كلاسيكي باللون الأسود',
                'sizes' => [
                    ['size_name' => '28', 'quantity' => 10],
                    ['size_name' => '30', 'quantity' => 15],
                    ['size_name' => '32', 'quantity' => 20],
                    ['size_name' => '34', 'quantity' => 18],
                    ['size_name' => '36', 'quantity' => 12],
                ]
            ],
            [
                'name' => 'حذاء رياضي أبيض',
                'code' => 'SHOE001',
                'purchase_price' => 50.00,
                'selling_price' => 90.00,
                'description' => 'حذاء رياضي مريح باللون الأبيض',
                'sizes' => [
                    ['size_name' => '38', 'quantity' => 8],
                    ['size_name' => '39', 'quantity' => 12],
                    ['size_name' => '40', 'quantity' => 15],
                    ['size_name' => '41', 'quantity' => 18],
                    ['size_name' => '42', 'quantity' => 10],
                ]
            ],
            [
                'name' => 'جاكيت شتوي رمادي',
                'code' => 'JACKET001',
                'purchase_price' => 60.00,
                'selling_price' => 110.00,
                'description' => 'جاكيت شتوي دافئ باللون الرمادي',
                'sizes' => [
                    ['size_name' => 'S', 'quantity' => 5],
                    ['size_name' => 'M', 'quantity' => 8],
                    ['size_name' => 'L', 'quantity' => 12],
                    ['size_name' => 'XL', 'quantity' => 6],
                ]
            ],
            [
                'name' => 'قميص رسمي أبيض',
                'code' => 'FORMAL001',
                'purchase_price' => 30.00,
                'selling_price' => 55.00,
                'description' => 'قميص رسمي أنيق باللون الأبيض',
                'sizes' => [
                    ['size_name' => 'S', 'quantity' => 15],
                    ['size_name' => 'M', 'quantity' => 25],
                    ['size_name' => 'L', 'quantity' => 20],
                    ['size_name' => 'XL', 'quantity' => 10],
                ]
            ]
        ];

        // Create products for each warehouse
        foreach ([$warehouse1, $warehouse2, $warehouse3] as $warehouse) {
            foreach ($products as $productData) {
                $product = Product::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => $productData['name'],
                    'code' => $productData['code'] . '_' . $warehouse->id,
                    'purchase_price' => $productData['purchase_price'],
                    'selling_price' => $productData['selling_price'],
                    'description' => $productData['description'],
                    'created_by' => $supplier->id,
                ]);

                // Add sizes
                foreach ($productData['sizes'] as $sizeData) {
                    ProductSize::create([
                        'product_id' => $product->id,
                        'size_name' => $sizeData['size_name'],
                        'quantity' => $sizeData['quantity'],
                    ]);
                }
            }
        }
    }
}
