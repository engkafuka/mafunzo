<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Course extends Model
{
    use Auditable;
    protected $fillable = [
        'name',
        'code',
        'session_year',
        'description',
        'application_opens_at',
        'application_deadline_at',
        'is_active',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'session_year' => 'integer',
            'application_opens_at' => 'date',
            'application_deadline_at' => 'date',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function trainingApplications()
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function scopePublishedForTrainees(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_published', true);
    }

    public function scopeAcceptingApplications(Builder $query): Builder
    {
        $today = now()->startOfDay();

        return $query->publishedForTrainees()
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('application_opens_at')
                    ->orWhereDate('application_opens_at', '<=', $today);
            })
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('application_deadline_at')
                    ->orWhereDate('application_deadline_at', '>=', $today);
            });
    }

    public function isAcceptingApplications(): bool
    {
        if (! $this->is_active || ! $this->is_published) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->application_opens_at && $this->application_opens_at->gt($today)) {
            return false;
        }

        if ($this->application_deadline_at && $this->application_deadline_at->lt($today)) {
            return false;
        }

        return true;
    }

    public function applicationWindowStatus(): string
    {
        if (! $this->is_published) {
            return 'unpublished';
        }

        $today = now()->startOfDay();

        if ($this->application_opens_at && $this->application_opens_at->gt($today)) {
            return 'upcoming';
        }

        if ($this->application_deadline_at && $this->application_deadline_at->lt($today)) {
            return 'closed';
        }

        return 'open';
    }

    public function formattedApplicationOpensAt(): ?string
    {
        return $this->application_opens_at?->format('d M Y');
    }

    public function formattedApplicationDeadlineAt(): ?string
    {
        return $this->application_deadline_at?->format('d M Y');
    }

    public function sessionLabel(): string
    {
        return (string) $this->session_year;
    }

    public function displayNameWithSession(): string
    {
        return $this->name.' ('.$this->sessionLabel().')';
    }

    public function canBePublished(): bool
    {
        return $this->session_year
            && $this->application_opens_at
            && $this->application_deadline_at
            && $this->application_deadline_at->gte($this->application_opens_at);
    }

    public function replicateForNewSession(int $sessionYear): self
    {
        $copy = $this->replicate([
            'is_published',
            'published_at',
        ]);

        $copy->session_year = $sessionYear;
        $copy->application_opens_at = null;
        $copy->application_deadline_at = null;
        $copy->is_published = false;
        $copy->published_at = null;
        $copy->save();

        return $copy;
    }
}
