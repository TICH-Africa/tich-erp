<?php

return [
    /*
    | When false, employee and HR WFH portals show an inactive placeholder and
    | submissions or HR actions are rejected.
    */
    'enabled' => (bool) env('TICH_WFH_ENABLED', false),
];
