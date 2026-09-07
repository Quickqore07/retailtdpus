<?php

namespace App\Http\Controllers\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\PurchaseInvoice;
use App\Models\AP\PurchaseInvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicePaymentController extends Controller
{
    public function index($purchaseInvoiceId)
    {
        $this->authorize('access', 'purchase-invoice.show');

        $invoice = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($purchaseInvoiceId);

        $payments = $invoice->payments()
            ->with(['createdBy'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return to_json([
            'payments' => $payments,
            'paid_amount' => $invoice->paid_amount,
            'due_amount' => $invoice->due_amount,
            'total_amount' => (float) ($invoice->total_amount ?? $invoice->amount ?? 0),
        ]);
    }

    public function store($purchaseInvoiceId, Request $request)
    {
        $this->authorize('access', 'purchase-invoice.payment');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:ach,cash,check,other',
            'check_number' => 'required_if:payment_method,check|nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $invoice = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->withSum('payments', 'amount')
            ->findOrFail($purchaseInvoiceId);

        if ($invoice->status !== 'approved') {
            return to_json([
                'success' => false,
                'message' => 'Payments can only be added to approved purchase invoices.',
            ], 422);
        }

        $totalAmount = (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
        $paidAmount = round((float) ($invoice->payments_sum_amount ?? 0), 2);
        $dueAmount = round(max($totalAmount - $paidAmount, 0), 2);
        $paymentAmount = round((float) $validated['amount'], 2);

        if ($paymentAmount > $dueAmount) {
            return to_json([
                'success' => false,
                'message' => 'Payment amount cannot exceed the due amount of ' . number_format($dueAmount, 2) . '.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $payment = PurchaseInvoicePayment::create([
                'purchase_invoice_id' => $invoice->id,
                'amount' => $paymentAmount,
                'payment_method' => $validated['payment_method'],
                'check_number' => $validated['payment_method'] === 'check'
                    ? trim($validated['check_number'])
                    : null,
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'remarks' => filled($validated['remarks'] ?? null)
                    ? trim($validated['remarks'])
                    : null,
            ]);

            $newPaidAmount = round($paidAmount + $paymentAmount, 2);
            $newDueAmount = round(max($totalAmount - $newPaidAmount, 0), 2);

            if ($newDueAmount <= 0) {
                $invoice->update([
                    'status' => 'paid',
                    'check_number' => null,
                ]);
            }

            DB::commit();

            $invoice->refresh();
            $invoice->load(['payments.createdBy']);

            return to_json([
                'success' => true,
                'message' => $newDueAmount <= 0
                    ? 'Payment recorded and purchase invoice marked as paid.'
                    : 'Payment recorded successfully.',
                'payment' => $payment->load('createdBy'),
                'paid_amount' => $invoice->paid_amount,
                'due_amount' => $invoice->due_amount,
                'status' => $invoice->status,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return to_json([
                'success' => false,
                'message' => 'Failed to record payment.',
            ], 500);
        }
    }

    public function update($purchaseInvoiceId, $paymentId, Request $request)
    {
        $this->authorize('access', 'purchase-invoice.payment');

        $validated = $request->validate([
            'payment_date' => 'required|date',
        ]);

        $invoice = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($purchaseInvoiceId);

        if (!in_array($invoice->status, ['approved', 'paid'], true)) {
            return to_json([
                'success' => false,
                'message' => 'Payments can only be updated for approved or paid purchase invoices.',
            ], 422);
        }

        $payment = $invoice->payments()->where('id', $paymentId)->firstOrFail();

        DB::beginTransaction();

        try {
            $payment->update([
                'payment_date' => $validated['payment_date'],
            ]);

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Payment date updated successfully.',
                'payment' => $payment->fresh()->load('createdBy'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return to_json([
                'success' => false,
                'message' => 'Failed to update payment date.',
            ], 500);
        }
    }

    public function destroy($purchaseInvoiceId, $paymentId)
    {
        $this->authorize('access', 'purchase-invoice.payment');

        $invoice = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->withSum('payments', 'amount')
            ->findOrFail($purchaseInvoiceId);

        if (!in_array($invoice->status, ['approved', 'paid'], true)) {
            return to_json([
                'success' => false,
                'message' => 'Payments can only be deleted from approved or paid purchase invoices.',
            ], 422);
        }

        $payment = $invoice->payments()->where('id', $paymentId)->firstOrFail();

        DB::beginTransaction();

        try {
            $paymentAmount = round((float) $payment->amount, 2);
            $payment->delete();

            $totalAmount = (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
            $paidAmount = round((float) ($invoice->payments_sum_amount ?? 0) - $paymentAmount, 2);
            $dueAmount = round(max($totalAmount - $paidAmount, 0), 2);

            if ($invoice->status === 'paid' && $dueAmount > 0) {
                $invoice->update([
                    'status' => 'approved',
                ]);
            }

            DB::commit();

            $invoice->refresh();
            $invoice->load(['payments.createdBy']);

            return to_json([
                'success' => true,
                'message' => 'Payment deleted successfully.',
                'paid_amount' => $invoice->paid_amount,
                'due_amount' => $invoice->due_amount,
                'status' => $invoice->status,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return to_json([
                'success' => false,
                'message' => 'Failed to delete payment.',
            ], 500);
        }
    }
}
