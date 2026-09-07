<?php

namespace App\Services;

use App\Models\AP\PurchaseInvoice;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class PurchaseInvoiceNotificationService
{
    public static function notifyCreated(PurchaseInvoice $invoice): void
    {
        self::dispatch(
            $invoice,
            'purchase_invoice_created',
            'created',
            sprintf(
                'Purchase invoice %s was created by %s.',
                self::invoiceLabel($invoice),
                self::actorName()
            ),
            'Purchase invoice created — '.self::invoiceLabel($invoice)
        );
    }

    public static function notifyApproved(PurchaseInvoice $invoice): void
    {
        self::dispatch(
            $invoice,
            'purchase_invoice_approved',
            'approved',
            sprintf(
                'Purchase invoice %s was approved by %s.',
                self::invoiceLabel($invoice),
                self::actorName()
            ),
            'Purchase invoice approved — '.self::invoiceLabel($invoice)
        );
    }

    private static function dispatch(
        PurchaseInvoice $invoice,
        string $permissionKey,
        string $action,
        string $inAppContent,
        string $subject
    ): void {
        try {
            $invoice->loadMissing(
                'company:id,name,store_number,workgroup_id',
                'vendor:id,name',
                'expense:id,name',
                'createdBy:id,name'
            );

            $workgroupId = (int) ($invoice->workgroup_id ?? $invoice->company?->workgroup_id ?? 0);
            $companyId = (int) ($invoice->company_id ?? 0);

            if ($workgroupId <= 0 || $companyId <= 0) {
                return;
            }

            $recipientIds = NotificationPermission::userIdsForPermissionInCompanies(
                $permissionKey,
                [$companyId],
                $workgroupId
            );

            if ($recipientIds === []) {
                return;
            }

            store_app_notifications_for_users($recipientIds, $inAppContent, $permissionKey);

            $html = View::make('emails.purchase-invoice-notification', [
                'action' => $action,
                'invoice' => $invoice,
                'actorName' => self::actorName(),
                'reviewUrl' => url('/ap/purchase-invoices/'.$invoice->id),
            ])->render();

            $emails = User::query()
                ->whereIn('id', $recipientIds)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->pluck('email')
                ->unique()
                ->toArray();

            foreach ($emails as $email) {
                try {
                    MailService::sendMail($email, $subject, $html);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function invoiceLabel(PurchaseInvoice $invoice): string
    {
        if ($invoice->invoice_no) {
            return $invoice->invoice_no;
        }

        return '#'.$invoice->id;
    }

    private static function actorName(): string
    {
        $user = Auth::user();

        return trim((string) ($user?->name ?? '')) !== ''
            ? (string) $user->name
            : 'A user';
    }
}
