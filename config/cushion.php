<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Form Drafts
    |--------------------------------------------------------------------------
    |
    | How form drafts are persisted. Drafts live in a cache store, so
    | they inherit the application's cache driver: keep them ephemeral
    | with the default cache driver, or persist them in the database
    | by using Laravel's database cache driver — no extra setup here.
    | Abandoned drafts expire with the TTL below.
    |
    */

    'driver' => env('CUSHION_DRIVER', 'cache'),

    'ttl' => env('CUSHION_TTL', 60 * 60 * 24 * 30),

];
