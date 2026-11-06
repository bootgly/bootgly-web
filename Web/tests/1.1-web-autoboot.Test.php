<?php

// Global namespace: the `Web` bootable (class + constant) lives in the
// global namespace — a namespaced test could not reference it unqualified.


use Bootgly\ACI\Tests\Suite\Test;
use Bootgly\API\Platform;
use Web\API;


return new Test(
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
      yield assert(
         assertion: Web instanceof Platform,
         description: 'The Web bootable implements the Bootgly\API\Platform contract'
      );

      // @ Included after Bootgly (Composer's `files` order): the root is mounted all the same
      $Process = proc_open(
         [PHP_BINARY, '-r', 'require $argv[1]; require $argv[2]; echo json_encode(defined("Web") && Web instanceof Bootgly\API\Platform);', '--', BOOTGLY_ROOT_DIR . 'autoboot.php', WEB_ROOT_DIR . 'autoboot.php'],
         [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
         $Pipes
      );
      $late = (string) stream_get_contents($Pipes[1]);
      fclose($Pipes[1]);
      fclose($Pipes[2]);
      proc_close($Process);

      yield assert(
         assertion: $late === 'true',
         description: "The Web root is mounted when its autoboot.php runs after Bootgly's (got '{$late}')"
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
