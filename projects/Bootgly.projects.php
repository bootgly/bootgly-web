<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

/**
 * Unified project registry — the allow-list read by `Projects::read()`.
 * Only listed paths may be started. The entry flagged `'default' => true`
 * is the web SAPI default (position is readability only).
 */

return [
   'Auth' => ['interfaces' => ['WPI']],
   'Blog' => ['interfaces' => ['WPI'], 'default' => true],
   'Chat' => ['interfaces' => ['WPI']],
   'Site' => ['interfaces' => ['WPI']],
   'Tasks' => ['interfaces' => ['WPI']],
];
