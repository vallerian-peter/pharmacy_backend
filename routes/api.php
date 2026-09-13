<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| Versioning: all routes are prefixed with /api/v1/
| Prefix is set in bootstrap/app.php (withRouting → apiPrefix: 'api/v1')
| OR via RouteServiceProvider if using older Laravel structure.
|
| Route groups:
|   Public   → no middleware (login)
|   Protected → auth:sanctum + branch-aware middleware
|
*/

// ═══════════════════════════════════════════════════════
// PUBLIC ROUTES — no authentication required
// ═══════════════════════════════════════════════════════
Route::prefix('auth')->name('auth.')->group(function () {

    // POST /api/v1/auth/login
    Route::post('login', [AuthController::class, 'login'])->name('login');

});

// ═══════════════════════════════════════════════════════
// PROTECTED ROUTES — requires Sanctum token
// ═══════════════════════════════════════════════════════
Route::middleware(['auth:sanctum'])->group(function () {

    // ── Auth ─────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {

        // POST /api/v1/auth/logout
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // GET  /api/v1/auth/me
        Route::get('me', [AuthController::class, 'me'])->name('me');

    });

    // ── Branches ─────────────────────────────────────
    // TODO: Route::apiResource('branches', BranchController::class);

    // ── Users ────────────────────────────────────────
    // TODO: Route::apiResource('users', UserController::class);

    // ── Products ─────────────────────────────────────
    // TODO: Route::apiResource('products', ProductController::class);

    // ── Categories ───────────────────────────────────
    // TODO: Route::apiResource('categories', CategoryController::class);

    // ── Batches (branch-scoped) ───────────────────────
    // TODO: Route::apiResource('batches', BatchController::class);

    // ── Suppliers ────────────────────────────────────
    // TODO: Route::apiResource('suppliers', SupplierController::class);

    // ── Customers ────────────────────────────────────
    // TODO: Route::apiResource('customers', CustomerController::class);

    // ── Purchases ────────────────────────────────────
    // TODO: Route::apiResource('purchases', PurchaseController::class);

    // ── Sales / POS ──────────────────────────────────
    // TODO: Route::apiResource('sales', SaleController::class);

    // ── Payments ─────────────────────────────────────
    // TODO: Route::apiResource('payments', PaymentController::class);

    // ── Inventory ────────────────────────────────────
    // TODO: Route::prefix('inventory')->name('inventory.')->group(function () {
    //   Route::get('/', [InventoryController::class, 'index']);
    //   Route::post('adjust', [InventoryController::class, 'adjust']);
    // });

    // ── Reports ──────────────────────────────────────
    // TODO: Route::prefix('reports')->name('reports.')->group(function () { ... });

    // ── Notifications ─────────────────────────────────
    // TODO: Route::apiResource('notifications', NotificationController::class)->only(['index', 'update']);

    // ── Sync ─────────────────────────────────────────
    // TODO: Route::prefix('sync')->name('sync.')->group(function () { ... });

});
