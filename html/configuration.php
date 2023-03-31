<?php

define('SBMAILER', array (
    'default' => getenv('DEFAULT_ADAPTER'),
    //'log_location' => '',
    'params'  => array (
        'postmark' => array (
            'api_key' => getenv('POSTMARK_API_KEY')
        ),
        'sendgrid' => array (
            'api_key' => getenv('SENDGRID_API_KEY')
        ),
        'mailersend' => array (
            'api_key' => getenv('MAILERSEND_API_KEY')
        ),
        'sendinblue' => array (
            'api_key' => getenv('SENDINBLUE_API_KEY')
        ),
        // 'phpmailer' => array (), // Using mail function
        'phpmailer' => array ( // Using SMTP function
            'smtp_server'   => getenv('MAIL_SMTP_SERVER'),
            'smtp_port'     => getenv('MAIL_SMTP_PORT'),
            'smtp_user'     => getenv('MAIL_SMTP_USER'),
            'smtp_password' => getenv('MAIL_SMTP_PASSWORD')
        ),
        'microsoft-graph' => array (
            'tenant_id' => getenv('MS_GRAPH_TENTANT_ID'),
            'client_id' => getenv('MS_GRAPH_CLIENT_ID'),
            'client_secret' => getenv('MS_GRAPH_CLIENT_SECRET'),
            'save_to_sent_items' => true,
        ),
    ),
    'log_level' => getenv('LOG_LEVEL'), // 0 - Off | 1 - Error only | 2 - Full
    'env' => getenv('ENV'), // 'prod' or 'test'
    'test_address' => getenv('TEST_ADDRESS'), // Required when env == 'test'
    'test_address_name' => getenv('TEST_ADDRESS_NAME'),
));