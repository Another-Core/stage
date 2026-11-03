
<?php
// Minimum rest time: 11 hours in seconds
define('REST_SECONDS', 11 * 60 * 60);

// Calculate hours between two dates
function hoursBetween(string $start, string $end): float
{
    return (strtotime($end) - strtotime($start)) / 3600;
}

// Get the week number in Amsterdam timezone
function weekOf(string $date): string
{
    $amsterdam = new DateTimeZone('Europe/Amsterdam');
    return (new DateTimeImmutable($date))->setTimezone($amsterdam)->format('o-W');
}

// Calculate total assigned hours in a week
function hoursInWeek(array $shifts, string $week): float
{
    $total = 0;

    foreach ($shifts as $shift) {
        // Only count shifts starting in this week
        if (weekOf($shift['start']) === $week) {
            $total += hoursBetween($shift['start'], $shift['end']);
        }
    }

    return $total;
}

// Calculate remaining hours after the new shift
function remainingHours(array $employee, array $newShift, array $assignedShifts): float
{
    $week = weekOf($newShift['start']);
    $used = hoursInWeek($assignedShifts, $week);
    $newHours = hoursBetween($newShift['start'], $newShift['end']);

    return round((float) $employee['contractHoursPerWeek'] - $used - $newHours, 2);
}

// Check all assignment rules and return violations
function checkRules(array $employee, array $newShift, array $assignedShifts): array
{
    $violations = [];
    $skills = json_decode($employee['skills'], true) ?: [];

    // 1. Check required skill
    if (!in_array($newShift['requiredSkill'], $skills, true)) {
        $violations[] = [
            'rule' => 'SKILL',
            'message' => 'Employee does not have the required skill.'
        ];
    }

    // 2. Check overlap and 3. Check rest time
    $start = strtotime($newShift['start']);
    $end = strtotime($newShift['end']);
    $overlapId = null;
    $restProblem = false;

    // Check all assigned shifts
    foreach ($assignedShifts as $shift) {
        $otherStart = strtotime($shift['start']);
        $otherEnd = strtotime($shift['end']);

        // Check if shifts overlap
        if ($start < $otherEnd && $end > $otherStart) {
            $overlapId = $shift['id'];

        // Check rest if the new shift is before
        } elseif ($end <= $otherStart && $otherStart - $end < REST_SECONDS) {
            $restProblem = true;

        // Check rest if the new shift is after
        } elseif ($start >= $otherEnd && $start - $otherEnd < REST_SECONDS) {
            $restProblem = true;
        }
    }

    // Add overlap error
    if ($overlapId !== null) {
        $violations[] = [
            'rule' => 'OVERLAP',
            'message' => 'Overlaps shift ' . $overlapId . '.'
        ];
    }

    // Add rest time error
    if ($restProblem) {
        $violations[] = [
            'rule' => 'REST',
            'message' => 'There must be at least 11 hours of rest between shifts.'
        ];
    }

    // 4. Check weekly contract hours
    $remaining = remainingHours($employee, $newShift, $assignedShifts);

    // Add error if contract hours are exceeded
    if ($remaining < 0) {
        $violations[] = [
            'rule' => 'CONTRACT_HOURS',
            'message' => 'This assignment exceeds the weekly contract hours.'
        ];
    }

    // Return all found errors
    return $violations;
}