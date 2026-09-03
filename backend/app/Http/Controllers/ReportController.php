<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function getDateRange(Request $request): array
    {
        $request->validate([
            'start' => 'nullable|date_format:Y-m-d',
            'end'   => 'nullable|date_format:Y-m-d',
        ]);

        $start = $request->query('start', Carbon::today()->toDateString());
        $end = $request->query('end', Carbon::today()->toDateString());

        $startCarbon = Carbon::parse($start)->startOfDay();
        $endCarbon = Carbon::parse($end)->endOfDay();

        if ($startCarbon->isAfter($endCarbon)) {
            abort(422, 'Start date must be before end date.');
        }

        if ($startCarbon->diffInDays($endCarbon) > 365) {
            abort(422, 'Date range cannot exceed 365 days.');
        }

        return [$startCarbon, $endCarbon];
    }

    public function sales(Request $request)
    {
        $branchId = $request->user()->branch_id;
        [$start, $end] = $this->getDateRange($request);

        $orders = Order::where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('voided_at')
            ->get();

        $gross = $orders->sum('subtotal');
        $discounts = $orders->sum('discount_amount');
        $net = $gross - $discounts;

        return response()->json([
            'summary' => [
                'orders' => $orders->count(),
                'gross_sales' => $gross,
                'discounts' => $discounts,
                'net_sales' => $net,
                'aov' => $orders->count() > 0 ? round($net / $orders->count(), 2) : 0,
            ]
        ]);
    }

    public function payments(Request $request)
    {
        $branchId = $request->user()->branch_id;
        [$start, $end] = $this->getDateRange($request);

        $payments = Payment::whereHas('order', fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'COMPLETED')
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get();

        return response()->json(['data' => $payments]);
    }

    public function staff(Request $request)
    {
        $branchId = $request->user()->branch_id;
        [$start, $end] = $this->getDateRange($request);

        $staffPerf = Order::where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('voided_at')
            ->with('waiter:id,name')
            ->select('waiter_id', DB::raw('COUNT(*) as orders'), DB::raw('SUM(subtotal) as gross'), DB::raw('SUM(discount_amount) as discounts'))
            ->groupBy('waiter_id')
            ->get();

        return response()->json(['data' => $staffPerf]);
    }
}
