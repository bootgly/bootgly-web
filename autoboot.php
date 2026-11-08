<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

// ?
if (defined('WEB_ROOT_BASE') === true) {
   return;
}

// !
define('WEB_ROOT_BASE', __DIR__);
define('WEB_ROOT_DIR', __DIR__ . DIRECTORY_SEPARATOR);
if (defined('WEB_WORKING_BASE') === false) {
   define('WEB_WORKING_BASE', WEB_ROOT_BASE);
   define('WEB_WORKING_DIR', WEB_ROOT_DIR);
}

define('WEB_VERSION', '1.1.1');

// ! Bootables ([0-9]) || (-[a-z]) || ([0-9]-[a-z])
// -- nothing --

// ! Classes ([A-Z])
// Web\* (platform entities) + global Web (bootable)
spl_autoload_register (function (string $class) {
   $paths = explode('\\', $class);
   $file = implode('/', $paths) . '.php';

   $included = @include(WEB_WORKING_DIR . $file);

   if ($included === false && WEB_ROOT_DIR !== WEB_WORKING_DIR) {
      @include(WEB_ROOT_DIR . $file);
   }
});

// ! Resources ([a-z])
// ...

// @ The `Web` root implements `Bootgly\API\Platform`, so Bootgly creates it
//   (the global `Web` constant) and boots it once its own autoloader exists:
//   `Bootgly::autoboot()` when this file runs first (the launchers), here when
//   Bootgly is already booted (Composer's `files` order)
if (defined('Bootgly') === true) {
   // ? A Bootgly older than 1.1 cannot mount it
   if (method_exists('Bootgly', 'mount') === false) {
      throw new LogicException('The Web platform requires Bootgly 1.1 or later.');
   }

   Bootgly::mount('Web');
}
