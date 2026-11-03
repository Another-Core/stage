
<?php
require __DIR__ . '/rules.php';

// Count failed tests
$failed = 0;

// Show PASS or FAIL for each test
function test(string $name, bool $success): void
{
    global $failed;

    echo ($success ? 'PASS' : 'FAIL') . ': ' . $name . PHP_EOL;

    // Count failed tests
    if (!$success) {
        $failed++;
    }
}

// Check if a specific rule was violated
function hasViolation(array $violations, string $rule): bool
{
    foreach ($violations as $violation) {
        // Return true if the rule is found
        if ($violation['rule'] === $rule) {
            return true;
        }
    }

    return false;
}

// Test employees and shifts
$anna = ['contractHoursPerWeek' => 24, 'skills' => '["care", "medication"]'];
$daan = ['contractHoursPerWeek' => 8, 'skills' => '["care", "medication"]'];
$bram = ['contractHoursPerWeek' => 32, 'skills' => '["care"]'];

$s1 = ['id' => 's1', 'start' => '2026-11-02T07:00:00+01:00', 'end' => '2026-11-02T15:00:00+01:00', 'requiredSkill' => 'care'];
$s2 = ['id' => 's2', 'start' => '2026-11-02T14:00:00+01:00', 'end' => '2026-11-02T22:00:00+01:00', 'requiredSkill' => 'medication'];

// Test missing skill
test('SKILL: missing skill', hasViolation(checkRules($bram, $s2, []), 'SKILL'));

// Test overlapping shifts
test('OVERLAP: overlapping shifts', hasViolation(checkRules($anna, $s2, [$s1]), 'OVERLAP'));

// Test shifts that touch but do not overlap
$touching = ['id' => 'sTouch', 'start' => '2026-11-02T15:00:00+01:00', 'end' => '2026-11-02T23:00:00+01:00', 'requiredSkill' => 'care'];
$issues = checkRules($anna, $touching, [$s1]);

test('Touching is not OVERLAP', !hasViolation($issues, 'OVERLAP'));
test('Touching still violates REST', hasViolation($issues, 'REST'));

// Test exactly 11 hours of rest
$eleven = ['id' => 's11', 'start' => '2026-11-03T02:00:00+01:00', 'end' => '2026-11-03T10:00:00+01:00', 'requiredSkill' => 'care'];
test('Exactly 11h rest is allowed', !hasViolation(checkRules($anna, $eleven, [$s1]), 'REST'));

// Test 10 hours of rest
$ten = ['id' => 's10', 'start' => '2026-11-03T01:00:00+01:00', 'end' => '2026-11-03T09:00:00+01:00', 'requiredSkill' => 'care'];
test('10h rest is not allowed', hasViolation(checkRules($anna, $ten, [$s1]), 'REST'));

// Test weekly contract hours
test('Contract hours exceeded', hasViolation(checkRules($daan, $eleven, [$s1]), 'CONTRACT_HOURS'));

// Test a valid assignment
test('Valid assignment', checkRules($bram, $s1, []) === []);

// Test rest when the new shift is before an existing shift
$s4 = ['id' => 's4', 'start' => '2026-11-03T07:00:00+01:00', 'end' => '2026-11-03T15:00:00+01:00', 'requiredSkill' => 'care'];
test('REST before existing shift', hasViolation(checkRules($anna, $s2, [$s4]), 'REST'));

// Test multiple rule violations
$several = checkRules($daan, $s2, [$s1]);
test('Returns OVERLAP and CONTRACT_HOURS', hasViolation($several, 'OVERLAP') && hasViolation($several, 'CONTRACT_HOURS'));

// Test night shift duration
test('Night shift duration', hoursBetween('2026-11-02T23:00:00+01:00', '2026-11-03T07:00:00+01:00') === 8.0);

// Test daylight saving time change
test('DST shift duration uses real hours', hoursBetween('2026-10-25T01:30:00+02:00', '2026-10-25T03:30:00+01:00') === 3.0);

// Test overnight shift and week calculation
$weekEnd = ['id' => 'sun', 'start' => '2026-11-08T23:00:00+01:00', 'end' => '2026-11-09T07:00:00+01:00'];

test('Overnight shift counts in its start week', hoursInWeek([$weekEnd], '2026-45') === 8.0);
test('Overnight shift does not count in next week', hoursInWeek([$weekEnd], '2026-46') === 0.0);

// Exit with error code 1 if any test failed
exit($failed > 0 ? 1 : 0);