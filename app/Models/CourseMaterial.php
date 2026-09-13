<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    use Auditable;

    public const CATEGORY_TIMETABLE = 'timetable';

    public const CATEGORY_SLIDES = 'slides';

    public const CATEGORY_HANDOUT = 'handout';

    public const CATEGORY_OTHER = 'other';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_TIMETABLE,
        self::CATEGORY_SLIDES,
        self::CATEGORY_HANDOUT,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'course_id',
        'title',
        'category',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'sort_order',
        'uploaded_by',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_TIMETABLE => __('Timetable'),
            self::CATEGORY_SLIDES => __('Slides'),
            self::CATEGORY_HANDOUT => __('Handout'),
            self::CATEGORY_OTHER => __('Other'),
            default => $this->category,
        };
    }

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_TIMETABLE => __('Timetable'),
            self::CATEGORY_SLIDES => __('Slides'),
            self::CATEGORY_HANDOUT => __('Handout'),
            self::CATEGORY_OTHER => __('Other'),
        ];
    }

    public function isPdf(): bool
    {
        if ($this->mime_type === 'application/pdf') {
            return true;
        }

        return str_ends_with(strtolower($this->original_filename), '.pdf');
    }

    public function formattedFileSize(): ?string
    {
        if ($this->file_size === null) {
            return null;
        }

        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1).' MB';
        }

        if ($this->file_size >= 1024) {
            return number_format($this->file_size / 1024, 1).' KB';
        }

        return $this->file_size.' B';
    }
}
