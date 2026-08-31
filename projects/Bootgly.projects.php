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
 * Only listed paths may be started (position is readability only).
 */

return [
   'Demo/Auth'  => ['interfaces' => ['WPI']],
   'Demo/Blog'  => ['interfaces' => ['WPI']],
   'Demo/Chat'  => ['interfaces' => ['WPI']],
   'Demo/Site'  => ['interfaces' => ['WPI']],
   'Demo/Tasks' => ['interfaces' => ['WPI']],
];
