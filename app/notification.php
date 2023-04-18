<?php

function sendInDeviceNotification($token,$title,$body){

		$clickAction = "OPEN_MAIN_ACTIVITY";

		$msg = array
		(
		'body' 	=> $body,
		'title'	=> $title,
		'icon'	=> 'default',
		'sound' => 'default',
		'click_action'=>$clickAction
		);


		$data = array(
				'isFromNotification' => 'true'
		);
		$fields = array
				(
                    'to'		    =>$token,
                    'notification'	=> $msg,
                    'data'          => $data
				);


		$headers = array
				(
					'Authorization: key=' . API_ACCESS_KEY,
					'Content-Type: application/json'
				);

		#Send Reponse To Firebase Server
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result = curl_exec($ch );
			curl_close( $ch );

	}
	$app->post('/testnotification',function($request,  $response,  $args){
		$token = "fyRvm05fRBumBPT_xkyOFu:APA91bFNLyCbUrHTxJDZaK5LxrfU8cA2oUA5S0g4lRYamHapoF1i_TjjT2zCkakRyJ0Y6FKU3ORtEdHTa2E-u0Mk7QzPQWBGavJz38a02HeTapkCpOD-hysSDUc_6BVd27ofsiFz7Yzc";

		$title = "From PHP after";
		$body = "This is a test notification from php";

		sendInDeviceNotification($token,$title,$body);

		$output['status']  = 200;
		$output['message'] = "Notification Sent !";
		$payload = json_encode($output);
		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	});


    function getUserInfo($userId){
		include __DIR__ . '/../bootstrap/dbconnection.php';
		$stmt =  $pdo->prepare("SELECT user.name, user.userToken from `user` WHERE `uid` = :uid LIMIT 1");
		$stmt->bindParam(':uid', $userId, PDO::PARAM_STR);
		$stmt->execute();
		$userInfo =$stmt->fetch(PDO::FETCH_OBJ);
		return $userInfo;
    }
    
    function sendNotification($toUserId, $fromUserId, $postId, $type){
		include __DIR__ . '/../bootstrap/dbconnection.php';
		
        $stmt = $pdo->prepare("INSERT INTO `notifications` 
        (`notificationTo`, `notificationFrom`, `type`,`notificationTime`,`postId`)
        VALUES (:notificationTo, :notificationFrom,:type, current_timestamp,:postId); ");

        $stmt->bindParam(':notificationTo', $toUserId, PDO::PARAM_STR);
        $stmt->bindParam(':notificationFrom', $fromUserId, PDO::PARAM_STR);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

	
        $senderInfo = getUserInfo($fromUserId);
        $receiverInfo = getUserInfo($toUserId);

        if($type == 'post-reaction'){
            $title = "New Reaction";
            $body  = $senderInfo->name . " reacted on your post";
        }else if($type == 'post-comment'){
            $title = "New Comment";
            $body  = $senderInfo->name . " commented on your post";
        }else if($type == 'comment-reply'){
            $title = "New replies";
            $body  = $senderInfo->name . " replied on your comment";
        }else if ($type == 'request-accepted'){
			$title = "Request Accepted";
            $body  = $senderInfo->name . " accepted your friend request";
		}else if($type == 'friend-request'){
			$title = "New Friend Request";
            $body  = $senderInfo->name . " send you friend request";
		}else{
			return;
		}

        $token = $receiverInfo->userToken;
        sendInDeviceNotification($token,$title,$body);
                      

    }
	$app->get('/getnotification',function($request,  $response,  $args){

		include __DIR__ . '/../bootstrap/dbconnection.php';
		 $userId = $request->getQueryParams()['uid'];

		$query = $pdo->prepare('
	
			SELECT notifications.*,user.name,user.profileUrl, posts.post FROM `notifications`
			LEFT join user
			ON
			notifications.notificationFrom = user.uid
			LEFT join posts
			ON
			notifications.postId = posts.postId 
			WHERE `notificationTo` = :userId 
			ORDER BY
			notifications.notificationTime
			DESC

			');


	    $query->bindParam(':userId', $userId, PDO::PARAM_STR);
		$query->execute();		

		$errorData = $query->errorInfo();
		if($errorData[1]){
			return checkError($response, $errorData);
		}
		
		$result = $query->fetchAll(PDO::FETCH_ASSOC);	

		$output['status']  = 200;
		$output['message'] = "Successfully Fetched Notifications !";
		$output['notifications'] = $result;

		$payload = json_encode($output);
		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	});
?>