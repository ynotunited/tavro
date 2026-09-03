<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarController;
use App\Http\Controllers\BarInventoryController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuplicateUserController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\ModifierGroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpsMonitorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesNotificationController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\StockCountSessionController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\TrackLoginFailures;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ─── Public routes ────────────────────────────────────────────────────────────
Route::middleware('throttle:login')->post('/auth/login', [AuthController::class, 'login'])
    ->middleware(TrackLoginFailures::class);

Route::middleware('throttle:password.reset')->post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Self-service signup — rate-limited to prevent signup spam
Route::middleware('throttle:organizations')->post('/auth/register', [AuthController::class, 'register']);

// Staff invitations (public link delivered by email)
Route::middleware('throttle:login')->post('/auth/invite/accept', [AuthController::class, 'acceptInvite']);
Route::middleware('throttle:password.reset')->post('/auth/invite/resend', [AuthController::class, 'resendInvite']);

// Email verification (public callback link — signed + expiring)
Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

// Public resend for accounts that haven't been verified yet
Route::middleware('throttle:verification')->post('/auth/email/verification/resend', [AuthController::class, 'resendVerification']);

// Onboarding — rate-limited to prevent spam
Route::middleware('throttle:organizations')->post('/organizations', [OrganizationController::class, 'store']);

// ─── Realtime (Reverb) broadcast channel authorization ─────────────────────────
// The frontend (src/lib/echo.ts) authorizes private channels by POSTing here
// when opening a WebSocket. It carries the Sanctum bearer token; no HMAC body
// signing is required for this handshake.
Route::middleware(['auth:sanctum', 'ensure.user.active'])
    ->post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });

// ─── Authenticated routes ─────────────────────────────────────────────────────
// signature.verify enforces HMAC request signing on every mutating call.
Route::middleware(['auth:sanctum', 'throttle:api', 'ensure.user.active', 'signature.verify'])->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────────
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/sessions', [AuthController::class, 'sessions']);
    Route::delete('/auth/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
    Route::middleware('throttle:verification')->post('/auth/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->name('verification.send');

    // ── Organizations ─────────────────────────────────────────────────────────
    Route::get('/organizations/{id}', [OrganizationController::class, 'show']);
    Route::patch('/organizations/{id}', [OrganizationController::class, 'update']);

    // ── Branches ──────────────────────────────────────────────────────────────
    Route::apiResource('branches', BranchController::class)->except(['destroy']);

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::middleware('throttle:users.create')->group(function () {
        Route::apiResource('users', UserController::class)->except(['show']);
    });
    Route::patch('/users/{user}/role', [UserController::class, 'assignRole']);

    // ── PHASE 2: Menu & Catalog ───────────────────────────────────────────────

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/categories/reorder', [CategoryController::class, 'reorder']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::patch('/products/{product}/availability', [ProductController::class, 'toggleAvailability']);

    // Product Variants (nested under products)
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);

    // Modifier Groups
    Route::apiResource('modifier-groups', ModifierGroupController::class)->except(['show']);

    // Recipes (nested under products)
    Route::get('/products/{product}/recipe', [RecipeController::class, 'show']);
    Route::post('/products/{product}/recipe', [RecipeController::class, 'store']);

    // ── GLOBAL DRINK CATALOG (read-only reference + one-tap imports) ─────────
    Route::get('/catalog/search', [CatalogController::class, 'search']);
    Route::get('/catalog/categories', [CatalogController::class, 'categories']);
    Route::get('/catalog/packs', [CatalogController::class, 'packs']);
    Route::get('/catalog/packs/{packId}/products', [CatalogController::class, 'packProducts']);
    Route::post('/catalog/items/{catalogProductId}/add', [CatalogController::class, 'add']);
    Route::post('/catalog/packs/{packId}/apply', [CatalogController::class, 'apply']);

    // ── PHASE 3: Floors & Tables ──────────────────────────────────────────────
    Route::apiResource('floors', FloorController::class)->except(['show']);
    Route::apiResource('tables', TableController::class)->except(['show']);
    Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus']);

    // ── PHASE 4: Orders (POS) ────────────────────────────────────────────────
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Mutating order routes require an active subscription
    Route::middleware(CheckSubscription::class)->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/orders/{order}/items', [OrderController::class, 'addItem']);
        Route::patch('/orders/{order}/items/{item}', [OrderController::class, 'updateItem']);
        Route::post('/orders/{order}/items/{item}/void', [OrderController::class, 'voidItem']);
        Route::post('/orders/{order}/send', [OrderController::class, 'send']);
        Route::post('/orders/{order}/void', [OrderController::class, 'void']);
        Route::post('/orders/{order}/discount', [OrderController::class, 'applyDiscount']);
        Route::post('/orders/{order}/close', [OrderController::class, 'close']);
    });

    // ── PHASE 5: Kitchen Display System (KDS) ────────────────────────────────
    Route::get('/kitchen/tickets', [KitchenController::class, 'index']);
    Route::patch('/kitchen/items/{item}/status', [KitchenController::class, 'updateItemStatus']);
    Route::patch('/kitchen/orders/{order}/status', [KitchenController::class, 'updateOrderStatus']);

    // ── PHASE 6.1: Bar Display System (BDS) ──────────────────────────────────
    Route::get('/bar/tickets', [BarController::class, 'index']);
    Route::patch('/bar/items/{item}/status', [BarController::class, 'updateItemStatus']);
    Route::patch('/bar/items/{item}/serve-notes', [BarController::class, 'updateServeNotes']);
    Route::patch('/bar/orders/{order}/status', [BarController::class, 'updateOrderStatus']);

    // ── PHASE 6.2: Bar Inventory ─────────────────────────────────────────────
    Route::get('/bar/inventory/open-bottles', [BarInventoryController::class, 'getBarInventory']);
    Route::post('/bar/inventory/open-bottles', [BarInventoryController::class, 'openBottle']);

    // ── PHASE 7: Payments ────────────────────────────────────────────────────
    Route::get('/orders/{order}/payments', [PaymentController::class, 'index']);
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm']);
    Route::post('/payments/{payment}/refund', [RefundController::class, 'store']);

    // ── PHASE 8: Inventory ───────────────────────────────────────────────────
    Route::get('/inventory', [InventoryItemController::class, 'index']);
    Route::post('/inventory', [InventoryItemController::class, 'store']);
    Route::put('/inventory/{item}', [InventoryItemController::class, 'update']);
    Route::post('/inventory/receive', [InventoryItemController::class, 'receive']);
    Route::post('/inventory/adjust', [InventoryItemController::class, 'adjust']);
    Route::post('/inventory/wastage', [InventoryItemController::class, 'wastage']);

    Route::apiResource('suppliers', SupplierController::class)->except(['show']);

    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

    Route::get('/inventory/counts', [StockCountSessionController::class, 'index']);
    Route::post('/inventory/counts', [StockCountSessionController::class, 'store']);
    Route::patch('/inventory/counts/{session}/entries', [StockCountSessionController::class, 'updateEntries']);
    Route::post('/inventory/counts/{session}/submit', [StockCountSessionController::class, 'submit']);
    Route::post('/inventory/counts/{session}/approve', [StockCountSessionController::class, 'approve']);

    // ── PHASE 9: Shifts ──────────────────────────────────────────────────────
    Route::get('/shifts/active', [ShiftController::class, 'active']);
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::post('/shifts/{shift}/prepare-close', [ShiftController::class, 'prepareClose']);
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close']);
    Route::post('/shifts/{shift}/approve-variance', [ShiftController::class, 'approveVariance']);

    // ── PHASE 10: Dashboard & Reports ────────────────────────────────────────
    Route::middleware('throttle:heavy')->group(function () {
        Route::get('/dashboard/owner', [DashboardController::class, 'owner']);
        Route::get('/dashboard/manager', [DashboardController::class, 'manager']);
        Route::get('/dashboard/alerts', [DashboardController::class, 'alerts']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/payments', [ReportController::class, 'payments']);
        Route::get('/reports/staff', [ReportController::class, 'staff']);
    });

    // ── PHASE 12: Notifications ──────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // ── PHASE 12.1: Sales-notification channel (Telegram for the owner) ─────
    Route::get('/notification-channels', [SalesNotificationController::class, 'settings']);
    Route::patch('/notification-channels', [SalesNotificationController::class, 'updateSettings']);
    Route::post('/notification-channels/telegram/pair', [SalesNotificationController::class, 'generatePairCode']);
    Route::post('/notification-channels/telegram/disconnect', [SalesNotificationController::class, 'disconnect']);
    Route::post('/notification-channels/telegram/test', [SalesNotificationController::class, 'sendTest']);

    // ── PHASE 13: Audit & Security ───────────────────────────────────────────
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // ── PHASE 14: Subscription & Billing ─────────────────────────────────────
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
    Route::middleware('throttle:subscriptions')->group(function () {
        Route::post('/subscriptions/init', [SubscriptionController::class, 'init']);
        Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    });

    // ── PHASE 15: Issue Tracking ────────────────────────────────────────────
    Route::get('/issues', [IssueController::class, 'index']);
    Route::post('/issues', [IssueController::class, 'store']);
    Route::get('/issues/{issue}', [IssueController::class, 'show']);
    Route::patch('/issues/{issue}', [IssueController::class, 'update']);

    // ── PHASE 16: Analytics ─────────────────────────────────────────────────
    Route::middleware('throttle:heavy')->group(function () {
        Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/analytics/slow-requests', [AnalyticsController::class, 'slowRequests']);
        Route::get('/analytics/errors', [AnalyticsController::class, 'errors']);
    });

    // ── PHASE 17: Duplicate User Detection ──────────────────────────────────
    Route::get('/admin/duplicates', [DuplicateUserController::class, 'index']);
    Route::post('/admin/merge-users', [DuplicateUserController::class, 'merge']);

    // ── PHASE 18: API Key Management ────────────────────────────────────────
    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::get('/api-keys/{apiKey}', [ApiKeyController::class, 'show']);
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);
    Route::get('/api-keys/{apiKey}/usage', [ApiKeyController::class, 'usage']);

    // ── PHASE 19: Status Page / Incidents / Maintenance ────────────────────
    Route::prefix('status')->group(function () {
        // Provider config
        Route::get('/config', [StatusPageController::class, 'getConfig']);
        Route::post('/config', [StatusPageController::class, 'saveConfig']);
        Route::delete('/config', [StatusPageController::class, 'disconnectConfig']);

        // Incidents
        Route::get('/incidents', [StatusPageController::class, 'indexIncidents']);
        Route::post('/incidents', [StatusPageController::class, 'storeIncident']);
        Route::post('/incidents/{incident}/update', [StatusPageController::class, 'updateIncident']);
        Route::post('/incidents/{incident}/resolve', [StatusPageController::class, 'resolveIncident']);

        // Maintenance
        Route::get('/maintenance', [StatusPageController::class, 'indexMaintenance']);
        Route::post('/maintenance', [StatusPageController::class, 'storeMaintenance']);
        Route::post('/maintenance/{maintenanceWindow}/cancel', [StatusPageController::class, 'cancelMaintenance']);

        // Sync audit
        Route::get('/sync-logs', [StatusPageController::class, 'syncLogs']);
    });
});

// ── API Gateway (external API consumers — API key auth, NOT session auth) ──
// Mutating gateway calls also require an HMAC signature bound to the API key.
Route::middleware(['api.gateway', 'throttle:api', 'signature.verify'])->group(function () {
    Route::get('/gateway/orders', [OrderController::class, 'index']);
    Route::get('/gateway/orders/{order}', [OrderController::class, 'show']);
    Route::get('/gateway/products', [ProductController::class, 'index']);
    Route::get('/gateway/inventory', [InventoryItemController::class, 'index']);
});

// Webhooks (Outside auth middleware — rate limited separately.
// Protected by VerifyWebhook: URL-token + IP allowlist + provider signature.
// Not covered by the HMAC scheme — providers use their own secret handshake.)
Route::middleware('throttle:webhooks')->group(function () {
    Route::post('/webhooks/paystack/{token}', [WebhookController::class, 'paystack'])
        ->middleware('webhook.verify:paystack');
    Route::post('/webhooks/flutterwave/{token}', [WebhookController::class, 'flutterwave'])
        ->middleware('webhook.verify:flutterwave');
});

// ── INTERNAL OPS / MONITORING (dev company tooling) ─────────────────────────
// Protected by an internal shared token (security.ops_token), NOT usable as a
// tenant credential. Read-only except for explicitly resolving an issue.
Route::middleware(['ops.token', 'throttle:ops'])->prefix('ops')->group(function () {
    Route::get('/summary', [OpsMonitorController::class, 'summary']);
    Route::get('/errors', [OpsMonitorController::class, 'errorBreakdown']);
    Route::get('/issues', [OpsMonitorController::class, 'issues']);
    Route::post('/issues/{issue}/resolve', [OpsMonitorController::class, 'resolveIssue']);
});
