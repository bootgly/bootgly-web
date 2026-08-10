<?php

use Bootgly\API\Environment\Configs\Config;
use Bootgly\API\Environment\Configs\Config\Types;


return new Config(scope: 'mail')
   ->From->bind(key: 'MAIL_FROM', default: 'no-reply@auth.localhost')
   // ! Empty host = zero-setup file sink (storage/mails/*.eml)
   ->Host->bind(key: 'MAIL_HOST', default: '')
   ->Port->bind(key: 'MAIL_PORT', default: 587, cast: Types::Integer)
   ->Secure->bind(key: 'MAIL_SECURE', default: 'starttls')
   ->Username->bind(key: 'MAIL_USERNAME', default: '')
   ->Password->bind(key: 'MAIL_PASSWORD', default: '')
   // ! true = enqueue through WPI\Queues (needs `bootgly queue run mail`)
   ->Queue->bind(key: 'MAIL_QUEUE', default: false, cast: Types::Boolean);
