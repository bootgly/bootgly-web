<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use function getenv;

use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\App;


return new Project(
   // # Project Metadata
   name: 'Site',
   description: 'Landing site — Web platform App shell demo (views, layout, statics)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      $App = new App(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });

      $App
         ->configure(
            port: getenv('PORT') ? (int) getenv('PORT') : 8088,
            workers: 2,
            // ! Stateless landing — no forms, so no BodyParser/CSRF needed
            middlewares: [
               new SecureHeaders,
               new RequestId
            ]
         )
         ->load(__DIR__ . '/router')
         ->start();
   }
);
