<?php

use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

class AfterMiddleware{

    public function __invoke( $request,  $handler): Response
    {
        $response = $handler->handle($request);
        $response->getBody()->write('Slim Framwork');
        return $response;
    }
}