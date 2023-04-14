<?php
	$app->post('/postcomment',function($request,  $response,  $args){
	include_once __DIR__ . '/../bootstrap/dbconnection.php';
	
		// The actual Comment
        $comment = $request->getParsedBody()['comment'];
        
		// UserId of a user who made a comment
        $commentBy =  $request->getParsedBody()['commentBy'];
        
		/*
			postId on which comment is posted
		*/
		$commentPostId =  $request->getParsedBody()['commentPostId'];
		
		
		/*
			It is the userId of a postOwner
		*/
		$postUserId =  $request->getParsedBody()['postUserId'];
		/*
			Simple flag to check whether user is replying to a post or to a comment
		*/
        $commentOn = $request->getParsedBody()['commentOn'];
        
		/*
            if user is replying to a comment, 
            it will be userId of the parent comment on which user is replying to otherwise -1
		*/
        $commentUserId = $request->getParsedBody()['commentUserId'];

        /*
            if user is replying to a comment, 
            then its value will be parent CommentId otherwise -1
        */
		$parentId =  $request->getParsedBody()['parentId'];
		
	
        $query = $pdo->prepare("INSERT INTO `comments` 
        (`comment`, `commentBy`, `commentDate`,`commentPostId`,`parentId`,`commentOn`) 
        VALUES (:comment, :commentBy, current_timestamp , :commentPostId, :parentId, :commentOn); ");

        $query->bindParam(':comment', $comment, PDO::PARAM_STR);
        $query->bindParam(':commentBy', $commentBy, PDO::PARAM_STR);
        $query->bindParam(':commentPostId', $commentPostId, PDO::PARAM_STR);
        $query->bindParam(':parentId', $parentId, PDO::PARAM_STR);
        $query->bindParam(':commentOn', $commentOn, PDO::PARAM_STR);

        $query->execute();

        $errorData = $query->errorInfo();
        if($errorData[1]){
            return checkError($response, $errorData);
        }

        $cid = $pdo->lastInsertId();

        $query = $pdo->prepare("UPDATE `posts` SET  `commentCount` = `commentCount`+1   WHERE `postId` = :postId");
        $query->bindParam(":postId", $commentPostId, PDO::PARAM_INT);
        $query->execute();
    
        $errorData = $query->errorInfo();
        if($errorData[1]){
            return checkError($response, $errorData);
        }

        $comment = array();
        if($commentOn=="post"){
            $recentComment = getThisPostComment($commentPostId,$cid);
            $recentComment['comments']=array();
            $comment[0] = $recentComment;
        }else{
            $recentComment =getThisCommentReply($commentPostId,$parentId,$cid);
            $recentComment['comments']=array();
            $comment[0] = $recentComment;
        }
       
        $comment[0]['postUserId'] = $postUserId;

        $output['status']  = 200;
		$output['message'] = "Comment Posted";
		$output['comments'] = $comment;

		$payload = json_encode($output);
		$response->getBody()->write($payload);
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        
	});


$app->get('/getpostcomments',function($request,  $response,  $args){
    include_once __DIR__ . '/../bootstrap/dbconnection.php';
        
    $postId = $request->getQueryParams()['postId'];
    $postUserId = $request->getQueryParams()['postUserId'];
    
    $query = $pdo->prepare("
        SELECT comments.*, user.name, user.profileUrl, user.userToken
        FROM `comments`
        INNER JOIN `user`
        ON 	comments.commentBy = user.uid
        WHERE `commentPostId` = :postId 
        AND `commentOn`='post' 
        ORDER BY commentDate DESC");
            
    $query->bindParam(":postId", $postId, PDO::PARAM_INT);
    $query->execute();
    
    $errorData = $query->errorInfo();
    if($errorData[1]){
        return checkError($response, $errorData);
    }


    $comments= $query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = array();
    foreach ($comments as $key => $comment) {
        $result[$key] = $comment;
        $result[$key]['totalCommentReplies'] = getTotalCommentReplies($postId,$comment['cid']);
        $result[$key]['postUserId'] = $postUserId;
        $result[$key]['comments'] =  getLastComment($postId,$comment['cid']);

    }

    $output['status']  = 200;
    $output['message'] = "Post Comment Retrieved";
    $output['comments'] = $result;

    $payload = json_encode($output,JSON_NUMERIC_CHECK);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

                            
    });


    $app->get('/getcommentreplies',function($request,  $response,  $args){
        include_once __DIR__ . '/../bootstrap/dbconnection.php';
            
        $postId = $request->getQueryParams()['postId'];
        $commentId = $request->getQueryParams()['commentId'];
        
        $query = $pdo->prepare("

            SELECT comments.*, user.name, user.profileUrl, user.userToken
            FROM `comments`
            INNER JOIN `user`
            ON 	comments.commentBy = user.uid
            WHERE `commentPostId`=:commentPostId 
            AND `parentId` = :commentId  
            AND `commentOn`= 'comment'
            ORDER BY commentDate DESC");
                
		$query->bindParam(":commentPostId", $postId, PDO::PARAM_INT);
		$query->bindParam(":commentId", $commentId, PDO::PARAM_INT);
		$query->execute();
        $comment = $query->fetchAll(PDO::FETCH_ASSOC);
    
        $output['status']  = 200;
        $output['message'] = "Post Comment Retrieved";
        $output['comments'] = $comment;

        $payload = json_encode($output);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

                    
    });

    function getThisPostComment($postId,$commentId){
        include __DIR__ . '/../bootstrap/dbconnection.php';
        
		$query = $pdo->prepare("
				SELECT comments.*,user.name, user.profileUrl, user.userToken
				FROM `comments`
				INNER JOIN `user`
				ON 	comments.commentBy = user.uid
                WHERE `commentPostId` = :postId 
                AND `cid`=:commentId");

		$query->bindParam(":postId", $postId, PDO::PARAM_INT);
		$query->bindParam(":commentId", $commentId, PDO::PARAM_INT);
		$query->execute();
		return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    
	function getThisCommentReply($commentPostId,$parentId,$commentId){
        include __DIR__ . '/../bootstrap/dbconnection.php';
        
		$query = $pdo->prepare("
				SELECT comments.*,user.name, user.profileUrl, user.userToken
				FROM `comments`
				INNER JOIN `user`
				ON 	comments.commentBy = user.uid
                WHERE `commentPostId`=:commentPostId 
                AND`parentId` = :parentId 
                AND `cid`=:commentId");
                
		$query->bindParam(":commentPostId", $commentPostId, PDO::PARAM_INT);
		$query->bindParam(":parentId", $parentId, PDO::PARAM_INT);
		$query->bindParam(":commentId", $commentId, PDO::PARAM_INT);
		$query->execute();
		return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    /*
        post ( This is our post  )
                -> ( this is my comment)
                    -> stop commenting on a random post
                    -> its his choice
                    -> haha
    */
    function  getLastComment($postId,$parentComment){
		include __DIR__ . '/../bootstrap/dbconnection.php';
	
		$query = $pdo->prepare("
				SELECT comments.* ,user.name, user.profileUrl, user.userToken
				FROM `comments`
				INNER JOIN `user`
				ON 	comments.commentBy = user.uid
                WHERE `commentPostId`=:postId 
                AND `parentId` = :parentComment 
                AND `commentOn`='comment' ORDER BY commentDate DESC LIMIT 1");
                
		$query->bindParam(":postId", $postId, PDO::PARAM_INT);
		$query->bindParam(":parentComment", $parentComment, PDO::PARAM_INT);
		$query->execute();
		return $query->fetchALL(PDO::FETCH_ASSOC);
    }
    
	function getTotalCommentReplies($postId,$parentComment){
		include __DIR__ . '/../bootstrap/dbconnection.php';
	
		$query = $pdo->prepare("
				SELECT count(*) as totalCount
				FROM `comments`
                WHERE `commentPostId`=:postId 
                AND `parentId` = :parentComment 
                AND `commentOn`='comment' ");
                
		$query->bindParam(":postId", $postId, PDO::PARAM_INT);
		$query->bindParam(":parentComment", $parentComment, PDO::PARAM_INT);
		$query->execute();
        $query= $query->fetchALL(PDO::FETCH_ASSOC);
		return ($query[0]['totalCount']);
    }
    
?>