<?php

namespace Web\App;

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
      '1.1-controller-helpers',
      '2.1-controllers-map-expansion',
      '2.2-controllers-map-only-except',
      '3.1-views-share-apply',
      '4.1-app-configure-stack',
   ]
);
