<?php
class Apihandler
{
	function apirequest($request)
	{
         
		$uri = $request->getUri();
		$requestIP = $request->getServerParam('REMOTE_ADDR');
		$body=$request->getBody();
		$payload=json_decode($body); 
              // print_r($payload);exit;
		return $payload;
	}
	
	function apiresponse($response,$data)
	{
		return $response->withJson($data, 200);
	}
	
}



?>
