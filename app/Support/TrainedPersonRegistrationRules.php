<?php

namespace App\Support;

class TrainedPersonRegistrationRules
{
    /**
     * Previously trained persons prove prior training with a WRRB Certificate
     * education row and must select the course they already completed.
     */
    public static function trainingRules(): array
    {
        return [
            'prior_course_id' => ['required', 'integer', 'exists:courses,id'],
        ];
    }

    public static function attributeNames(): array
    {
        return [
            'prior_course_id' => __('course previously trained'),
        ];
    }
}
