<?php

namespace Web\App;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;

return new Specification(
   description: 'It should share view variables and apply conventions onto the View resource',
   test: function () {
      // ! Views
      $Views = new Views;

      // @ Platform default
      yield assert(
         assertion: $Views->layout === 'layouts/main',
         description: 'The default layout convention is views/layouts/main'
      );

      // @ share() merges (later wins)
      $Views
         ->share(['app' => 'Blog', 'year' => 2026])
         ->share(['year' => 2027]);

      yield assert(
         assertion: $Views->shared === ['app' => 'Blog', 'year' => 2027],
         description: 'share() merges shared variables with later values winning'
      );

      // @ apply() pushes layout + shared exports onto the View resource
      $Response = new Response;
      $Views->layout = 'layouts/site';
      $Views->apply($Response);

      yield assert(
         assertion: $Response->View->layout === 'layouts/site',
         description: 'apply() sets the View resource layout'
      );
   }
);
