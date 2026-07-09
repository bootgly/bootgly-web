<?php

namespace Web\API;

use Bootgly\ACI\Tests\Suite;

return new Suite(
   // * Config
   autoBoot: __DIR__,
   autoInstance: true,
   autoReport: true,
   autoSummarize: true,
   exitOnFailure: true,
   // * Data
   suiteName: __NAMESPACE__,
   tests: [
      '1.1-action-dispatch-fresh',
      '1.2-action-invalid',
      '2.1-problem-render',
      '2.2-problems-middleware',
      '3.1-resource-transform-collect',
      '3.2-resource-paginate',
      '4.1-routes-map-expansion',
      '4.2-routes-map-only-except',
   ]
);
