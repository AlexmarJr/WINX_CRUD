<?php

return [
    'driver' => env('SEARCH_DRIVER', 'database'),
    'elasticsearch' => [
        'url' => env('ELASTICSEARCH_URL', 'http://elasticsearch:9200'),
        'api_key' => env('ELASTICSEARCH_API_KEY'),
        'index' => env('ELASTICSEARCH_PRODUCTS_INDEX', 'winx_products_v1'),
    ],
];
