<?php

namespace App\Support;

use App\Models\CourseMaterial;
use App\Models\TrainingApplication;
use Illuminate\Database\Eloquent\Builder;

class CourseMaterialAccess
{
    public static function eligibleApplicationsQuery(int $courseId): Builder
    {
        return TrainingApplication::query()
            ->with('user')
            ->where('course_id', $courseId)
            ->where('status', 'payment_completed')
            ->where('application_review_status', '!=', 'rejected')
            ->whereNotNull('user_id');
    }

    public static function traineeCanAccess(CourseMaterial $material, int $userId): bool
    {
        if (! $material->isPublished()) {
            return false;
        }

        return self::eligibleApplicationsQuery($material->course_id)
            ->where('user_id', $userId)
            ->exists();
    }
}
