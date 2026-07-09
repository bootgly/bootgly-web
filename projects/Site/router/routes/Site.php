<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

/**
 * Site routes — controller-dispatched pages, statics and a custom 404.
 */

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Web\API\Action;
use Web\App\Statics;

use projects\Site\Controllers\Pages;


return static function (Request $Request, Response $Response, Router $Router): Generator
{
   // * Pages — one controller action serves every page (`:page` param)
   $Show = new Action(Pages::class, 'show');
   yield $Router->route('/', $Show, GET);
   yield $Router->route('/:page<alpha>', $Show, GET);

   // * Statics — served inline with the right media type
   yield $Router->route('/statics/:file*', new Statics, GET);

   // * Fallback
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      $Response->View->render('errors/404');

      return $Response->code(404);
   }, GET);
};
