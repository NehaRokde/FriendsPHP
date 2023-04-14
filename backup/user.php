<?php


$app->post('/login', function ($request, $response, $args) {

    require_once __DIR__ .'/../bootstrap/dbconnection.php';

    $output = array();
    $requestData = array();


    $requestData['uid'] = $request->getParsedBody()['uid'];
    $requestData['name'] = $request->getParsedBody()['name'];
    $requestData['email']  = $request->getParsedBody()['email'];
    $requestData['profileUrl']  = $request->getParsedBody()['profileUrl'];
    $requestData['coverUrl']  = $request->getParsedBody()['coverUrl'];
    $requestData['userToken']  = $request->getParsedBody()['userToken'];

 
    $query = $pdo->prepare("SELECT `uid` from `user` WHERE `uid` = :uid LIMIT 1");
    $query->bindparam(':uid', $requestData['uid']);
    $query->execute();

    $errorData = $query->errorInfo();
    if($errorData[1]){
        return checkError($response, $errorData);
    }


    $count = $query->rowCount();

    if($count == 1){
        //update data
        $query = $pdo->prepare("UPDATE `user` SET 
        `name`= :name,  `email`= :email, `profileUrl`= :profileUrl, `coverUrl`= :coverUrl, `userToken`= :userToken
        WHERE `uid` = :uid; ");
        
        $query->execute($requestData);
     
    }else{
        //create user
        $query = $pdo->prepare("INSERT INTO `user` (`uid`, `name`, `email`, `profileUrl`, `coverUrl`, `userToken` )
        VALUES(:uid, :name, :email, :profileUrl, :coverUrl, :userToken);  ");

        $query->execute($requestData);
    }

    $errorData = $query->errorInfo();
    if($errorData[1]){
        return checkError($response, $errorData);
    }

    $output['status'] = 200;
    $output['message'] = "Login Success";
    $output['auth'] = $requestData;

    $payload = json_encode($output);
    $response->getBody()->write($payload);

    return $response->withHeader('Content-Type','application/json')->withStatus(200);
});

$app->get('/loadprofileinfo', function($request, $response, $args){

    require_once __DIR__ .'/../bootstrap/dbconnection.php';

    $output = array();
    $userId = $request->getQueryParams()['userId'];
    $state = 0;

    if(isset($request->getQueryParams()['current_state'])){
        $state = $request->getQueryParams()['current_state'];
    }else{
        // handle it later
    }

    $query = $pdo->prepare('SELECT * FROM  `user` WHERE `uid` = :userId');
    $query->bindparam(':userId', $userId, PDO::PARAM_STR);
    $query->execute();

    $errorData = $query->errorInfo();
    if($errorData[1]){
        return checkError($response, $errorData);
    }

    $result = $query->fetch(PDO::FETCH_ASSOC);

    $result['state']=$state;
    $output['status'] = 200;
    $output['message'] = "Profile Data Retrieved";
    $output['profile'] = $result;

    $payload = json_encode($output);
    $response->getBody()->write($payload);

    return $response->withHeader('Content-Type','application/json')->withStatus(200);


});

?>
