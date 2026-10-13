<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\Concerns\ResolvesPortalStaff;
use App\Models\PayrollItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    use ResolvesPortalStaff;

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        $payslips = PayrollItem::query()
            ->with('run')
            ->where('staff_id', $staff->id)
            ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'posted']))
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->orderByDesc('id')
            ->limit(36)
            ->get();

        return response()->json([
            'payslips' => $payslips->map(fn (PayrollItem $item) => [
                'id' => $item->id,
                'payslip_number' => $item->payslip_number,
                'year' => $item->pay_period_year,
                'month' => $item->pay_period_month,
                'period_label' => $item->run?->periodLabel()
                    ?? sprintf('%04d-%02d', $item->pay_period_year, $item->pay_period_month),
                'net_pay' => (float) ($item->net_pay ?? 0),
                'gross_pay' => (float) ($item->gross_pay ?? 0),
                'status' => $item->run?->status,
            ])->values(),
        ]);
    }

    public function show(Request $request, PayrollItem $payrollItem): JsonResponse
    {
        $staff = $this->portalStaff($request);
        abort_unless($payrollItem->staff_id === $staff->id, 403);
        abort_unless(in_array($payrollItem->run?->status, ['approved', 'posted'], true), 404);

        $breakdown = $payrollItem->breakdown();
        abort_if($breakdown === [], 404);

        return response()->json([
            'id' => $payrollItem->id,
            'payslip_number' => $payrollItem->payslip_number,
            'period_label' => $payrollItem->run?->periodLabel(),
            'breakdown' => $breakdown,
            'web_url' => url('/employee/finances/payslips/'.$payrollItem->id),
        ]);
    }
}
