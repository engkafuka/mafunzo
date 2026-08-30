<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewUserRole extends Model
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_PANELIST = 'panelist';

    public const ROLE_CHAIR = 'chair';

    public const ROLE_APPROVER = 'approver';

    public const ROLE_VIEWER = 'viewer';

    protected $fillable = ['user_id', 'role'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleLabel(): string
    {
        return config('interview.roles')[$this->role] ?? $this->role;
    }
}
