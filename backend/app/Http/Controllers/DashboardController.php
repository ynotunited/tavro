<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * OWNER DASHBOARD — High level BI
     */
    public function owner(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $today = Carbon::today();

        // 1. Hero Metrics (Today)
        $ordersToday = Order::where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->whereNull('voided_at')
            ->get();

        $grossSales = $ordersToday->sum('subtotal');
        $discounts = $ordersToday->sum('discount_amount');
        $netSales = $grossSales - $discounts;
        $orderCount = $ordersToday->count();
        $aov = $orderCount > 0 ? round($netSales / $orderCount, 2) : 0;

        // 2. 7-Day Revenue Sparkline
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayNet = Order::where('branch_id', $branchId)
                ->whereDate('created_at', $date)
                ->whereNull('voided_at')
                ->sum(DB::raw('subtotal - discount_amount'));
            
            $sparkline[] = [
                'date' => $date->format('D'),
                'value' => (float) $dayNet
            ];
        }

        // 3. Payment Mix (Today)
        $paymentMix = Payment::whereHas('order', fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'COMPLETED')
            ->whereDate('created_at', $today)
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->map(fn($p) => ['name' => $p->method, 'value' => (float)$p->total]);

        // 4. Top 5 Products (Today)
        $topProducts = OrderItem::whereHas('order', fn($q) => $q->where('branch_id', $branchId)->whereDate('created_at', $today))
            ->whereNull('voided_at')
            ->select('product_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(subtotal) as revenue'))
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return response()->json([
            'hero' => [
                'gross_sales' => $grossSales,
                'net_sales' => $netSales,
                'orders' => $orderCount,
                'aov' => $aov,
            ],
            'sparkline' => $sparkline,
            'payment_mix' => $paymentMix,
            'top_products' => $topProducts,
        ]);
    }

    /**
     * MANAGER DASHBOARD — Live Operations
     */
    public function manager(Request $request)
    {
        $branchId = $request->user()->branch_id;

        // 1. Active Operations
        $activeOrders = Order::where('branch_id', $branchId)
            ->whereIn('status', ['OPEN', 'SENT'])
            ->count();

        $occupiedTables = Table::where('branch_id', $branchId)
            ->where('status', 'occupied')
            ->count();
        $totalTables = Table::where('branch_id', $branchId)->count();

        // 2. Staff on shift
        $activeShifts = Shift::where('branch_id', $branchId)
            ->whereIn('status', ['OPEN', 'CLOSING'])
            ->with('user:id,name')
            ->get();

        // 3. Variances Pending Approval
        $pendingVariances = Shift::where('branch_id', $branchId)
            ->where('status', 'CLOSING')
            ->with('user:id,name')
            ->get();

        // 4. Low Stock Alerts
        $lowStock = InventoryItem::where('branch_id', $branchId)
            ->where('track_inventory', true)
            ->whereRaw('current_stock <= min_level')
            ->select('id', 'name', 'current_stock', 'min_level', 'unit_of_measure')
            ->limit(10)
            ->get();

        return response()->json([
            'active_orders' => $activeOrders,
            'tables' => ['occupied' => $occupiedTables, 'total' => $totalTables],
            'active_shifts' => $activeShifts,
            'pending_variances' => $pendingVariances,
            'low_stock' => $lowStock,
        ]);
    }

    /**
     * BI ALERTS — Deterministic exception detection
     */
    public function alerts(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $today = Carbon::today();
        $alerts = [];

        // Rule 1: High Cash Variance Pending
        $highVarianceShifts = Shift::where('branch_id', $branchId)
            ->where('status', 'CLOSING')
            ->count();
        if ($highVarianceShifts > 0) {
            $alerts[] = [
                'severity' => 'high',
                'message' => "{$highVarianceShifts} shift(s) pending manager approval for high cash variance."
            ];
        }

        // Rule 2: Critically Low Stock
        $outOfStock = InventoryItem::where('branch_id', $branchId)
            ->where('track_inventory', true)
            ->where('current_stock', '<=', 0)
            ->count();
        if ($outOfStock > 0) {
            $alerts[] = [
                'severity' => 'high',
                'message' => "{$outOfStock} inventory items are out of stock."
            ];
        }

        // Rule 3: High Void Activity Today
        $voidValue = OrderItem::whereHas('order', fn($q) => $q->where('branch_id', $branchId)->whereDate('created_at', $today))
            ->whereNotNull('voided_at')
            ->sum('subtotal');
        
        $grossSales = Order::where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->sum('subtotal');

        if ($grossSales > 0 && ($voidValue / $grossSales) > 0.10) {
            $alerts[] = [
                'severity' => 'medium',
                'message' => "Unusual void activity: ₦" . number_format($voidValue, 2) . " voided today (>10% of gross sales)."
            ];
        }

        // Rule 4: Unpaid Orders from previous days
        $unpaidOldOrders = Order::where('branch_id', $branchId)
            ->where('status', '!=', 'PAID')
            ->whereDate('created_at', '<', $today)
            ->count();
        
        if ($unpaidOldOrders > 0) {
            $alerts[] = [
                'severity' => 'medium',
                'message' => "{$unpaidOldOrders} orders from previous days remain unpaid or unclosed."
            ];
        }

        return response()->json(['data' => $alerts]);
    }
}
