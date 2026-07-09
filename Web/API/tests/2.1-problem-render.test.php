<?php

namespace Web\API;

use function assert;
use function is_string;
use function json_decode;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Modules\HTTP;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;

return new Specification(
   description: 'It should render RFC 9457 problem details as application/problem+json',
   test: function () {
      // @ Title defaults from the HTTP status text
      $Problem = new Problem(422);

      yield assert(
         assertion: $Problem->title === HTTP::RESPONSE_STATUS[422]
            && $Problem->status === 422
            && $Problem->type === 'about:blank'
            && $Problem->detail === null
            && $Problem->instance === null,
         description: 'Problem defaults: status text title, about:blank type, null detail/instance'
      );

      // @ Render — status, content type, required members
      $Response = new Response;
      $Rendered = $Problem->render($Response);

      $body = $Rendered->Body->raw;
      $members = json_decode(is_string($body) ? $body : '', true);

      yield assert(
         assertion: $Rendered->code === 422
            && $Rendered->Header->get('Content-Type') === 'application/problem+json',
         description: 'Rendered response carries the problem status and media type'
      );
      yield assert(
         assertion: $members === [
            'type' => 'about:blank',
            'title' => HTTP::RESPONSE_STATUS[422],
            'status' => 422
         ],
         description: 'Null detail/instance members are omitted from the payload'
      );

      // @ Full member set + extensions (never overriding standard members)
      $Full = new Problem(
         status: 403,
         title: 'Out of credit',
         detail: 'Your current balance is 30, but that costs 50.',
         type: 'https://example.com/probs/out-of-credit',
         instance: '/account/12345/msgs/abc',
         extensions: [
            'balance' => 30,
            'status' => 'this must not override the standard member'
         ]
      );

      $Rendered = $Full->render(new Response);
      $body = $Rendered->Body->raw;
      $members = json_decode(is_string($body) ? $body : '', true);

      yield assert(
         assertion: $members['type'] === 'https://example.com/probs/out-of-credit'
            && $members['title'] === 'Out of credit'
            && $members['status'] === 403
            && $members['detail'] === 'Your current balance is 30, but that costs 50.'
            && $members['instance'] === '/account/12345/msgs/abc'
            && $members['balance'] === 30,
         description: 'Full member set is rendered; extensions never override standard members'
      );

      // @ Throwable interface — message and code mirror title and status
      yield assert(
         assertion: $Full->getMessage() === 'Out of credit' && $Full->getCode() === 403,
         description: 'Problem is an Exception carrying title as message and status as code'
      );
   }
);
