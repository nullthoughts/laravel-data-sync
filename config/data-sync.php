<?php

return [
    /**
     * Namespace for Laravel Models
     */
    'namespace' => '\\App\\Models\\',

    /**
     * Path to directory containing JSON files for synchronization
     */
    'path'  => base_path('sync'),

    /**
     * Array of Model names which controls the synchronization order
     */
    'order' => [
        //
    ],
];
