<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use function file_get_contents;
use function getenv;
use function preg_replace;
use function spl_object_id;
use function str_starts_with;
use function substr;

use const Bootgly\CLI;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Bootgly\WPI\Nodes\WS_Server_CLI;
use Bootgly\WPI\Nodes\WS_Server_CLI\Events;


return new Project(
   // # Project Metadata
   name: 'Chat',
   description: 'Chat — Web platform realtime demo (WS Channels rooms; open statics/chat.html)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      $WS_Server_CLI = new WS_Server_CLI(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });

      $WS_Server_CLI->configure(
         host: '0.0.0.0',
         port: getenv('PORT') ? (int) getenv('PORT') : 8085,
         // ! Single worker — Channels are per-worker state
         workers: 1,
         heartbeatInterval: 30,
         // ! Plain HTTP requests on the same port get the client page —
         //   open http://localhost:8085 in the browser, no second server
         fallback: static function (string $target): null|array {
            return match ($target) {
               '/', '/index.html', '/chat.html' => [
                  'text/html; charset=UTF-8',
                  (string) file_get_contents(__DIR__ . '/statics/chat.html')
               ],
               '/favicon.png' => [
                  'image/png',
                  (string) file_get_contents(__DIR__ . '/statics/favicon.png')
               ],
               default => null
            };
         }
      );

      // ! Per-session room map (worker-local)
      $rooms = [];

      $WS_Server_CLI
         // # Every connection joins the lobby
         ->on(Events::Connected, function ($Session) use (&$rooms) {
            $rooms[spl_object_id($Session)] = 'lobby';

            $Session->join('lobby');
            $Session->broadcast('lobby', 'sys: a peer joined the room');
            $Session->send('sys: joined #lobby — send `/join <room>` to switch rooms');
         })
         // # Relay messages to the current room; `/join <room>` switches
         ->on(Events::MessageReceived, function ($Session, $Message) use (&$rooms) {
            $id = spl_object_id($Session);
            $room = $rooms[$id] ?? 'lobby';
            $payload = $Message->payload;

            // ?: Room switch
            if (str_starts_with($payload, '/join ')) {
               $next = (string) preg_replace('/[^a-z0-9_-]/i', '', substr($payload, 6));
               if ($next === '') {
                  return 'sys: invalid room name';
               }

               $Session->broadcast($room, 'sys: a peer left the room');
               $Session->leave($room);
               $Session->join($next);
               $rooms[$id] = $next;
               $Session->broadcast($next, 'sys: a peer joined the room');

               return "sys: joined #{$next}";
            }

            // @ Relay to everyone else in the room; echo to the sender
            $Session->broadcast($room, $payload);

            return "you: {$payload}";
         })
         // # Cleanup the room map
         ->on(Events::Disconnected, function ($Session) use (&$rooms) {
            unset($rooms[spl_object_id($Session)]);
         })
         ->on(Events::ServerStarted, function ($WS_Server_CLI) {
            $Output = CLI->Terminal->Output;

            $protocol = $WS_Server_CLI->socket ?? 'ws://';
            $host = $WS_Server_CLI->host ?? '0.0.0.0';
            $port = $WS_Server_CLI->port ?? 0;

            $Output->render('@.;@#green:✓ Bootgly Chat started@;@.;');
            $Output->render("  Listening on @#cyan:{$protocol}{$host}:{$port}@;@.;");
            $Output->render("  @#Green:Tip:@; Open @#Black:http://localhost:{$port}@; in two browser tabs to chat.@..;");
         })
         ->on(Events::ServerStopped, function ($WS_Server_CLI) {
            $Output = CLI->Terminal->Output;

            $Output->render('@.;@#yellow:■ Bootgly Chat stopped@;@.;');
         });

      $WS_Server_CLI->start();
   }
);
