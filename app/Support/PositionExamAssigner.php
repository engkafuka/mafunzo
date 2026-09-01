<?php

namespace App\Support;

use App\Models\TrainingApplication;

class PositionExamAssigner
{
    /**
     * Resolve pass flag and final assigned position from score + education.
     *
     * @return array{exam_passed: ?bool, assigned_position: ?string, note: ?string}
     */
    public static function resolve(TrainingApplication $application, ?float $score): array
    {
        if ($score === null) {
            return [
                'exam_passed' => null,
                'assigned_position' => null,
                'note' => null,
            ];
        }

        $passScore = (float) config('position_exam_rules.pass_score', 50);
        $examPassed = $score >= $passScore;
        $applied = $application->position;
        $rules = config('position_exam_rules.positions.'.$applied);

        // Positions without special rules keep the applied position.
        if (! is_array($rules)) {
            return [
                'exam_passed' => $examPassed,
                'assigned_position' => $applied,
                'note' => null,
            ];
        }

        $minScore = (float) ($rules['min_score'] ?? 0);
        $requiredEducation = self::normalizeRequiredEducation($rules);
        $fallbacks = $rules['fallback_positions']
            ?? config('position_exam_rules.fallback_positions', []);

        $hasRequiredEducation = self::hasRequiredEducation($application, $requiredEducation);
        $eligibleForApplied = $hasRequiredEducation && $score >= $minScore;

        if ($eligibleForApplied) {
            return [
                'exam_passed' => $examPassed,
                'assigned_position' => $applied,
                'note' => null,
            ];
        }

        $fallback = self::pickFallback($application, $fallbacks);

        return [
            'exam_passed' => $examPassed,
            'assigned_position' => $fallback ?? $applied,
            'note' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<array{level: string, program: ?string}>
     */
    public static function normalizeRequiredEducation(array $rules): array
    {
        if (! empty($rules['required_education']) && is_array($rules['required_education'])) {
            $normalized = [];
            foreach ($rules['required_education'] as $row) {
                if (! is_array($row) || blank($row['level'] ?? null)) {
                    continue;
                }
                $normalized[] = [
                    'level' => (string) $row['level'],
                    'program' => isset($row['program']) && $row['program'] !== ''
                        ? (string) $row['program']
                        : null,
                ];
            }

            return $normalized;
        }

        // Legacy: levels-only list
        $levels = $rules['required_education_levels'] ?? [];
        $normalized = [];
        foreach ($levels as $level) {
            $normalized[] = ['level' => (string) $level, 'program' => null];
        }

        return $normalized;
    }

    /**
     * @param  list<array{level: string, program: ?string}>|list<string>  $requiredEducation
     */
    public static function hasRequiredEducation(TrainingApplication $application, array $requiredEducation): bool
    {
        if ($requiredEducation === []) {
            return true;
        }

        // Allow calling with plain level strings (exam-results UI helpers).
        if (isset($requiredEducation[0]) && is_string($requiredEducation[0])) {
            $requiredEducation = array_map(
                fn (string $level) => ['level' => $level, 'program' => null],
                $requiredEducation,
            );
        }

        $application->loadMissing('user.educationBackgrounds');
        $backgrounds = $application->user?->educationBackgrounds ?? collect();

        foreach ($requiredEducation as $requirement) {
            $level = $requirement['level'] ?? null;
            $program = $requirement['program'] ?? null;

            if (! $level) {
                continue;
            }

            $match = $backgrounds->contains(function ($background) use ($level, $program) {
                if ($background->level !== $level) {
                    return false;
                }

                if ($program === null) {
                    return true;
                }

                return $background->program === $program;
            });

            if ($match) {
                return true;
            }
        }

        return false;
    }

    public static function meetsPositionEducation(TrainingApplication $application, string $positionKey): bool
    {
        $rules = config('position_exam_rules.positions.'.$positionKey);
        if (! is_array($rules)) {
            return true;
        }

        return self::hasRequiredEducation($application, self::normalizeRequiredEducation($rules));
    }

    /**
     * Whether stored exam score and education satisfy licensing gates for a final position.
     */
    public static function meetsLicensedPositionRequirements(TrainingApplication $application, string $positionKey): bool
    {
        $rules = config('position_exam_rules.positions.'.$positionKey);
        if (! is_array($rules)) {
            return true;
        }

        if ($application->exam_score === null) {
            return false;
        }

        $minScore = (float) ($rules['min_score'] ?? 0);

        return (float) $application->exam_score >= $minScore
            && self::meetsPositionEducation($application, $positionKey);
    }

    /**
     * Stable auto-pick among fallback positions (same application → same fallback).
     *
     * @param  list<string>  $fallbacks
     */
    public static function pickFallback(TrainingApplication $application, array $fallbacks): ?string
    {
        if ($fallbacks === []) {
            return null;
        }

        $index = ((int) $application->id) % count($fallbacks);

        return $fallbacks[$index];
    }

    public static function fallbackGroupLabel(): string
    {
        return __('Documentation / Weight Assistant / Store Keeper');
    }
}
