<?php

return [
    'prefix' => 'mailgroups',
    'middleware' => 'web',
    'stage_to' => env('MAIL_LIST_STAGE_TO', 'Create "MAIL_LIST_STAGE_TO" in .env with default mail'),
];
