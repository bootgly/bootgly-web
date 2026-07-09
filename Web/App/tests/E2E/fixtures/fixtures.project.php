<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\API\Projects\Project;


return new Project(
   // # Project Metadata
   name: 'Web App E2E fixtures',
   description: 'Fixture project anchoring the Web platform E2E suite',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: false,

   // # Project Boot Function
   boot: static function (): void {
      // ? The E2E suite boots the Test-mode server itself — nothing to do here
   }
);
