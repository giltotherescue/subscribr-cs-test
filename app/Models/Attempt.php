<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    /** @use HasFactory<\Database\Factories\AttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'assessment_version',
        'candidate_name',
        'candidate_email',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'last_activity_at',
        'reviewed_at',
        'admin_notes',
        'active_session_id',
        'active_session_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'active_session_updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}
