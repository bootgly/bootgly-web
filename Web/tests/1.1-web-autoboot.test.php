<?php

// Global namespace: the `Web` bootable (class + constant) lives in the
// global namespace — a namespaced test could not reference it unqualified.

use function assert;
use function class_exists;
use function defined;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Web\API;


return new Specification(
   description: 'It should autoboot the Web platform (constants + bootable + autoloader)',
   test: function () {
      // @ Constants
      yield assert(
         assertion: defined('WEB_ROOT_BASE') === true,
         description: 'WEB_ROOT_BASE is defined'
      );
      yield assert(
         assertion: defined('WEB_VERSION') === true,
         description: 'WEB_VERSION is defined: ' . WEB_VERSION
      );

      // @ Bootable singleton
      yield assert(
         assertion: Web instanceof Web,
         description: 'The Web constant holds the Web bootable'
      );

      // @ Double boot guarded
      $guarded = false;
      try {
         Web->autoboot();
      }
      catch (Exception) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'Rebooting the Web platform throws'
      );

      // @ Autoloader resolves platform entities
      yield assert(
         assertion: class_exists(API::class) === true,
         description: 'The autoloader resolves Web\API'
      );
   }
);
