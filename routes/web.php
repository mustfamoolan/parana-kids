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
use App\Http\Controllers\Admin\ProductMovementController as AdminProductMovementController;
use App\Http\Controllers\Delegate\OrderController as DelegateOrderController;

// Redirect root to delegate login
Route::get('/', function () {
    return redirect()->route('delegate.login');
});

// Admin/Supplier Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

    // Protected admin routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

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

        // Product routes
        Route::resource('warehouses.products', AdminProductController::class)->names([
            'index' => 'admin.warehouses.products.index',
            'create' => 'admin.warehouses.products.create',
            'store' => 'admin.warehouses.products.store',
            'show' => 'admin.warehouses.products.show',
            'edit' => 'admin.warehouses.products.edit',
            'update' => 'admin.warehouses.products.update',
            'destroy' => 'admin.warehouses.products.destroy',
        ]);
        // Order routes

        // إدارة الطلبات (الصفحة الجديدة الموحدة)
        Route::get('orders-management', [AdminOrderController::class, 'management'])->name('admin.orders.management');

        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
        Route::get('orders/materials/list', [AdminOrderController::class, 'getMaterialsList'])->name('admin.orders.materials');
        Route::get('orders/materials/management', [AdminOrderController::class, 'getMaterialsListManagement'])->name('admin.orders.materials.management');

        // تجهيز وتقييد الطلبات
        Route::get('orders/{order}/process', [AdminOrderController::class, 'showProcess'])->name('admin.orders.process');
        Route::post('orders/{order}/process', [AdminOrderController::class, 'processOrder'])->name('admin.orders.process.submit');
        Route::post('orders/{order}/confirm', [AdminOrderController::class, 'confirm'])->name('admin.orders.confirm');


        // تعديل الطلب المقيد
        Route::get('orders/{order}/edit', [AdminOrderController::class, 'edit'])->name('admin.orders.edit');
        Route::put('orders/{order}', [AdminOrderController::class, 'update'])->name('admin.orders.update');

        // إدارة حالات الطلبات
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('admin.orders.cancel');

        // الاسترجاع المباشر (بدون نماذج)
        Route::post('orders/{order}/return-direct', [AdminOrderController::class, 'returnDirect'])->name('admin.orders.return.direct');

        // الإرجاع (كلي أو جزئي) - محذوف
        // Route::get('orders/{order}/return', [AdminOrderController::class, 'showReturn'])->name('admin.orders.return');
        // Route::post('orders/{order}/return', [AdminOrderController::class, 'processReturn'])->name('admin.orders.return.process');
        Route::get('orders/{order}/return-details', [AdminOrderController::class, 'returnDetails'])->name('admin.orders.return.details');

        // الاستبدال (كلي أو جزئي) - محذوف
        // Route::get('orders/{order}/exchange', [AdminOrderController::class, 'showExchange'])->name('admin.orders.exchange');
        // Route::post('orders/{order}/exchange', [AdminOrderController::class, 'processExchange'])->name('admin.orders.exchange.process');
        Route::get('orders/{order}/exchange-details', [AdminOrderController::class, 'exchangeDetails'])->name('admin.orders.exchange.details');

        // صفحات القوائم
        Route::get('orders-cancelled', [AdminOrderController::class, 'cancelled'])->name('admin.orders.cancelled');
        Route::get('orders-exchanged', [AdminOrderController::class, 'exchanged'])->name('admin.orders.exchanged');

        // حذف واسترجاع الطلبات
        Route::delete('orders/{order}', [AdminOrderController::class, 'destroy'])->name('admin.orders.destroy');
        Route::post('orders/{order}/restore', [AdminOrderController::class, 'restore'])->name('admin.orders.restore');
        Route::delete('orders/{order}/force', [AdminOrderController::class, 'forceDelete'])->name('admin.orders.forceDelete');
        Route::get('orders/{order}/check-restore', [AdminOrderController::class, 'checkRestoreAvailability'])->name('admin.orders.check-restore');

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
    });
});

// Delegate Authentication Routes
Route::prefix('delegate')->group(function () {
    Route::get('/login', [DelegateLoginController::class, 'showLoginForm'])->name('delegate.login');
    Route::post('/login', [DelegateLoginController::class, 'login']);
    Route::post('/logout', [DelegateLoginController::class, 'logout'])->name('delegate.logout');

    // Protected delegate routes
    Route::middleware(['delegate', 'check.cart.expiration'])->group(function () {
        Route::get('/dashboard', [DelegateDashboardController::class, 'index'])->name('delegate.dashboard');

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

        // عرض السلة الحالية
        Route::get('carts/view', [\App\Http\Controllers\Delegate\CartController::class, 'view'])->name('delegate.carts.view');

        // نظام الطلبات القديم (عبر السلات)
        Route::get('carts/{cart}/checkout', [DelegateOrderController::class, 'create'])->name('delegate.orders.create');
        Route::post('orders', [DelegateOrderController::class, 'store'])->name('delegate.orders.store');
        Route::get('orders', [DelegateOrderController::class, 'index'])->name('delegate.orders.index');
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
    });
});

Route::view('/apps/chat', 'apps.chat');
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
