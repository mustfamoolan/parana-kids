<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\DelegateLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\WarehouseController as AdminWarehouseController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Delegate\DashboardController as DelegateDashboardController;
use App\Http\Controllers\Delegate\WarehouseController as DelegateWarehouseController;
use App\Http\Controllers\Delegate\ProductController as DelegateProductController;
use App\Http\Controllers\Delegate\CartController;
use App\Http\Controllers\Delegate\CartItemController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\BulkReturnController;
use App\Http\Controllers\Admin\BulkExchangeReturnController;
use App\Http\Controllers\Admin\ProductMovementController as AdminProductMovementController;
use App\Http\Controllers\Delegate\OrderController as DelegateOrderController;
use App\Http\Controllers\Admin\ProductLinkController;
use App\Http\Controllers\Delegate\ProductLinkController as DelegateProductLinkController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\Admin\PrivateWarehouseController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\CartController as ShopCartController;
use App\Http\Controllers\Shop\CartItemController as ShopCartItemController;

// Redirect root to delegate login
Route::get('/', function () {
    return redirect()->route('delegate.login');
});

// Public routes (without authentication)
Route::get('/p/{token}', [PublicProductController::class, 'show'])->name('public.products.show');

// Shop routes (public, no authentication)
Route::prefix('shop')->group(function () {
    // Main pages
    Route::get('/', [ShopController::class, 'index'])->name('shop.index');
    Route::get('/products', [ShopController::class, 'index'])->name('shop.products');
    Route::get('/products/{product}', [ShopController::class, 'show'])->name('shop.products.show');

    // Cart routes
    Route::get('/cart', [ShopCartController::class, 'view'])->name('shop.cart.view');
    Route::post('/cart/items', [ShopCartItemController::class, 'store'])->name('shop.cart.items.store');
    Route::put('/cart/items/{cartItem}', [ShopCartItemController::class, 'update'])->name('shop.cart.items.update');
    Route::delete('/cart/items/{cartItem}', [ShopCartItemController::class, 'destroy'])->name('shop.cart.items.destroy');

    // API routes
    Route::get('/api/products/{product}', [ShopController::class, 'getProductData'])->name('shop.api.products.data');
});

// Admin/Supplier Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->middleware('guest')->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->middleware('guest');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

    // Protected admin routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Phone Book routes (Admin only)
        Route::prefix('phone-book')->name('admin.phone-book.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PhoneBookController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PhoneBookController::class, 'store'])->name('store');
            Route::post('/{contact}/add-phone', [\App\Http\Controllers\Admin\PhoneBookController::class, 'addPhone'])->name('add-phone');
            Route::delete('/phone/{phoneNumber}', [\App\Http\Controllers\Admin\PhoneBookController::class, 'deletePhone'])->name('delete-phone');
            Route::delete('/{contact}', [\App\Http\Controllers\Admin\PhoneBookController::class, 'deleteContact'])->name('delete-contact');
        });

        // Order Creation routes (Admin and Supplier)
        Route::prefix('orders/create')->name('admin.orders.create.')->group(function () {
            Route::get('/start', [\App\Http\Controllers\Admin\OrderCreationController::class, 'start'])->name('start');
            Route::post('/initialize', [\App\Http\Controllers\Admin\OrderCreationController::class, 'initialize'])->name('initialize');
            Route::post('/submit', [\App\Http\Controllers\Admin\OrderCreationController::class, 'submit'])->name('submit');
        });

        // Cart routes (Admin and Supplier)
        Route::get('carts/view', [\App\Http\Controllers\Admin\CartController::class, 'view'])->name('admin.carts.view');

        // Cart Items routes (Admin and Supplier)
        Route::post('carts/items', [\App\Http\Controllers\Admin\CartItemController::class, 'store'])->name('admin.carts.items.store');
        Route::put('cart-items/{cartItem}', [\App\Http\Controllers\Admin\CartItemController::class, 'update'])->name('admin.cart-items.update');
        Route::delete('cart-items/{cartItem}', [\App\Http\Controllers\Admin\CartItemController::class, 'destroy'])->name('admin.cart-items.destroy');

        // صفحة التقارير (للمدير فقط)
        Route::get('reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');

        // صفحة كشف مبيعات (للمدير فقط)
        Route::get('sales-report', [\App\Http\Controllers\Admin\SalesReportController::class, 'index'])->name('admin.sales-report');
        Route::get('sales-report/search-products', [\App\Http\Controllers\Admin\SalesReportController::class, 'searchProducts'])->name('admin.sales-report.search-products');

        // صفحة إنشاء فواتير PDF (للمدير فقط)
        Route::get('invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('admin.invoices.index');
        Route::get('invoices/my-invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'myInvoices'])->name('admin.invoices.my-invoices');
        Route::post('invoices/products', [\App\Http\Controllers\Admin\InvoiceController::class, 'storeProduct'])->name('admin.invoices.products.store');
        Route::put('invoices/products/{id}', [\App\Http\Controllers\Admin\InvoiceController::class, 'updateProduct'])->name('admin.invoices.products.update');
        Route::delete('invoices/products/{id}', [\App\Http\Controllers\Admin\InvoiceController::class, 'deleteProduct'])->name('admin.invoices.products.delete');
        Route::post('invoices/save', [\App\Http\Controllers\Admin\InvoiceController::class, 'saveInvoice'])->name('admin.invoices.save');
        Route::get('invoices/{id}/edit', [\App\Http\Controllers\Admin\InvoiceController::class, 'edit'])->name('admin.invoices.edit');
        Route::put('invoices/{id}', [\App\Http\Controllers\Admin\InvoiceController::class, 'update'])->name('admin.invoices.update');
        Route::delete('invoices/{id}', [\App\Http\Controllers\Admin\InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');
        Route::get('invoices/{id}/pdf', [\App\Http\Controllers\Admin\InvoiceController::class, 'downloadPdf'])->name('admin.invoices.pdf');

        // Settings routes (Admin and Supplier)
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.settings.update');
        Route::post('settings/profile', [\App\Http\Controllers\Admin\SettingController::class, 'updateProfile'])->name('admin.settings.profile');
        Route::post('settings/banner', [\App\Http\Controllers\Admin\SettingController::class, 'updateBanner'])->name('admin.settings.banner');
        Route::post('settings/banner/toggle', [\App\Http\Controllers\Admin\SettingController::class, 'toggleBanner'])->name('admin.settings.banner.toggle');
        Route::post('settings/dashboard-banner', [\App\Http\Controllers\Admin\SettingController::class, 'updateDashboardBanner'])->name('admin.settings.dashboard-banner');
        Route::post('settings/dashboard-banner/toggle', [\App\Http\Controllers\Admin\SettingController::class, 'toggleDashboardBanner'])->name('admin.settings.dashboard-banner.toggle');

        // AlWaseet routes (Admin only)
        Route::prefix('alwaseet')->name('admin.alwaseet.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\AlWaseetController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [\App\Http\Controllers\Admin\AlWaseetController::class, 'index'])->name('index');
            Route::get('/add-order-from-pending', [\App\Http\Controllers\Admin\AlWaseetController::class, 'addOrderFromPending'])->name('add-order-from-pending');
            Route::get('/print-and-upload-orders', [\App\Http\Controllers\Admin\AlWaseetController::class, 'printAndUploadOrders'])->name('print-and-upload-orders');
            Route::get('/track-orders', [\App\Http\Controllers\Admin\AlWaseetController::class, 'trackOrders'])->name('track-orders');
            Route::get('/materials-list', [\App\Http\Controllers\Admin\AlWaseetController::class, 'getMaterialsListForPrintUpload'])->name('materials-list');
            Route::get('/materials-list-grouped', [\App\Http\Controllers\Admin\AlWaseetController::class, 'getMaterialsListGroupedForPrintUpload'])->name('materials-list-grouped');
            Route::post('/orders/{order}/confirm', [\App\Http\Controllers\Admin\AlWaseetController::class, 'confirmOrder'])->name('orders.confirm');
            Route::get('/materials-list/orders/{order}/edit', [\App\Http\Controllers\Admin\AlWaseetController::class, 'editOrderFromMaterialsList'])->name('materials-list.orders.edit');
            Route::post('/materials-list/orders/{order}/update', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateOrderFromMaterialsList'])->name('materials-list.orders.update');
            Route::post('/print-all-orders', [\App\Http\Controllers\Admin\AlWaseetController::class, 'printAllOrders'])->name('print-all-orders');
            Route::get('/orders/{order}/download-pdf', [\App\Http\Controllers\Admin\AlWaseetController::class, 'downloadOrderPdf'])->name('orders.download-pdf');
            Route::post('/orders/{id}/update-alwaseet-fields', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateOrderAlwaseetFields'])->name('orders.update-alwaseet-fields');
            Route::post('/orders/{id}/update-delivery-time-note', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateDeliveryTimeNote'])->name('orders.update-delivery-time-note');
            Route::post('/orders/{id}/send', [\App\Http\Controllers\Admin\AlWaseetController::class, 'sendOrderToAlWaseet'])->name('orders.send');
            Route::delete('/orders/{order}/delete', [\App\Http\Controllers\Admin\AlWaseetController::class, 'deleteOrder'])->name('orders.delete');

            // Orders routes
            Route::get('/orders', [\App\Http\Controllers\Admin\AlWaseetController::class, 'orders'])->name('orders');
            Route::get('/orders/create', [\App\Http\Controllers\Admin\AlWaseetController::class, 'createOrder'])->name('orders.create');
            Route::post('/orders', [\App\Http\Controllers\Admin\AlWaseetController::class, 'storeOrder'])->name('orders.store');
            Route::get('/orders/{id}/edit', [\App\Http\Controllers\Admin\AlWaseetController::class, 'editOrder'])->name('orders.edit');
            Route::post('/orders/{id}', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateOrder'])->name('orders.update');
            Route::get('/api/regions', [\App\Http\Controllers\Admin\AlWaseetController::class, 'getRegions'])->name('api.regions');

            // Receipts routes
            Route::get('/receipts', [\App\Http\Controllers\Admin\AlWaseetController::class, 'receipts'])->name('receipts');
            Route::get('/receipts/{id}/download', [\App\Http\Controllers\Admin\AlWaseetController::class, 'downloadReceipt'])->name('receipts.download');
            Route::get('/receipts/download-by-link', [\App\Http\Controllers\Admin\AlWaseetController::class, 'downloadReceiptPdfByLink'])->name('receipts.download-by-link');

            // Invoices routes
            Route::get('/invoices', [\App\Http\Controllers\Admin\AlWaseetController::class, 'invoices'])->name('invoices.index');
            Route::get('/invoices/{id}', [\App\Http\Controllers\Admin\AlWaseetController::class, 'showInvoice'])->name('invoices.show');
            Route::post('/invoices/{id}/receive', [\App\Http\Controllers\Admin\AlWaseetController::class, 'receiveInvoice'])->name('invoices.receive');

            // Settings routes
            Route::get('/settings', [\App\Http\Controllers\Admin\AlWaseetController::class, 'settings'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateSettings'])->name('settings.update');
            Route::post('/logout', [\App\Http\Controllers\Admin\AlWaseetController::class, 'logout'])->name('logout');
            Route::post('/reconnect', [\App\Http\Controllers\Admin\AlWaseetController::class, 'reconnect'])->name('reconnect');
            Route::get('/test-connection', [\App\Http\Controllers\Admin\AlWaseetController::class, 'testConnection'])->name('test-connection');
            Route::post('/sync', [\App\Http\Controllers\Admin\AlWaseetController::class, 'sync'])->name('sync');

            // Auto Integration routes
            Route::get('/auto-integration', [\App\Http\Controllers\Admin\AlWaseetController::class, 'autoIntegration'])->name('auto-integration');
            Route::post('/auto-integration', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateAutoIntegration'])->name('auto-integration.update');

            // Auto Sync routes
            Route::get('/auto-sync', [\App\Http\Controllers\Admin\AlWaseetController::class, 'autoSync'])->name('auto-sync');
            Route::post('/auto-sync', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateAutoSync'])->name('auto-sync.update');

            // Notifications routes
            Route::get('/notifications', [\App\Http\Controllers\Admin\AlWaseetController::class, 'notifications'])->name('notifications');
            Route::post('/notifications', [\App\Http\Controllers\Admin\AlWaseetController::class, 'updateNotifications'])->name('notifications.update');
            Route::post('/notifications/{id}/read', [\App\Http\Controllers\Admin\AlWaseetController::class, 'markNotificationAsRead'])->name('notifications.read');
            Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\AlWaseetController::class, 'markAllNotificationsAsRead'])->name('notifications.read-all');

            // Reports routes
            Route::get('/reports', [\App\Http\Controllers\Admin\AlWaseetController::class, 'reports'])->name('reports');

            // Rate Limiting routes
            Route::get('/rate-limiting', [\App\Http\Controllers\Admin\AlWaseetController::class, 'rateLimiting'])->name('rate-limiting');

            // Show route (يجب أن يكون في النهاية لتجنب التعارض)
            Route::get('/{id}', [\App\Http\Controllers\Admin\AlWaseetController::class, 'show'])->name('show');
            Route::post('/{id}/link-order', [\App\Http\Controllers\Admin\AlWaseetController::class, 'linkToOrder'])->name('link-order');
            Route::post('/{id}/unlink-order', [\App\Http\Controllers\Admin\AlWaseetController::class, 'unlinkOrder'])->name('unlink-order');
        });

        // Expenses routes (Admin only)
        // يجب وضع search-products قبل resource route لتجنب التعارض
        Route::get('expenses/search-products', [\App\Http\Controllers\Admin\ExpenseController::class, 'searchProducts'])->name('admin.expenses.search-products');
        Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)->names([
            'index' => 'admin.expenses.index',
            'create' => 'admin.expenses.create',
            'store' => 'admin.expenses.store',
            'edit' => 'admin.expenses.edit',
            'update' => 'admin.expenses.update',
            'destroy' => 'admin.expenses.destroy',
        ])->except(['show']);

        // Private Warehouses routes (Admin only)
        Route::resource('private-warehouses', PrivateWarehouseController::class)->names([
            'index' => 'admin.private-warehouses.index',
            'create' => 'admin.private-warehouses.create',
            'store' => 'admin.private-warehouses.store',
            'show' => 'admin.private-warehouses.show',
            'edit' => 'admin.private-warehouses.edit',
            'update' => 'admin.private-warehouses.update',
            'destroy' => 'admin.private-warehouses.destroy',
        ]);

        // Warehouse routes
        Route::resource('warehouses', AdminWarehouseController::class)->names([
            'index' => 'admin.warehouses.index',
            'create' => 'admin.warehouses.create',
            'store' => 'admin.warehouses.store',
            'show' => 'admin.warehouses.show',
            'edit' => 'admin.warehouses.edit',
            'update' => 'admin.warehouses.update',
            'destroy' => 'admin.warehouses.destroy',
        ]);
        Route::get('warehouses/{warehouse}/assign-users', [AdminWarehouseController::class, 'assignUsers'])->name('admin.warehouses.assign-users');
        Route::post('warehouses/{warehouse}/update-users', [AdminWarehouseController::class, 'updateUsers'])->name('admin.warehouses.update-users');

        // Warehouse Promotion routes
        Route::get('warehouses/{warehouse}/promotion/active', [AdminWarehouseController::class, 'getActivePromotion'])->name('admin.warehouses.promotion.active');
        Route::post('warehouses/{warehouse}/promotion', [AdminWarehouseController::class, 'storePromotion'])->name('admin.warehouses.promotion.store');
        Route::post('warehouses/{warehouse}/promotion/toggle', [AdminWarehouseController::class, 'togglePromotion'])->name('admin.warehouses.promotion.toggle');
        Route::put('warehouses/{warehouse}/promotion/{promotion}', [AdminWarehouseController::class, 'updatePromotion'])->name('admin.warehouses.promotion.update');

        // User Management routes (Admin only)
        Route::resource('users', AdminUserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);

        // View supplier invoices (Admin only)
        Route::get('users/{userId}/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'viewSupplierInvoices'])->name('admin.users.invoices');

        // Product routes
        Route::get('products', [AdminProductController::class, 'allProducts'])->name('admin.products.index');
        Route::get('api/products/{product}', [AdminProductController::class, 'getProductData'])->name('admin.api.products.data');
        Route::resource('warehouses.products', AdminProductController::class)->names([
            'index' => 'admin.warehouses.products.index',
            'create' => 'admin.warehouses.products.create',
            'store' => 'admin.warehouses.products.store',
            'show' => 'admin.warehouses.products.show',
            'edit' => 'admin.warehouses.products.edit',
            'update' => 'admin.warehouses.products.update',
            'destroy' => 'admin.warehouses.products.destroy',
        ]);
        Route::post('warehouses/{warehouse}/products/{product}/toggle-hidden', [AdminProductController::class, 'toggleHidden'])->name('admin.warehouses.products.toggle-hidden');
        Route::post('warehouses/{warehouse}/products/{product}/discount', [AdminProductController::class, 'updateDiscount'])->name('admin.warehouses.products.discount');
        // Order routes

        // إدارة الطلبات (الصفحة الجديدة الموحدة)
        Route::get('orders-management', [AdminOrderController::class, 'management'])->name('admin.orders.management');
        Route::get('orders-pending', [AdminOrderController::class, 'pendingOrders'])->name('admin.orders.pending');
        Route::get('orders-confirmed', [AdminOrderController::class, 'confirmedOrders'])->name('admin.orders.confirmed');

        // الإرجاع الجزئي الجديد (يجب وضعه قبل orders/{order} لتجنب التعارض)
        Route::get('orders/partial-returns', [AdminOrderController::class, 'partialReturnsIndex'])->name('admin.orders.partial-returns.index');
        Route::get('orders/materials/list', [AdminOrderController::class, 'getMaterialsList'])->name('admin.orders.materials');
        Route::get('orders/materials/management', [AdminOrderController::class, 'getMaterialsListManagement'])->name('admin.orders.materials.management');
        Route::get('orders/materials/management-grouped', [AdminOrderController::class, 'getMaterialsListManagementGrouped'])->name('admin.orders.materials.management-grouped');

        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');

        // تجهيز وتقييد الطلبات
        Route::get('orders/{order}/process', [AdminOrderController::class, 'showProcess'])->name('admin.orders.process');
        Route::post('orders/{order}/process', [AdminOrderController::class, 'processOrder'])->name('admin.orders.process.submit');
        Route::post('orders/{order}/confirm', [AdminOrderController::class, 'confirm'])->name('admin.orders.confirm');
        Route::put('orders/{order}/review-status', [AdminOrderController::class, 'updateReviewStatus'])->name('admin.orders.review-status.update');

        // تعديل منتج من صفحة تجهيز الطلب
        Route::get('orders/products/{product}/edit-data', [AdminOrderController::class, 'getProductEditData'])->name('admin.orders.products.edit-data');
        Route::put('orders/products/{product}/update-sizes', [AdminOrderController::class, 'updateProductSizes'])->name('admin.orders.products.update-sizes');


        // تعديل الطلب المقيد
        Route::get('orders/{order}/edit', [AdminOrderController::class, 'edit'])->name('admin.orders.edit');
        Route::put('orders/{order}', [AdminOrderController::class, 'update'])->name('admin.orders.update');
        Route::post('orders/{order}/items/{item}/remove', [AdminOrderController::class, 'removeOrderItem'])->name('admin.orders.items.remove');

        // إدارة حالات الطلبات
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('admin.orders.cancel');

        // الاسترجاع المباشر (بدون نماذج)
        Route::post('orders/{order}/return-direct', [AdminOrderController::class, 'returnDirect'])->name('admin.orders.return.direct');

        // الإرجاع (كلي أو جزئي) - محذوف
        // Route::get('orders/{order}/return', [AdminOrderController::class, 'showReturn'])->name('admin.orders.return');
        // Route::post('orders/{order}/return', [AdminOrderController::class, 'processReturn'])->name('admin.orders.return.process');
        Route::get('orders/{order}/return-details', [AdminOrderController::class, 'returnDetails'])->name('admin.orders.return.details');

        // الإرجاع الجزئي الجديد (بدون تغيير حالة الطلب)
        Route::get('orders/{order}/partial-return', [AdminOrderController::class, 'showPartialReturn'])->name('admin.orders.partial-return');
        Route::post('orders/{order}/partial-return', [AdminOrderController::class, 'processPartialReturn'])->name('admin.orders.partial-return.process');

        // الاستبدال (كلي أو جزئي) - محذوف
        // Route::get('orders/{order}/exchange', [AdminOrderController::class, 'showExchange'])->name('admin.orders.exchange');
        // Route::post('orders/{order}/exchange', [AdminOrderController::class, 'processExchange'])->name('admin.orders.exchange.process');
        Route::get('orders/{order}/exchange-details', [AdminOrderController::class, 'exchangeDetails'])->name('admin.orders.exchange.details');

        // صفحات القوائم
        Route::get('orders-cancelled', [AdminOrderController::class, 'cancelled'])->name('admin.orders.cancelled');
        Route::get('orders-exchanged', [AdminOrderController::class, 'exchanged'])->name('admin.orders.exchanged');

        // حذف الطلبات
        Route::delete('orders/{order}', [AdminOrderController::class, 'destroy'])->name('admin.orders.destroy');
        Route::delete('orders/{order}/force', [AdminOrderController::class, 'forceDelete'])->name('admin.orders.forceDelete')->where('order', '[0-9]+');

        // كشف حركة الطلبات
        Route::get('order-movements', [AdminProductMovementController::class, 'orderMovements'])->name('admin.order-movements.index');
        Route::get('products/{warehouse}/product/{product}/movements', [AdminProductMovementController::class, 'show'])
            ->name('admin.products.movements');

        // كشف حركة المواد
        Route::get('product-movements', [AdminProductMovementController::class, 'productMovements'])->name('admin.product-movements.index');

        // صفحة اختبار الاسترجاع (للتطوير فقط)
        Route::get('test-restore', function() {
            return view('admin.orders.test-restore');
        })->name('admin.test-restore');

        // Transfer routes
        Route::get('transfers', [TransferController::class, 'index'])->name('admin.transfers.index');
        Route::get('transfers/search-products', [TransferController::class, 'searchProducts'])->name('admin.transfers.search');
        Route::post('transfers', [TransferController::class, 'transfer'])->name('admin.transfers.store');

        // Bulk Return routes
        Route::get('bulk-returns', [BulkReturnController::class, 'index'])->name('admin.bulk-returns.index');
        Route::get('bulk-returns/search', [BulkReturnController::class, 'searchProducts'])->name('admin.bulk-returns.search');
        Route::post('bulk-returns', [BulkReturnController::class, 'returnProducts'])->name('admin.bulk-returns.store');

        // Bulk Exchange Return routes
        Route::get('bulk-exchange-returns', [BulkExchangeReturnController::class, 'index'])->name('admin.bulk-exchange-returns.index');
        Route::get('bulk-exchange-returns/search', [BulkExchangeReturnController::class, 'searchProducts'])->name('admin.bulk-exchange-returns.search');
        Route::post('bulk-exchange-returns', [BulkExchangeReturnController::class, 'returnProducts'])->name('admin.bulk-exchange-returns.store');

        // Product Links routes
        Route::get('product-links/get-sizes', [ProductLinkController::class, 'getSizes'])->name('admin.product-links.get-sizes');
        Route::resource('product-links', ProductLinkController::class)->names([
            'index' => 'admin.product-links.index',
            'create' => 'admin.product-links.create',
            'store' => 'admin.product-links.store',
            'destroy' => 'admin.product-links.destroy',
        ])->except(['show', 'edit', 'update']);
    });
});

// Delegate Authentication Routes
Route::prefix('delegate')->group(function () {
    Route::get('/login', [DelegateLoginController::class, 'showLoginForm'])->middleware('guest')->name('delegate.login');
    Route::post('/login', [DelegateLoginController::class, 'login'])->middleware('guest');
    Route::post('/logout', [DelegateLoginController::class, 'logout'])->name('delegate.logout');

    // Protected delegate routes
    Route::middleware(['delegate', 'check.cart.expiration'])->group(function () {
        Route::get('/dashboard', [DelegateDashboardController::class, 'index'])->name('delegate.dashboard');

        // Settings routes
        Route::get('settings', [\App\Http\Controllers\Delegate\SettingController::class, 'index'])->name('delegate.settings.index');
        Route::post('settings/profile', [\App\Http\Controllers\Delegate\SettingController::class, 'updateProfile'])->name('delegate.settings.profile');

        // الصفحة الرئيسية - جميع المنتجات
        Route::get('/products', [DelegateProductController::class, 'allProducts'])->name('delegate.products.all');

        // Warehouse routes (view only)
        Route::get('warehouses', [DelegateWarehouseController::class, 'index'])->name('delegate.warehouses.index');

        // Product routes (view only)
        Route::get('warehouses/{warehouse}/products', [DelegateProductController::class, 'index'])->name('delegate.warehouses.products.index');
        Route::get('warehouses/{warehouse}/products/{product}', [DelegateProductController::class, 'show'])->name('delegate.warehouses.products.show');

        // Product API for modal
        Route::get('api/products/{product}', [DelegateProductController::class, 'getProductData'])->name('delegate.products.data');

        // Cart routes
        // عرض السلة الحالية (يجب أن يكون قبل resource route)
        Route::get('carts/view', [\App\Http\Controllers\Delegate\CartController::class, 'view'])->name('delegate.carts.view');
        Route::get('carts/{cart}/info', [\App\Http\Controllers\Delegate\CartController::class, 'info'])->name('delegate.carts.info');

        Route::resource('carts', CartController::class)->only(['index', 'show', 'store', 'destroy'])->names([
            'index' => 'delegate.carts.index',
            'show' => 'delegate.carts.show',
            'store' => 'delegate.carts.store',
            'destroy' => 'delegate.carts.destroy'
        ]);
        Route::post('carts/{cart}/extend', [CartController::class, 'extend'])->name('delegate.carts.extend');

        // Cart Items routes
        Route::post('carts/items', [CartItemController::class, 'store'])->name('delegate.carts.items.store');
        Route::put('cart-items/{cartItem}', [CartItemController::class, 'update'])->name('delegate.cart-items.update');
        Route::delete('cart-items/{cartItem}', [CartItemController::class, 'destroy'])->name('delegate.cart-items.destroy');

        // Order routes
        // نظام الطلبات الجديد (المبسط)
        Route::get('orders/start', [DelegateOrderController::class, 'start'])->name('delegate.orders.start');
        Route::post('orders/initialize', [DelegateOrderController::class, 'initialize'])->name('delegate.orders.initialize');
        Route::post('orders/submit', [DelegateOrderController::class, 'submit'])->name('delegate.orders.submit');
        Route::post('orders/cancel-current', [DelegateOrderController::class, 'cancel'])->name('delegate.orders.cancel-current');
        Route::post('orders/archive-current', [\App\Http\Controllers\Delegate\ArchivedOrderController::class, 'archiveCurrent'])->name('delegate.orders.archive-current');

        // نظام الطلبات القديم (عبر السلات)
        Route::get('carts/{cart}/checkout', [DelegateOrderController::class, 'create'])->name('delegate.orders.create');
        Route::post('orders', [DelegateOrderController::class, 'store'])->name('delegate.orders.store');
        Route::get('orders', [DelegateOrderController::class, 'index'])->name('delegate.orders.index');
        Route::get('orders/track', [DelegateOrderController::class, 'trackOrders'])->name('delegate.orders.track');
        Route::get('orders/{order}', [DelegateOrderController::class, 'show'])->name('delegate.orders.show');
        Route::get('orders/{order}/edit', [DelegateOrderController::class, 'edit'])->name('delegate.orders.edit');
        Route::put('orders/{order}', [DelegateOrderController::class, 'update'])->name('delegate.orders.update');
        Route::delete('orders/{order}/cancel', [DelegateOrderController::class, 'cancelOld'])->name('delegate.orders.cancel');

        // حذف واسترجاع الطلبات للمندوب
        Route::delete('orders/{order}', [DelegateOrderController::class, 'destroy'])->name('delegate.orders.destroy');
        Route::post('orders/{id}/force-delete', [DelegateOrderController::class, 'forceDelete'])->name('delegate.orders.forceDelete');
        Route::get('orders-deleted', [DelegateOrderController::class, 'deleted'])->name('delegate.orders.deleted');
        Route::post('orders/{order}/restore', [DelegateOrderController::class, 'restore'])->name('delegate.orders.restore');

        // Archived orders routes
        Route::get('archived', [\App\Http\Controllers\Delegate\ArchivedOrderController::class, 'index'])->name('delegate.archived.index');
        Route::post('archived/{archived}/restore', [\App\Http\Controllers\Delegate\ArchivedOrderController::class, 'restore'])->name('delegate.archived.restore');
        Route::delete('archived/{archived}', [\App\Http\Controllers\Delegate\ArchivedOrderController::class, 'destroy'])->name('delegate.archived.destroy');

        // Product Links routes
        Route::get('product-links/get-sizes', [DelegateProductLinkController::class, 'getSizes'])->name('delegate.product-links.get-sizes');
        Route::resource('product-links', DelegateProductLinkController::class)->names([
            'index' => 'delegate.product-links.index',
            'create' => 'delegate.product-links.create',
            'store' => 'delegate.product-links.store',
            'destroy' => 'delegate.product-links.destroy',
        ])->except(['show', 'edit', 'update']);
    });
});

// Chat routes
Route::get('/apps/chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
Route::get('/api/chat/conversations', [App\Http\Controllers\ChatController::class, 'getConversations'])->name('chat.conversations');
Route::post('/api/chat/conversation', [App\Http\Controllers\ChatController::class, 'getOrCreateConversation'])->name('chat.get-or-create-conversation');
Route::get('/api/chat/messages', [App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
Route::post('/api/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
Route::post('/api/chat/send-to-user', [App\Http\Controllers\ChatController::class, 'sendMessageToUser'])->name('chat.send-to-user');
Route::post('/api/chat/mark-read', [App\Http\Controllers\ChatController::class, 'markAsRead'])->name('chat.mark-read');
Route::get('/api/chat/search-order', [App\Http\Controllers\ChatController::class, 'searchOrder'])->name('chat.search-order');
Route::post('/api/chat/send-order', [App\Http\Controllers\ChatController::class, 'sendOrderMessage'])->name('chat.send-order');
Route::get('/api/chat/search-product', [App\Http\Controllers\ChatController::class, 'searchProduct'])->name('chat.search-product');
Route::post('/api/chat/send-product', [App\Http\Controllers\ChatController::class, 'sendProductMessage'])->name('chat.send-product');
Route::post('/api/chat/create-group', [App\Http\Controllers\ChatController::class, 'createGroup'])->name('chat.create-group');
Route::post('/api/chat/add-participants', [App\Http\Controllers\ChatController::class, 'addParticipantsToGroup'])->name('chat.add-participants');
Route::post('/api/chat/remove-participant', [App\Http\Controllers\ChatController::class, 'removeParticipantFromGroup'])->name('chat.remove-participant');
Route::get('/api/chat/group-participants/{id}', [App\Http\Controllers\ChatController::class, 'getGroupParticipants'])->name('chat.group-participants');

    // Notification API Routes
Route::middleware('auth')->group(function () {
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('api.notifications.unread-count');
    Route::get('/api/notifications', [App\Http\Controllers\NotificationController::class, 'getNotifications'])->name('api.notifications.index');
    Route::post('/api/notifications/{id}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('api.notifications.mark-read');
    Route::post('/api/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');

    // تم إزالة FCM و SSE routes - استخدام SweetAlert فقط

    // SweetAlert Routes
    Route::get('/api/sweet-alerts/unread', [App\Http\Controllers\SweetAlertController::class, 'getUnread'])->name('api.sweet-alerts.unread');
    Route::post('/api/sweet-alerts/{id}/read', [App\Http\Controllers\SweetAlertController::class, 'markAsRead'])->name('api.sweet-alerts.read');
    Route::get('/api/sweet-alerts/check-order/{orderId}', [App\Http\Controllers\SweetAlertController::class, 'checkOrder'])->name('api.sweet-alerts.check-order');
    Route::get('/api/sweet-alerts/check-conversation/{conversationId}', [App\Http\Controllers\SweetAlertController::class, 'checkConversation'])->name('api.sweet-alerts.check-conversation');

    // PWA Token Routes
    Route::post('/api/pwa/token', [App\Http\Controllers\PwaTokenController::class, 'generateToken'])->name('api.pwa.token');
    Route::delete('/api/pwa/token', [App\Http\Controllers\PwaTokenController::class, 'revokeToken'])->name('api.pwa.token.revoke');

    // Banner Routes
    Route::get('/api/banner/active', [App\Http\Controllers\BannerController::class, 'getActiveBanner'])->name('api.banner.active');
    Route::post('/api/banner/dismiss', [App\Http\Controllers\BannerController::class, 'dismissBanner'])->name('api.banner.dismiss');
    Route::get('/api/banner/dashboard', [App\Http\Controllers\BannerController::class, 'getDashboardBanner'])->name('api.banner.dashboard');
});
Route::view('/apps/mailbox', 'apps.mailbox');
Route::view('/apps/todolist', 'apps.todolist');
Route::view('/apps/notes', 'apps.notes');
Route::view('/apps/scrumboard', 'apps.scrumboard');
Route::view('/apps/contacts', 'apps.contacts');
Route::view('/apps/calendar', 'apps.calendar');

Route::view('/apps/invoice/list', 'apps.invoice.list');
Route::view('/apps/invoice/preview', 'apps.invoice.preview');
Route::view('/apps/invoice/add', 'apps.invoice.add');
Route::view('/apps/invoice/edit', 'apps.invoice.edit');

Route::view('/components/tabs', 'ui-components.tabs');
Route::view('/components/accordions', 'ui-components.accordions');
Route::view('/components/modals', 'ui-components.modals');
Route::view('/components/cards', 'ui-components.cards');
Route::view('/components/carousel', 'ui-components.carousel');
Route::view('/components/countdown', 'ui-components.countdown');
Route::view('/components/counter', 'ui-components.counter');
Route::view('/components/sweetalert', 'ui-components.sweetalert');
Route::view('/components/timeline', 'ui-components.timeline');
Route::view('/components/notifications', 'ui-components.notifications');
Route::view('/components/media-object', 'ui-components.media-object');
Route::view('/components/list-group', 'ui-components.list-group');
Route::view('/components/pricing-table', 'ui-components.pricing-table');
Route::view('/components/lightbox', 'ui-components.lightbox');

Route::view('/elements/alerts', 'elements.alerts');
Route::view('/elements/avatar', 'elements.avatar');
Route::view('/elements/badges', 'elements.badges');
Route::view('/elements/breadcrumbs', 'elements.breadcrumbs');
Route::view('/elements/buttons', 'elements.buttons');
Route::view('/elements/buttons-group', 'elements.buttons-group');
Route::view('/elements/color-library', 'elements.color-library');
Route::view('/elements/dropdown', 'elements.dropdown');
Route::view('/elements/infobox', 'elements.infobox');
Route::view('/elements/jumbotron', 'elements.jumbotron');
Route::view('/elements/loader', 'elements.loader');
Route::view('/elements/pagination', 'elements.pagination');
Route::view('/elements/popovers', 'elements.popovers');
Route::view('/elements/progress-bar', 'elements.progress-bar');
Route::view('/elements/search', 'elements.search');
Route::view('/elements/tooltips', 'elements.tooltips');
Route::view('/elements/treeview', 'elements.treeview');
Route::view('/elements/typography', 'elements.typography');

Route::view('/charts', 'charts');
Route::view('/widgets', 'widgets');
Route::view('/font-icons', 'font-icons');
Route::view('/dragndrop', 'dragndrop');

Route::view('/tables', 'tables');

Route::view('/datatables/advanced', 'datatables.advanced');
Route::view('/datatables/alt-pagination', 'datatables.alt-pagination');
Route::view('/datatables/basic', 'datatables.basic');
Route::view('/datatables/checkbox', 'datatables.checkbox');
Route::view('/datatables/clone-header', 'datatables.clone-header');
Route::view('/datatables/column-chooser', 'datatables.column-chooser');
Route::view('/datatables/export', 'datatables.export');
Route::view('/datatables/multi-column', 'datatables.multi-column');
Route::view('/datatables/multiple-tables', 'datatables.multiple-tables');
Route::view('/datatables/order-sorting', 'datatables.order-sorting');
Route::view('/datatables/range-search', 'datatables.range-search');
Route::view('/datatables/skin', 'datatables.skin');
Route::view('/datatables/sticky-header', 'datatables.sticky-header');

Route::view('/forms/basic', 'forms.basic');
Route::view('/forms/input-group', 'forms.input-group');
Route::view('/forms/layouts', 'forms.layouts');
Route::view('/forms/validation', 'forms.validation');
Route::view('/forms/input-mask', 'forms.input-mask');
Route::view('/forms/select2', 'forms.select2');
Route::view('/forms/touchspin', 'forms.touchspin');
Route::view('/forms/checkbox-radio', 'forms.checkbox-radio');
Route::view('/forms/switches', 'forms.switches');
Route::view('/forms/wizards', 'forms.wizards');
Route::view('/forms/file-upload', 'forms.file-upload');
Route::view('/forms/quill-editor', 'forms.quill-editor');
Route::view('/forms/markdown-editor', 'forms.markdown-editor');
Route::view('/forms/date-picker', 'forms.date-picker');
Route::view('/forms/clipboard', 'forms.clipboard');

Route::view('/users/profile', 'users.profile');
Route::view('/users/user-account-settings', 'users.user-account-settings');

Route::view('/pages/knowledge-base', 'pages.knowledge-base');
Route::view('/pages/contact-us-boxed', 'pages.contact-us-boxed');
Route::view('/pages/contact-us-cover', 'pages.contact-us-cover');
Route::view('/pages/faq', 'pages.faq');
Route::view('/pages/coming-soon-boxed', 'pages.coming-soon-boxed');
Route::view('/pages/coming-soon-cover', 'pages.coming-soon-cover');
Route::view('/pages/error404', 'pages.error404');
Route::view('/pages/error500', 'pages.error500');
Route::view('/pages/error503', 'pages.error503');
Route::view('/pages/maintenence', 'pages.maintenence');

Route::view('/auth/boxed-lockscreen', 'auth.boxed-lockscreen');
Route::view('/auth/boxed-signin', 'auth.boxed-signin');
Route::view('/auth/boxed-signup', 'auth.boxed-signup');
Route::view('/auth/boxed-password-reset', 'auth.boxed-password-reset');
Route::view('/auth/cover-login', 'auth.cover-login');
Route::view('/auth/cover-register', 'auth.cover-register');
Route::view('/auth/cover-lockscreen', 'auth.cover-lockscreen');
Route::view('/auth/cover-password-reset', 'auth.cover-password-reset');

// Route للتحقق من حالة تسجيل الدخول في PWA
Route::get('/api/check-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->check() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
        ] : null,
    ]);
})->middleware('web');

// Telegram webhook route
Route::post('/telegram/webhook', [App\Http\Controllers\TelegramController::class, 'webhook'])->name('telegram.webhook');

// Handle missing or empty Nunito font files - return 204 (No Content) to prevent timeout
Route::get('/assets/fonts/nunito/{filename}', function ($filename) {
    $filePath = public_path("assets/fonts/nunito/{$filename}");

    // If file doesn't exist or is empty, return 204 immediately
    if (!file_exists($filePath) || filesize($filePath) == 0) {
        return response('', 204)->header('Content-Type', 'font/woff2');
    }

    // If file exists and has content, serve it normally
    return response()->file($filePath);
})->where('filename', '.*');
