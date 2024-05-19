<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP Connection
    |--------------------------------------------------------------------------
    */

    'host' => env('LDAP_HOST', '127.0.0.1'),
    'username' => env('LDAP_USERNAME', 'cn=user,dc=local,dc=com'),
    'password' => env('LDAP_PASSWORD', 'secret'),
    'port' => env('LDAP_PORT', 389),
    'base_dn' => env('LDAP_BASE_DN', 'dc=local,dc=com'),
    'timeout' => env('LDAP_TIMEOUT', 5),
    'protocol_version' => env('LDAP_OPT_PROTOCOL_VERSION', 3),
    'mail_domain' => env('LDAP_MAIL_DOMAIN', null),
    
    'auto_create' => env('LDAP_AUTO_CREATE', false)
];
