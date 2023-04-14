<?php


function checkError($response, $errorData){

 $output['status'] = $errorData[1];
 $output['message'] = IS_APP_LIVE ? "Query Failed" : $errorData[2];

 $payload = json_encode($output);
 $response->getBody()->write($payload);
 
 return $response->withHeader('Content-Type','application/json')->withStatus(500);
}