<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewCompany extends Model
{
    protected $fillable = [
        'name',
        'registration_number',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class, 'company_id');
    }
}
