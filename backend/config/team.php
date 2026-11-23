<?php

declare(strict_types=1);

return [
    /*
    | Invite link lifetime in days. Not fixed by the product owner yet (decision 0001 P3);
    | 7 days until they answer. Do not change without a written decision.
    */
    'invite_ttl_days' => (int) env('TEAM_INVITE_TTL_DAYS', 7),
];
