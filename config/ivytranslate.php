<?php

return [
    // This is the lang file that holds your original content
    'source_locale' => config('app.locale', env('APP_LOCALE')),

    // These are the langs enabled for this env, null will enable all
    'target_locales' => env('APP_TARGET_LOCALES'),

    // Pseudo is helpful for local development and will never be enabled in
    // Production, set to null to disable
    'pseudo_locale' => env('APP_PSEUDO_LOCALE', 'en_XA'),
];
