<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PasswordLinkService
{
    /**
     * Generate a password reset link for a user.
     *
     * @param User $user
     * @param int $expiryHours
     * @return PasswordLink
     */
    public function generatePasswordLink(User $user, $type = 'password_reset', $expiryHours = 24)
    {
        return PasswordLink::createLink(
            $user->id,
            $user->email,
            $type,
            $expiryHours
        );
    }
    /**
     * Generate an email verification link for a user.
     *
     * @param User $user
     * @param int $expiryHours
     * @return PasswordLink
     */

    /**
     * Verify a password reset token.
     *
     * @param string $token
     * @return array
     */
    public function verifyPasswordLinkByToken($token, $type = 'password_reset')
    {
        $link = PasswordLink::findValidLink($token, $type);

        if (!$link) {
            return [
                'valid' => false,
                'message' => 'Invalid or expired token',
                'link' => null
            ];
        }

        return [
            'valid' => true,
            'message' => 'Token is valid',
            'link' => $link,
            'user' => $link->user,
            'admin' => $link->admin
        ];
    }

    /**
     * Use a password reset link.
     *
     * @param string $token
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array
     */
    public function usePasswordResetLink($token, $ipAddress = null, $userAgent = null)
    {
        $link = PasswordLink::findValidLink($token, 'password_reset');

        if (!$link) {
            return [
                'success' => false,
                'message' => 'Invalid or expired token',
                'user' => null
            ];
        }

        $link->markAsUsed($ipAddress, $userAgent);

        return [
            'success' => true,
            'message' => 'Link used successfully',
            'user' => $link->user
        ];
    }

    /**
     * Invalidate all password reset links for a user.
     *
     * @param int $userId
     * @return int
     */
    public function invalidateUserPasswordLinks($userId, $type = 'password_reset')
    {
        return PasswordLink::invalidateUserLinks($userId, $type);
    }

    /**
     * Get active password links for a user.
     *
     * @param int $userId
     * @param string|null $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserActiveLinks($userId, $type = null)
    {
        $query = PasswordLink::where('user_id', $userId)
            ->valid();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Get link statistics for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserLinkStats($userId)
    {
        $total = PasswordLink::where('user_id', $userId)->count();
        $used = PasswordLink::where('user_id', $userId)->where('is_used', true)->count();
        $expired = PasswordLink::where('user_id', $userId)
            ->where('is_used', false)
            ->where('expires_at', '<=', Carbon::now())
            ->count();
        $active = PasswordLink::where('user_id', $userId)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->count();

        return [
            'total' => $total,
            'used' => $used,
            'expired' => $expired,
            'active' => $active
        ];
    }

    /**
     * Clean up old expired links.
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanupOldLinks($daysOld = 30)
    {
        return PasswordLink::cleanupExpiredLinks($daysOld);
    }

    /**
     * Get password reset link URL.
     *
     * @param PasswordLink $link
     * @param string|null $baseUrl
     * @return string
     */
    public function getPasswordResetUrl(PasswordLink $link, $baseUrl = null)
    {
        $baseUrl = $baseUrl ?: config('app.url');
        return $baseUrl . '/setpassword?token=' . $link->token . '&email=' . urlencode($link->email);
    }

    /**
     * Check if user has any active password reset links.
     *
     * @param int $userId
     * @return bool
     */
    public function hasActivePasswordResetLink($userId)
    {
        return PasswordLink::where('user_id', $userId)
            ->where('type', 'password_reset')
            ->valid()
            ->exists();
    }

    /**
     * Get the most recent password reset link for a user.
     *
     * @param int $userId
     * @return PasswordLink|null
     */
    public function getMostRecentPasswordResetLink($userId)
    {
        return PasswordLink::where('user_id', $userId)
            ->where('type', 'password_reset')
            ->valid()
            ->orderBy('created_at', 'desc')
            ->first();
    }
}

