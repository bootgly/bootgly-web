<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web;


use Web;


// ?
if ( ($this ?? null) && $this instanceof Web === false )
   return;

// ! Resources ([a-z])
require(__DIR__ . '/API/autoload.php');

// @
/**
 * @var API API
 */
const API = new API;

return [
   API
];
