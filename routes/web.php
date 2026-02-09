<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ResellerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\CashRegisterController as AdminCashRegisterController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboardController;
use App\Http\Controllers\Cashier\CashRegisterController;
use App\Http\Controllers\Cashier\SaleController;
use App\Http\Controllers\Cashier\RepairController as CashierRepairController;
use App\Http\Controllers\Cashier\CustomerController;
use App\Http\Controllers\Cashier\ResellerPaymentController;
use App\Http\Controllers\Technician\DashboardController as TechnicianDashboardController;
use App\Http\Controllers\Technician\RepairController as TechnicianRepairController;
use App\Http\Controllers\SavController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - EGREGORE BUSINESS CRM
|--------------------------------------------------------------------------
*/

// Page d'accueil - Redirection vers login ou dashboard
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('caissiere')) {
            return redirect()->route('cashier.dashboard');
        } elseif ($user->hasRole('technicien')) {
            return redirect()->route('technician.dashboard');
        }
    }
    return redirect()->route('login');
})->name('home');

// ==================== AUTHENTICATION ====================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ==================== ROUTES PUBLIQUES (Accessibles par QR Code) ====================
Route::prefix('track')->name('track.')->group(function () {
    // Suivi de réparation (accessible par QR code)
    Route::get('/repair/{ticket}', [App\Http\Controllers\PublicTrackingController::class, 'repair'])
        ->name('repair');
    
    // Reçu de vente (accessible par QR code)
    Route::get('/sale/{invoice}', [App\Http\Controllers\PublicTrackingController::class, 'sale'])
        ->name('sale');
});

// Alias pour compatibilité
Route::get('/repair/track/{ticket}', [App\Http\Controllers\PublicTrackingController::class, 'repair'])
    ->name('repair.track');
Route::get('/sale/receipt/{invoice}', [App\Http\Controllers\PublicTrackingController::class, 'sale'])
    ->name('sale.receipt');

// ==================== PROFILE (Tous les utilisateurs connectés) ====================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ==================== NOTIFICATIONS (Tous les utilisateurs connectés) ====================
Route::prefix('notifications')
    ->name('notifications.')
    ->middleware('auth')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/latest', [NotificationController::class, 'latest'])->name('latest');
        Route::get('/check', [NotificationController::class, 'check'])->name('check');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/clear-read', [NotificationController::class, 'clearRead'])->name('clear-read');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });

// ==================== S.A.V ROUTES (Admin et Caissière) ====================
Route::prefix('sav')
    ->name('sav.')
    ->middleware(['auth', 'role:admin|caissiere'])
    ->group(function () {
        // Consultation (pas besoin de caisse ouverte)
        Route::get('/', [SavController::class, 'index'])->name('index');
        Route::get('/dashboard', [SavController::class, 'dashboard'])->name('dashboard');
        Route::get('/search-sale', [SavController::class, 'searchSale'])->name('search-sale');
        Route::get('/search-repair', [SavController::class, 'searchRepair'])->name('search-repair');
        
        // Opérations SAV (nécessitent une caisse ouverte pour la caissière)
        Route::middleware(['cash.open'])->group(function () {
            Route::get('/create', [SavController::class, 'create'])->name('create');
            Route::post('/', [SavController::class, 'store'])->name('store');
            Route::put('/{sav}/status', [SavController::class, 'updateStatus'])->name('update-status');
            Route::put('/{sav}/assign', [SavController::class, 'assign'])->name('assign');
            Route::post('/{sav}/comment', [SavController::class, 'addComment'])->name('add-comment');
            // Retour en stock
            Route::get('/{ticket}/stock-return', [SavController::class, 'stockReturnForm'])->name('stock-return');
            Route::post('/{ticket}/stock-return', [SavController::class, 'processStockReturn'])->name('process-stock-return');
            Route::delete('/{ticket}/cancel-stock-return', [SavController::class, 'cancelStockReturn'])->name('cancel-stock-return');
        });
        
        // Route show en dernier (wildcard)
        Route::get('/{sav}', [SavController::class, 'show'])->name('show');
    });

// ==================== ADMIN ROUTES ====================
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Gestion des boutiques (Multi-tenant)
        Route::get('/shops/dashboard', [ShopController::class, 'dashboard'])->name('shops.dashboard');
        Route::resource('shops', ShopController::class);
        Route::post('/shops/{shop}/assign-user', [ShopController::class, 'assignUser'])->name('shops.assign-user');
        Route::delete('/shops/{shop}/remove-user/{user}', [ShopController::class, 'removeUser'])->name('shops.remove-user');
        Route::post('/shops/{shop}/toggle-status', [ShopController::class, 'toggleStatus'])->name('shops.toggle-status');

        // Gestion des dépenses
        Route::get('/expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/dashboard', [\App\Http\Controllers\Admin\ExpenseController::class, 'dashboard'])->name('expenses.dashboard');
        Route::get('/expenses/categories', [\App\Http\Controllers\Admin\ExpenseController::class, 'categories'])->name('expenses.categories');
        Route::get('/expenses/export', [\App\Http\Controllers\Admin\ExpenseController::class, 'export'])->name('expenses.export');
        Route::get('/expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'show'])->name('expenses.show');
        Route::post('/expenses/{expense}/approve', [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('/expenses/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->name('expenses.reject');

        // Gestion des utilisateurs
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Gestion des catégories
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Gestion des produits
        Route::resource('products', ProductController::class);
        Route::get('/products/{product}/stock-entry', [ProductController::class, 'stockEntry'])->name('products.stock-entry');
        Route::post('/products/{product}/stock-entry', [ProductController::class, 'storeStockEntry'])->name('products.store-stock-entry');
        Route::get('/products-low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
        Route::post('/products/find-by-barcode', [ProductController::class, 'findByBarcode'])->name('products.find-by-barcode');

        // Gestion des revendeurs (paramétrage crédit)
        Route::resource('resellers', ResellerController::class);
        Route::put('/resellers/{reseller}/credit-limit', [ResellerController::class, 'updateCreditLimit'])->name('resellers.update-credit-limit');
        Route::get('/resellers/{reseller}/statement', [ResellerController::class, 'accountStatement'])->name('resellers.statement');
        Route::get('/resellers/{reseller}/export-statement', [ResellerController::class, 'exportAccountStatement'])->name('resellers.export-statement');
        
        // Fidélité revendeurs
        Route::get('/resellers-loyalty', [ResellerController::class, 'loyaltyReport'])->name('resellers.loyalty');
        Route::post('/resellers-loyalty/pay-bonus', [ResellerController::class, 'payBonus'])->name('resellers.pay-bonus');

        // Gestion des fournisseurs et commandes
        Route::resource('suppliers', \App\Http\Controllers\Admin\SupplierController::class);
        Route::get('/suppliers-low-stock', [\App\Http\Controllers\Admin\SupplierController::class, 'lowStockProducts'])->name('suppliers.low-stock');
        Route::get('/suppliers-price-comparison', [\App\Http\Controllers\Admin\SupplierController::class, 'priceComparison'])->name('suppliers.price-comparison');
        Route::post('/suppliers-generate-order', [\App\Http\Controllers\Admin\SupplierController::class, 'generateOrderPdf'])->name('suppliers.generate-order');
        Route::post('/suppliers-store-price', [\App\Http\Controllers\Admin\SupplierController::class, 'storePrice'])->name('suppliers.store-price');
        Route::get('/suppliers/{supplier}/prices', [\App\Http\Controllers\Admin\SupplierController::class, 'supplierPrices'])->name('suppliers.prices');
        Route::get('/products/{product}/supplier-prices', [\App\Http\Controllers\Admin\SupplierController::class, 'productPrices'])->name('products.supplier-prices');
        Route::get('/supplier-orders', [\App\Http\Controllers\Admin\SupplierController::class, 'orders'])->name('suppliers.orders');
        Route::get('/supplier-orders/{order}', [\App\Http\Controllers\Admin\SupplierController::class, 'showOrder'])->name('suppliers.orders.show');
        Route::post('/supplier-orders/{order}/mark-sent', [\App\Http\Controllers\Admin\SupplierController::class, 'markOrderSent'])->name('suppliers.orders.mark-sent');
        Route::post('/supplier-orders/{order}/mark-received', [\App\Http\Controllers\Admin\SupplierController::class, 'markOrderReceived'])->name('suppliers.orders.mark-received');

        // Paramètres système
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::delete('/settings/shop/{shopId}/reset', [SettingController::class, 'resetToGlobal'])->name('settings.reset-to-global');
        Route::post('/settings/upload-logo', [SettingController::class, 'uploadLogo'])->name('settings.upload-logo');
        Route::post('/settings/backup', [SettingController::class, 'backup'])->name('settings.backup');
        
        // Modes de paiement
        Route::post('/payment-methods', [SettingController::class, 'storePaymentMethod'])->name('payment-methods.store');
        Route::delete('/payment-methods/{paymentMethod}', [SettingController::class, 'destroyPaymentMethod'])->name('payment-methods.destroy');

        // Rapports et Analyses
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/repairs', [ReportController::class, 'repairs'])->name('reports.repairs');
        Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
        Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('/reports/sav', [ReportController::class, 'sav'])->name('reports.sav');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        // ==================== SÉCURITÉ ====================
        Route::prefix('security')->name('security.')->group(function () {
            Route::get('/', [SecurityController::class, 'index'])->name('index');
            Route::get('/alerts', [SecurityController::class, 'alerts'])->name('alerts');
            Route::post('/alerts/{alert}/resolve', [SecurityController::class, 'resolveAlert'])->name('resolve-alert');
            Route::get('/sessions', [SecurityController::class, 'sessions'])->name('sessions');
            Route::delete('/sessions/{session}', [SecurityController::class, 'terminateSession'])->name('terminate-session');
            Route::post('/users/{user}/terminate-sessions', [SecurityController::class, 'terminateUserSessions'])->name('terminate-user-sessions');
            Route::get('/login-history', [SecurityController::class, 'loginHistory'])->name('login-history');
            Route::post('/users/{user}/unlock', [SecurityController::class, 'unlockAccount'])->name('unlock-account');
            Route::post('/users/{user}/force-logout', [SecurityController::class, 'forceLogout'])->name('force-logout');
            Route::post('/users/{user}/force-password-change', [SecurityController::class, 'forcePasswordChange'])->name('force-password-change');
            Route::get('/export-alerts', [SecurityController::class, 'exportAlerts'])->name('export-alerts');
            Route::get('/export-login-history', [SecurityController::class, 'exportLoginHistory'])->name('export-login-history');
        });

        // ==================== GESTION DES CAISSES ====================
        Route::prefix('cash-registers')->name('cash-registers.')->group(function () {
            Route::get('/', [AdminCashRegisterController::class, 'index'])->name('index');
            Route::get('/export', [AdminCashRegisterController::class, 'export'])->name('export');
            Route::get('/{cashRegister}', [AdminCashRegisterController::class, 'show'])->name('show');
            Route::post('/{cashRegister}/reopen', [AdminCashRegisterController::class, 'reopen'])->name('reopen');
            Route::post('/{cashRegister}/force-close', [AdminCashRegisterController::class, 'forceClose'])->name('force-close');
            Route::delete('/{cashRegister}', [AdminCashRegisterController::class, 'destroy'])->name('destroy');
        });

        // ==================== MAINTENANCE SYSTÈME ====================
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            Route::post('/backup', [MaintenanceController::class, 'backup'])->name('backup');
            Route::post('/cleanup', [MaintenanceController::class, 'cleanup'])->name('cleanup');
            Route::get('/backup/{filename}/download', [MaintenanceController::class, 'downloadBackup'])->name('backup.download');
            Route::delete('/backup/{filename}', [MaintenanceController::class, 'deleteBackup'])->name('backup.delete');
        });
    });

// ==================== CASHIER ROUTES ====================
Route::prefix('caisse')
    ->name('cashier.')
    ->middleware(['auth', 'role:caissiere'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [CashierDashboardController::class, 'index'])->name('dashboard');

        // Gestion de la caisse (pas besoin d'avoir une caisse ouverte)
        Route::get('/caisse', [CashRegisterController::class, 'index'])->name('cash-register.index');
        Route::get('/caisse/ouvrir', [CashRegisterController::class, 'openForm'])->name('cash-register.open-form');
        Route::post('/caisse/ouvrir', [CashRegisterController::class, 'open'])->name('cash-register.open');
        Route::get('/caisse/fermer', [CashRegisterController::class, 'closeForm'])->name('cash-register.close-form');
        Route::post('/caisse/fermer', [CashRegisterController::class, 'close'])->name('cash-register.close');
        Route::get('/caisse/{cashRegister}', [CashRegisterController::class, 'show'])->name('cash-register.show');

        // Routes nécessitant une caisse ouverte
        Route::middleware(['cash.open'])->group(function () {
            
            // Opérations de caisse
            Route::post('/caisse/depense', [CashRegisterController::class, 'addExpense'])->name('cash-register.add-expense');
            Route::post('/caisse/entree', [CashRegisterController::class, 'cashIn'])->name('cash-register.cash-in');
            Route::post('/caisse/sortie', [CashRegisterController::class, 'cashOut'])->name('cash-register.cash-out');

            // Ventes
            Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
            Route::post('/sales/find-product', [SaleController::class, 'findProduct'])->name('sales.find-product');
            Route::get('/sales/{sale}/receipt', [SaleController::class, 'printReceipt'])->name('sales.receipt');
            Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

            // Réparations
            Route::resource('repairs', CashierRepairController::class)->except(['destroy']);
            Route::get('/repairs/{repair}/payment', [CashierRepairController::class, 'paymentForm'])->name('repairs.payment-form');
            Route::post('/repairs/{repair}/payment', [CashierRepairController::class, 'processPayment'])->name('repairs.process-payment');
            Route::post('/repairs/{repair}/pay', [CashierRepairController::class, 'pay'])->name('repairs.pay');
            Route::post('/repairs/{repair}/deliver', [CashierRepairController::class, 'deliver'])->name('repairs.deliver');
            Route::get('/repairs/{repair}/ticket', [CashierRepairController::class, 'printTicket'])->name('repairs.ticket');
            Route::get('/repairs/{repair}/receipt', [CashierRepairController::class, 'printReceipt'])->name('repairs.receipt');
            Route::get('/repairs/{repair}/delivery-note', [CashierRepairController::class, 'printDeliveryNote'])->name('repairs.delivery-note');

            // Créances revendeurs - Paiements
            Route::get('/creances/{reseller}/paiement', [ResellerPaymentController::class, 'createPayment'])->name('reseller-payments.create');
            Route::post('/creances/{reseller}/paiement', [ResellerPaymentController::class, 'storePayment'])->name('reseller-payments.store');
        });

        // Clients (consultation possible sans caisse ouverte)
        Route::resource('customers', CustomerController::class)->except(['destroy']);
        Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');

        // Créances revendeurs - Consultation (pas besoin de caisse ouverte)
        Route::get('/creances', [ResellerPaymentController::class, 'index'])->name('reseller-payments.index');
        Route::get('/creances/{reseller}', [ResellerPaymentController::class, 'show'])->name('reseller-payments.show');
        Route::get('/historique-paiements', [ResellerPaymentController::class, 'paymentHistory'])->name('reseller-payments.history');

        // Dépenses - Consultation (pas besoin de caisse ouverte)
        Route::get('/depenses', [App\Http\Controllers\Cashier\ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/depenses/categories', [App\Http\Controllers\Cashier\ExpenseController::class, 'categories'])->name('expenses.categories');
        Route::post('/depenses/categories', [App\Http\Controllers\Cashier\ExpenseController::class, 'storeCategory'])->name('expenses.categories.store');
        Route::put('/depenses/categories/{category}', [App\Http\Controllers\Cashier\ExpenseController::class, 'updateCategory'])->name('expenses.categories.update');
        Route::delete('/depenses/categories/{category}', [App\Http\Controllers\Cashier\ExpenseController::class, 'destroyCategory'])->name('expenses.categories.destroy');
        Route::get('/depenses/create', [App\Http\Controllers\Cashier\ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/depenses', [App\Http\Controllers\Cashier\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/depenses/{expense}', [App\Http\Controllers\Cashier\ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/depenses/{expense}/edit', [App\Http\Controllers\Cashier\ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/depenses/{expense}', [App\Http\Controllers\Cashier\ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/depenses/{expense}', [App\Http\Controllers\Cashier\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

// ==================== TECHNICIAN ROUTES ====================
Route::prefix('technicien')
    ->name('technician.')
    ->middleware(['auth', 'role:technicien'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [TechnicianDashboardController::class, 'index'])->name('dashboard');

        // Réparations
        Route::get('/repairs', [TechnicianRepairController::class, 'index'])->name('repairs.index');
        Route::get('/repairs/{repair}', [TechnicianRepairController::class, 'show'])->name('repairs.show');
        Route::put('/repairs/{repair}', [TechnicianRepairController::class, 'update'])->name('repairs.update');
        Route::post('/repairs/{repair}/take-over', [TechnicianRepairController::class, 'takeOver'])->name('repairs.take-over');
        Route::get('/repairs/{repair}/diagnosis', [TechnicianRepairController::class, 'diagnosisForm'])->name('repairs.diagnosis-form');
        Route::post('/repairs/{repair}/diagnosis', [TechnicianRepairController::class, 'storeDiagnosis'])->name('repairs.store-diagnosis');
        Route::put('/repairs/{repair}/status', [TechnicianRepairController::class, 'updateStatus'])->name('repairs.update-status');
        Route::post('/repairs/{repair}/parts', [TechnicianRepairController::class, 'addPart'])->name('repairs.add-part');
        Route::delete('/repairs/{repair}/parts/{part}', [TechnicianRepairController::class, 'removePart'])->name('repairs.remove-part');
        Route::post('/repairs/{repair}/mark-repaired', [TechnicianRepairController::class, 'markAsRepaired'])->name('repairs.mark-repaired');
    });
