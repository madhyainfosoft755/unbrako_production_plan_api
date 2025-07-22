<?php

if (!function_exists('get_current_week_info')) {
    // when start calculating first week as first date of the year
    // function get_current_week_info(): array
    // {
    //     $today = new DateTime();

    //     // Clone today to calculate Monday
    //     $monday = clone $today;
    //     $dayOfWeek = (int) $today->format('N'); // 1 (Mon) to 7 (Sun)
    //     $monday->modify('-' . ($dayOfWeek - 1) . ' days'); // Move to Monday

    //     // Clone to calculate Saturday
    //     $saturday = clone $monday;
    //     $saturday->modify('+5 days'); // Move to Saturday

    //     // Calculate ISO week number
    //     $weekNumber = (int) $today->format('W');

    //     return [
    //         'week_number' => $weekNumber,
    //         'start_date'  => $monday->format('Y-m-d'),
    //         'end_date'    => $saturday->format('Y-m-d'),
    //         'day_today'   => $today->format('l'), // e.g., Thursday
    //         'today'       => $today->format('Y-m-d'),
    //     ];
    // }


    // when start calculating first week of the year from first monday of the year
    function get_current_week_info(): array{
        $today = new DateTime();
        $year = (int) $today->format('Y');

        // Step 1: Find the first Monday of the year
        $firstDayOfYear = new DateTime("$year-01-01");
        $firstDayWeekday = (int) $firstDayOfYear->format('N'); // 1 (Mon) to 7 (Sun)

        if ($firstDayWeekday !== 1) {
            // Move forward to next Monday
            $daysToAdd = 8 - $firstDayWeekday;
            $firstMonday = clone $firstDayOfYear;
            $firstMonday->modify("+$daysToAdd days");
        } else {
            $firstMonday = clone $firstDayOfYear;
        }

        // Step 2: Calculate current week's Monday (based on today)
        $dayOfWeek = (int) $today->format('N'); // 1 (Mon) to 7 (Sun)
        $currentMonday = clone $today;
        $currentMonday->modify('-' . ($dayOfWeek - 1) . ' days');

        // Step 3: Calculate number of full weeks passed since first Monday
        $diffDays = (int)$firstMonday->diff($currentMonday)->format('%a');
        $weekNumber = floor($diffDays / 7) + 1;

        // Step 4: Calculate Saturday of the current week
        $currentSaturday = clone $currentMonday;
        $currentSaturday->modify('+5 days');

        return [
            'week_number' => $weekNumber,
            'start_date'  => $currentMonday->format('Y-m-d'),
            'end_date'    => $currentSaturday->format('Y-m-d'),
            'day_today'   => $today->format('l'),
            'today'       => $today->format('Y-m-d'),
        ];
    }
}
