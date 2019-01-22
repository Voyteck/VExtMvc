<?php

/**
 * Config file that sets BasicLazyFactory as default lazy factory for view helpers
 * 
 */

namespace Voyteck\VExtMvc;

return [
    'view_helpers' => [
        'abstract_factories' => [
            \Voyteck\VExtMvc\Factory\BasicLazyFactory::class,
        ],
    ],
];