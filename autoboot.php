<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
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

define('WEB_VERSION', '0.1.0-alpha');

// ! Bootables ([0-9]) || (-[a-z]) || ([0-9]-[a-z])
// -- nothing --

// ! Classes ([A-Z])
// API (Application Programming Interface)
spl_autoload_register (function (string $class) {
   $paths = explode('\\', $class);
   $file = implode('/', $paths) . '.php';

   $included = @include(WEB_WORKING_DIR . $file);

   if ($included === false && WEB_ROOT_DIR !== WEB_WORKING_DIR) {
      @include(WEB_ROOT_DIR . $file);
   }
});
