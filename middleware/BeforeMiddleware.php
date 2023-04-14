<?php

use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

class BeforeMiddleware{

    public function __invoke( $request,  $handler): Response
    {
    
        if(!IS_APP_LIVE){
            sleep(1);
        }
       
        $response = $handler->handle($request);
        // $existingContent = (string) $response->getBody();
    
        // $response = new Response();
        // $response->getBody()->write('BEFORE' . $existingContent);
    
        return $response;
    }
}