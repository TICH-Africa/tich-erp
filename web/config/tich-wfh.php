<?php

return [
    /*
    | When false, employee and HR WFH portals show an inactive placeholder and
    | submissions or HR actions are rejected.
    */
    'enabled' => (bool) env('TICH_WFH_ENABLED', true),

    /*
    | ISO weekday numbers when WFH may be taken (1 = Monday, 7 = Sunday).
    */
    'allowed_iso_weekdays' => [2, 3, 4],

    /*
    | Calendar days between application day and the WFH date (e.g. 2 = apply Mon for Wed onward).
    */
    'min_advance_days' => (int) env('TICH_WFH_MIN_ADVANCE_DAYS', 2),
];
