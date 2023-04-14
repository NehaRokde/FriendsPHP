<?php
$app->post('/uploadpost', function ( $request,  $response,  $args) {

    require_once __DIR__ .'/../bootstrap/dbconnection.php';

 
    /* 
        privacy level flag
        0  => friends
        1  => only me
        2  = > public

    */



$requestData = array();
$output = array();

// $requestData['post'] = isset($request->getParsedBody()['post']) ? $request->getParsedBody()['post'] : null;
// $requestData['postUserId'] = isset($request->getParsedBody()['postUserId']) ? $request->getParsedBody()['postUserId'] : null;
// $requestData['privacy'] = isset($request->getParsedBody()['privacy']) ? $request->getParsedBody()['privacy'] : null;
// $requestData['statusTime'] = date('Y-m-d H:i:s', time());
// $requestData['statusImage'] = "";



$requestData['post'] = $request->getParsedBody()['post'];
$requestData['postUserId'] = $request->getParsedBody()['postUserId'];
$requestData['privacy'] = $request->getParsedBody()['privacy'];
$requestData['statusTime'] = date('Y-m-d H:i:s', time());
$requestData['statusImage'] = "";


 if(isset($_FILES['file']['tmp_name'])){
        if (move_uploaded_file( $_FILES ['file'] ["tmp_name"], "../uploads/" . $_FILES ["file"] ["name"] )) {
            $requestData['statusImage'] = "../uploads/" . $_FILES ["file"] ["name"];
        }else{
            
            $output['status']  = 500;
            $output['message'] = "Couldn't Upload Image to Server !";

            $payload = json_encode($output);
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    try {
        if (isset($requestData['post']) && !empty($requestData['post'])) {
            $query = $pdo->prepare("INSERT INTO `posts` (`post`, `postUserId`, `statusImage`, `statusTime`,  `privacy`) VALUES (:post, :postUserId, :statusImage, :statusTime, :privacy)");
            $result = $query->execute($requestData);
        
            if ($result) {
                // echo "Query executed successfully";
                $output['status']  = 200;
                $output['message'] = "Post Uploaded Successfully !";

                $payload = json_encode($output);
                $response->getBody()->write($payload);
            } else {
                echo "Error executing query";
            }
        } else {
            echo "Error: post value is null or empty";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }


return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

});




//Api for add/remove reaction
$app->post('/performReaction',function($request, $response,  $args){


    include __DIR__ .'/../bootstrap/dbconnection.php';

    $userId = $request->getParsedBody()['userId'];
    $postId =  $request->getParsedBody()['postId'];
    $postOwnerId =  $request->getParsedBody()['postOwnerId'];
    $previousReactionType = $request->getParsedBody()['previousReactionType'];
    $newReactionType = $request->getParsedBody()['newReactionType'];
    $message = "Operation Successful";

    /*
        (previousReactionType , newReactionType)
        like, love, care, haha, wow, sad, angry, default

        default -> used to undo reactions
    */

    $oldReactionColumn = checkColumnName($previousReactionType);
    $newReactionColumn = checkColumnName($newReactionType);

    if($newReactionType == "default"){
        
        if($previousReactionType == "default"){
            $message = "No Operation Performed";
            $reactions = getReactionCount($postId);
            $reactions->reactionType = 'default';
            
            $output['status']  = 200;
            $output['message'] = $message;
            $output['reaction'] = $reactions;

            $payload = json_encode($output,JSON_NUMERIC_CHECK);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);   
            
        }
            // decrease counter of old reaction
            $stmt = $pdo->prepare(" UPDATE `posts` 
                                SET ". $oldReactionColumn ." = " . $oldReactionColumn ." -1 "." 
                                WHERE `postId` = :postId");

            $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt->execute();

            $errorData = $stmt->errorInfo();
            if($errorData[1]){
                return checkError($response, $errorData);
            }

        // remove old reaction from reaction table
        $stmt = $pdo->prepare( "DELETE FROM `reactions` WHERE
                                `reactionBy` = :userId AND
                                `postOn` = :postId 
                             ");

        $stmt->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        $errorData = $stmt->errorInfo();
        if($errorData[1]){
        return checkError($response, $errorData);
        }  
        $message = "Reaction Undo Successfull";
    }else{
        // previous = care, newReaction = wow
        if($previousReactionType != "default"){
        // decrease counter of old reaction
        $stmt = $pdo->prepare(" UPDATE `posts` 
        SET ". $oldReactionColumn ." = " . $oldReactionColumn ." -1 "." 
        WHERE `postId` = :postId");

        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        $errorData = $stmt->errorInfo();
        if($errorData[1]){
        return checkError($response, $errorData);
        }

        // remove old reaction from reaction table
        $stmt = $pdo->prepare( "DELETE FROM `reactions` WHERE
        `reactionBy` = :userId AND
        `postOn` = :postId 
        ");

        $stmt->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        $errorData = $stmt->errorInfo();
        if($errorData[1]){
        return checkError($response, $errorData);
        }
      }
       

        // increase counter of new reaction
        $stmt = $pdo->prepare(" UPDATE `posts` 
                            SET ". $newReactionColumn ." = " . $newReactionColumn ." +1 "." 
                            WHERE `postId` = :postId");

        
      
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        $errorData = $stmt->errorInfo();
        if($errorData[1]){
            return checkError($response, $errorData);
        }

        // insert new reaction to reactions table
        $stmt = $pdo->prepare( "INSERT INTO `reactions` 
                                (`reactionBy`, `postOn`, `reactionType` ) 
                                VALUES (:reactionBy, :postOn, :reactionType); ");


        $stmt->bindParam(':reactionBy', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':postOn', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':reactionType', $newReactionType, PDO::PARAM_STR);

        $stmt->execute();

        $errorData = $stmt->errorInfo();
        if($errorData[1]){
            return checkError($response, $errorData);
        }
        $message = "Reaction changed from ".$previousReactionType. " to ".$newReactionType;
    }
        // send back the updated reaction counts
        $reactions = getReactionCount($postId);
        $reactions->reactionType = $newReactionType;

        $output['status']  = 200;
        $output['message'] = $message;
        $output['reaction'] = $reactions;

        $payload = json_encode($output,JSON_NUMERIC_CHECK);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);         
    

});


function getReactionCount($postId){

    include __DIR__ . '/../bootstrap/dbconnection.php';

    $stmt =  $pdo->prepare("  SELECT 
                             `likeCount` , `loveCount`, `careCount`,
                             `hahaCount`, `wowCount`, `sadCount`, `angryCount`
                              from `posts` WHERE `postId` = :postId LIMIT 1");


    $stmt->bindParam(':postId', $postId, PDO::PARAM_STR);
    $stmt->execute();
    
    $errorData = $stmt->errorInfo();

    $userInfo =$stmt->fetch(PDO::FETCH_OBJ);
    return $userInfo;

    }

function checkColumnName ($reactionType){
    $columnName = "likeCount";
    if($reactionType == "love"){

        $columnName = "loveCount";

    }else if($reactionType == "care"){

       $columnName = "careCount";

    }else if($reactionType == "haha"){

       $columnName = "hahaCount";

    }else if($reactionType == "wow"){

       $columnName = "wowCount";

    }else if($reactionType == "sad"){

       $columnName = "sadCount";

    }else if($reactionType == "angry"){

       $columnName = "angryCount";

    }
    return $columnName;
}
?>