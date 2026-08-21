<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee clock-in location
    |--------------------------------------------------------------------------
    |
    | Staff must share GPS coordinates when clocking in. On-campus clock-ins
    | must fall within the configured geofence radius.
    |
    */

    'require_location' => env('HR_ATTENDANCE_REQUIRE_LOCATION', true),

    'max_accuracy_meters' => (int) env('HR_ATTENDANCE_MAX_ACCURACY_M', 2000),

    'default_geofence' => [
        'name' => env('HR_ATTENDANCE_CAMPUS_NAME', 'Main Campus, Kisumu'),
        'latitude' => (float) env('HR_ATTENDANCE_CAMPUS_LAT', -0.091702),
        'longitude' => (float) env('HR_ATTENDANCE_CAMPUS_LNG', 34.767956),
        'radius_meters' => (int) env('HR_ATTENDANCE_CAMPUS_RADIUS_M', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | HR review of clock-ins
    |--------------------------------------------------------------------------
    |
    | When enabled, all staff clock-in records are marked as pending for
    | HR review. GPS location verification is skipped and HR must approve
    | each clock-in before it is considered verified.
    |
    */

    'require_hr_review' => env('HR_ATTENDANCE_REQUIRE_HR_REVIEW', true),

];
