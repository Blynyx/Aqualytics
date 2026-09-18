<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'pond_id',
        'name',
        'device_uid',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'api_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * HMAC-SHA256 of the plaintext token using APP_KEY.
     *
     * Chosen over Hash::make (bcrypt) so IoT checks stay fast and
     * deterministic. The plaintext token is never stored.
     */
    public static function hashToken(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, (string) config('app.key'));
    }

    public function issueToken(): string
    {
        $plainToken = bin2hex(random_bytes(32));

        $this->forceFill([
            'api_token_hash' => static::hashToken($plainToken),
        ])->save();

        return $plainToken;
    }

    public function tokenMatches(?string $plainToken): bool
    {
        if ($this->api_token_hash === null || $this->api_token_hash === '') {
            return false;
        }

        if ($plainToken === null || $plainToken === '') {
            return false;
        }

        return hash_equals($this->api_token_hash, static::hashToken($plainToken));
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
