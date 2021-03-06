<?php
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth. 
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.

if(empty($_GET['token']) || ($_GET['token'] != $slackactivitiestoken)) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die; //If there is no text added, kill the connection.
$exploded = explode("|",$_GET['text']); //Explode the string attached to the slash command for use in variables.

$urlactivities = $connectwise . "/v4_6_release/apis/3.0/sales/activities/";
$activityurl = $connectwise . '/v4_6_release/ConnectWise.aspx?fullscreen=false&locale=en_US#startscreen=activity_detail&state={"p":"activity_detail", "s":{"p":{"pid":3, "rd": ';
$activityurl2 = ' ,"compId":0, "contId":0, "oppid":0}}}';

$utc = time(); //Get the time.
// Authorization array. Auto encodes API key for auhtorization above.
$header_data =array(
 "Authorization: Basic ". $authorization,
);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 =array(
"Authorization: Basic " . $authorization,
 "Content-Type: application/json"
);

$command=NULL; //Create a command variable and set it to Null
if (array_key_exists(0,$exploded)) //If a string exists in the slash command array, make it the command.
{
	$command = $exploded[0];
}

//Need to create array before hand to ensure no errors occur.
$dataResponse = array();

if($command=="new") { 
$ch = curl_init();

$postfieldspre = array("name"=>$exploded[1],"status"=>array("id"=>1),"assignTo"=>array("identifier"=>$exploded[2])); //Command array to post activity
$postfields = json_encode($postfieldspre); //Format the array as JSON

$curlOpts = array(
	CURLOPT_URL => $urlactivities,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => $header_data2,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => $postfields,
	CURLOPT_POST => 1,
	CURLOPT_HEADER => 1,
);
curl_setopt_array($ch, $curlOpts);

$answerResponse = curl_exec($ch);
$headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE); 
$curlResponse = substr($answerResponse, $headerLen);
// If there was an error, show it
if (curl_error($ch)) {
	die(curl_error($ch));
}
curl_close($ch);
$dataResponse = json_decode($curlResponse);
}

$return="Unknown command.";
if($command == "new") //If command is new.
{
	$return =array(
		"parse" => "full", //Parse all text.
		"response_type" => "in_channel", //Send the response in the channel
		"attachments"=>array(array(
			"title" => "New Activity Created: " . $dataResponse->name, //Set bolded title text
			"pretext" => "Activity #" . $dataResponse->id . " has been created and assigned to " . $exploded[2], //Set pretext
			"text" => "Click <" . $activityurl . $dataResponse -> id . $activityurl2 . "|here> to open the activity.", //Set text to be returned
			"mrkdwn_in" => array( //Set markdown values
				"text",
				"pretext"
				)
			))
		);
}
else
{
	echo $return;
	return;
}
echo json_encode($return);
?>