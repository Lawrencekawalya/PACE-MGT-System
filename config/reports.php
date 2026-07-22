<?php

return [
    'queue_threshold' => (int) env('REPORT_EXPORT_QUEUE_THRESHOLD', 5000),
    'expiry_days' => (int) env('REPORT_EXPORT_EXPIRY_DAYS', 7),
];
