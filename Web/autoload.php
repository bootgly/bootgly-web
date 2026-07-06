<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
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
