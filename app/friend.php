<?php

    // Api for sendFriend, cancel Friend, UnFriend and accept Friend
    $app->post('/performaction',function($request,  $response,  $args){

        require_once __DIR__ . '/../bootstrap/dbconnection.php';

        $userId = $request->getParsedBody()['uid'];
        $profileId = $request->getParsedBody()['profileId'];
        $operationType = $request->getParsedBody()['operationType'];

		/*
			1 -> Unfriend
			2 -> cancel friend request
			3 -> accept friend request
			4 -> send friend request
		*/

        if($operationType==1){
			// unfriend friend Request
			$deletion1 = delete($response,'friends', array('userId' => $userId, 'profileId' => $profileId));
			$deletion2 = delete($response,'friends', array('userId' => $profileId, 'profileId' => $userId));

			if((  is_bool($deletion1) && is_bool($deletion2) )){
				$output['status']  = 200;
				$output['message'] = "Unfriend Successfully !";

				$payload = json_encode($output);
				$response->getBody()->write($payload);
				return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
			}
			
		
        }else if($operationType==2){
			// cancel friend Request

			if(is_bool(delete($response,'requests', array('sender' => $userId, 'receiver' => $profileId)))){
			
	
			$output['status']  = 200;
			$output['message'] = "Friend Request Cancelled !";
			$payload = json_encode($output);
			$response->getBody()->write($payload);
			return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
		}

        }else if($operationType==3){
			// accept friend Request

			$request = checkRequest($userId,$profileId);
			$state = 0;

			if($request){
				if($request['sender']==$userId){
					// we have send the request
					$state = "2";
				}else{
					$state="3";
					//we have received the request
				}
			}

			if($state!=0){
				$insertion1 = insert($response,'friends', array('userId' => $userId, 'profileId' => $profileId, 'friendOn' => date('Y-m-d H:i:s')));
				$insertion2 = insert($response,'friends', array('userId' => $profileId, 'profileId' => $userId, 'friendOn' => date('Y-m-d H:i:s')));

				$deletion1 = delete($response,'requests', array('sender' => $userId, 'receiver' => $profileId));
				$deletion2 = delete($response,'requests', array('sender' => $profileId, 'receiver' => $userId));

			if( ( is_bool($insertion1)  &&  is_bool($insertion2) ) && (  is_bool($deletion1) ||  is_bool($deletion2))){
				
				$output['status']  = 200;
				$output['message'] = "You are Friends Now !";
			
				$payload = json_encode($output);
				$response->getBody()->write($payload);
				return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
			}
			}else{
				$output['status']  = 500;
				$output['message'] = "No Friend request record found on Server to accept friend request !";

				$payload = json_encode($output);
				$response->getBody()->write($payload);
				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}

        }else if($operationType==4){
			// send friend Request
			$result = insert($response,
							'requests',
							 array('sender' => $userId, 'receiver' => $profileId, 'requestDate' => date('Y-m-d H:i:s'))
							);

			if(is_bool($result)){

				
				$output['status']  = 200;
				$output['message'] = "Friend Request Sent !";
				$payload = json_encode($output);
				$response->getBody()->write($payload);
				return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
			}
			return $result;
        }
						
});
	// Api for laoding Friends and Requests
	$app->get('/loadfriends',function($request,  $response,  $args){

		include __DIR__ .'/../bootstrap/dbconnection.php';
		$userId = $request->getQueryParams()['uid'];

		$query = $pdo->prepare('
								SELECT user.* FROM `user` 
								Inner JOIN `requests`
								ON user.uid = requests.sender
								WHERE `receiver` = :userId'
							);

		$query->bindParam(':userId', $userId, PDO::PARAM_STR);

		$query->execute();	

		$errorData = $query->errorInfo();
		if($errorData[1]){
			return checkError($response, $errorData);
		}

		$result['requests']= $query->fetchAll(PDO::FETCH_ASSOC);

		$query = $pdo->prepare('
								SELECT user.* FROM `user` 
								Inner JOIN `friends`
								ON user.uid = friends.profileId
								WHERE friends.userId = :userId'
							);

		$query->bindParam(':userId', $userId, PDO::PARAM_STR);
		$query->execute();	

		$errorData = $query->errorInfo();
		if($errorData[1]){
			return checkError($response, $errorData);
		}

		$result['friends'] = $query->fetchAll(PDO::FETCH_ASSOC);	

		$output['status']  = 200;
		$output['message'] = "Friends and Requests list Fetched !";
		$output['result'] = $result;


		$payload = json_encode($output);
		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

	});

	 function insert($response, $table, $fields = array()){
	 	include __DIR__ . '/../bootstrap/dbconnection.php';
		
		$columns = implode(',', array_keys($fields));

		$values  = ':'.implode(', :', array_keys($fields));

		$sql     = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";

		$query = $pdo->prepare($sql);

		foreach ($fields as $key => $data) {
			$query->bindValue(':'.$key, $data);
		}

		$query->execute();
		$errorData = $query->errorInfo();
		if($errorData[1]){
			return checkError($response,$errorData);
		}
		return true;
		
	}

	 function delete($response,$table, $array){
	 	include __DIR__ . '/../bootstrap/dbconnection.php';
		
		$sql   = "DELETE FROM " . $table;
		$where = " WHERE ";

		foreach($array as $key => $value){
			$sql .= $where . $key . " = '" . $value . "'";
			$where = " AND ";
		}

		$sql .= ";";
		$query = $pdo->prepare($sql);

        $query->execute();
        $errorData = $query->errorInfo();
        if($errorData[1]){
			return checkError($response,$errorData);
        }
		return true;
		
	}

?>