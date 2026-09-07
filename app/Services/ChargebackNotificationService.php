<?php

namespace App\Services;

use App\Models\ChargeBack\Chargeback;
use App\Models\ChargeBack\ChargebackReasonCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ChargebackNotificationService
{
    public static function notifyCreated(Chargeback $chargeback): void
    {
        self::dispatch(
            $chargeback,
            'chargeback_submitted',
            'created',
            sprintf(
                'Chargeback %s was created by %s.',
                self::chargebackLabel($chargeback),
                self::actorName()
            ),
            'Chargeback created — '.self::chargebackLabel($chargeback)
        );
    }

    public static function notifySubmitted(Chargeback $chargeback): void
    {
        self::dispatch(
            $chargeback,
            'chargeback_submitted',
            'submitted',
            sprintf(
                'Chargeback %s was submitted to the processor by %s.',
                self::chargebackLabel($chargeback),
                self::actorName()
            ),
            'Chargeback submitted to processor — '.self::chargebackLabel($chargeback)
        );
    }

    public static function notifyUpdated(Chargeback $chargeback): void
    {
        self::dispatch(
            $chargeback,
            'chargeback_submitted',
            'updated',
            sprintf(
                'Chargeback %s was updated by %s.',
                self::chargebackLabel($chargeback),
                self::actorName()
            ),
            'Chargeback updated — '.self::chargebackLabel($chargeback)
        );
    }

    public static function notifySalesReceiptUploaded(Chargeback $chargeback, bool $isReupload = false): void
    {
        $action = $isReupload ? 'sales_receipt_reuploaded' : 'sales_receipt_uploaded';
        $verb = $isReupload ? 'reuploaded' : 'uploaded';

        self::dispatch(
            $chargeback,
            'sales_receipt_uploaded',
            $action,
            sprintf(
                'Sales receipt %s for chargeback %s by %s.',
                $verb,
                self::chargebackLabel($chargeback),
                self::actorName()
            ),
            sprintf(
                'Sales receipt %s — %s',
                $isReupload ? 'reuploaded' : 'uploaded',
                self::chargebackLabel($chargeback)
            )
        );
    }

    private static function dispatch(
        Chargeback $chargeback,
        string $permissionKey,
        string $action,
        string $inAppContent,
        string $subject
    ): void {
        try {
            info('dispatching notification for chargeback: '.$chargeback->id);
            $chargeback->loadMissing('company:id,name,store_number,workgroup_id', 'createdBy:id,name');

            $workgroupId = (int) ($chargeback->company?->workgroup_id ?? 0);
            $companyId = (int) ($chargeback->company_id ?? 0);

            info('workgroupId: '.$workgroupId);
            info('companyId: '.$companyId);
            if ($workgroupId <= 0 || $companyId <= 0) {
                return;
            }

            $recipientIds = NotificationPermission::userIdsForPermissionInCompanies(
                $permissionKey,
                [$companyId],
                $workgroupId
            );

            info('recipientIds: '.json_encode($recipientIds));

            if ($recipientIds === []) {
                return;
            }

            store_app_notifications_for_users($recipientIds, $inAppContent, $permissionKey);

            $html = View::make('emails.chargeback-notification', [
                'action' => $action,
                'chargeback' => $chargeback,
                'actorName' => self::actorName(),
                'reviewUrl' => url('/charge-back'),
                'reasonLabel' => ChargebackReasonCode::labelsMap()[$chargeback->reason_code] ?? $chargeback->reason_code,
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

    private static function chargebackLabel(Chargeback $chargeback): string
    {
        if ($chargeback->case_number) {
            return $chargeback->case_number;
        }

        if ($chargeback->reference_number) {
            return $chargeback->reference_number;
        }

        return '#'.$chargeback->id;
    }

    private static function actorName(): string
    {
        $user = Auth::user();

        return trim((string) ($user?->name ?? '')) !== ''
            ? (string) $user->name
            : 'A user';
    }
}
