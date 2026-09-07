<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PasswordLink extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
        'email',
        'type',
        'expires_at',
        'is_used',
        'used_at',
        'ip_address',
        'user_agent',
        'admin_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the password link.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Check if the link is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Check if the link is valid (not used and not expired).
     *
     * @return bool
     */
    public function isValid()
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Mark the link as used.
     *
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function markAsUsed($ipAddress = null, $userAgent = null)
    {
        $this->is_used = true;
        $this->used_at = Carbon::now();
        
        if ($ipAddress) {
            $this->ip_address = $ipAddress;
        }
        
        if ($userAgent) {
            $this->user_agent = $userAgent;
        }
        
        return $this->save();
    }

    /**
     * Invalidate all unused links for a specific user and type.
     *
     * @param int $userId
     * @param string $type
     * @return int
     */
    public static function invalidateUserLinks($userId, $type = 'password_reset')
    {
        return self::where('user_id', $userId)
            ->where('type', $type)
            ->where('is_used', false)
            ->update([
                'is_used' => true,
                'used_at' => Carbon::now()
            ]);
    }
    public static function invalidateAdminLinks($adminId, $type = 'password_reset')
    {
        return self::where('admin_id', $adminId)
            ->where('type', $type)
            ->where('is_used', false)
            ->update([
                'is_used' => true,
                'used_at' => Carbon::now()
            ]);
    }
    /**
     * Create a new password link for a user.
     *
     * @param int $userId
     * @param string $email
     * @param string $type
     * @param int $expiryHours
     * @return PasswordLink
     */
    public static function createLink($userId, $email, $type = 'password_reset', $expiryHours = 24)
    {
        // Invalidate all previous unused links for this user
        self::invalidateUserLinks($userId, $type);

        // Generate a unique token
        $token = bin2hex(random_bytes(32));

        // Create new link
        return self::create([
            'user_id' => $userId,
            'token' => $token,
            'email' => $email,
            'type' => $type,
            'expires_at' => Carbon::now()->addHours($expiryHours),
            'is_used' => false,
        ]);
    }
    public static function createAdminLink($adminId, $email, $type = 'password_reset', $expiryHours = 24){
        self::invalidateAdminLinks($adminId, $type);
        $token = bin2hex(random_bytes(32));
        return self::create([
            'admin_id' => $adminId,
            'token' => $token,
            'email' => $email,
            'type' => $type,
            'expires_at' => Carbon::now()->addHours($expiryHours),
            'is_used' => false,
        ]);
    }
    /**
     * Find a valid link by token.
     *
     * @param string $token
     * @param string $type
     * @return PasswordLink|null
     */
    public static function findValidLink($token, $type = 'password_reset')
    {
        $link = self::where('token', $token)
        ->where('type', $type)
        ->where('is_used', false)
        ->first();

        if ($link && !$link->isExpired()) {
            return $link;
        }

        return null;
    }

    /**
     * Clean up expired links.
     *
     * @param int $daysOld
     * @return int
     */
    public static function cleanupExpiredLinks($daysOld = 30)
    {
        return self::where('expires_at', '<', Carbon::now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Scope a query to only include valid links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope a query to only include expired links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope a query to only include used links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Scope a query to filter by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}

