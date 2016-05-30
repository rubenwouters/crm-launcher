<?php

return [
    /**
     * Returns Twitter credentials from config file
     */
    'twitter_credentials' => [
        'twitter_consumer_key' => getenv('TWITTER_CONSUMER_KEY'),
        'twitter_consumer_secret' => getenv('TWITTER_CONSUMER_SECRET'),
        'twitter_access_token' => getenv('TWITTER_ACCESS_TOKEN'),
        'twitter_access_token_secret' => getenv('TWITTER_ACCESS_TOKEN_SECRET'),
    ],

    /**
     * Returns Facebook credentials from config file
     */
    'facebook_credentials' => [
        'facebook_app_id' => getenv('FACEBOOK_APP_ID'),
        'facebook_app_secret' => getenv('FACEBOOK_APP_SECRET'),
        'facebook_page_id' => getenv('FACEBOOK_PAGE_ID'),
    ],

];
