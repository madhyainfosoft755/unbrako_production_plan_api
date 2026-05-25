<?php

if (!function_exists('generateRandomTimeWindow')) {

    function generateRandomTimeWindow($gapHours = 4)
    {
        date_default_timezone_set('Asia/Kolkata');

        // Maximum start hour allowed
        // so end does not exceed 24
        $maxStartHour = 24 - $gapHours;

        // Random start hour
        $startHour = rand(0, $maxStartHour - 1);

        $startTime = sprintf('%02d:00', $startHour);

        $endHour = $startHour + $gapHours;

        $endTime = sprintf('%02d:00', $endHour);

        return [
            'start' => $startTime,
            'end'   => $endTime
        ];
    }
}