<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Table
    |--------------------------------------------------------------------------
    */
    'table' => env('VCARD_TABLE', 'users'),

    /*
    |--------------------------------------------------------------------------
    | Column Mapping
    |--------------------------------------------------------------------------
    | Key   = vCard field name (fixed)
    | Value = column name in DB  (null = field not used)
    |
    | Required : first_name, phone_mobile
    | Optional : last_name, middle_name, phone_work, phone_home, email
    */
    'columns' => [
        'first_name'   => 'first_name',
        'last_name'    => null,
        'middle_name'  => null,

        'phone_mobile' => 'phone',
        'phone_work'   => null,
        'phone_home'   => null,

        'email'        => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'filename'         => 'contacts',  // Default filename (without .vcf)
        'append_count'     => true,        // contacts_150.vcf
        'append_date'      => false,       // contacts_2025-01-01.vcf
        'skip_empty_phone' => true,        // Skip records with no phone number
        'normalize_phone'  => true,        // Strip spaces and symbols from phone numbers
        'charset_utf8'     => true,        // UTF-8 charset support for Arabic names
        'chunk_size'       => 500,         // Number of records read per chunk
    ],

];