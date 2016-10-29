<?php

$subscriptionKey = 'febcde8f1653414fa734b4ccb0f65de1';
$url_token = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';
$url_cognitive= 'https://speech.platform.bing.com/';
$app_id_cognitive="D4D52672-91D7-4C74-8AD8-42B1D98141A5";
$locale="en-US";
$deviceos="wp7";
$version_cog="3.0";
$format_cog="json";
//curl -v -X POST "https://speech.platform.bing.com/recognize?scenarios=smd&appid=D4D52672-91D7-4C74-8AD8-42B1D98141A5&locale=your_locale&device.os=your_device_os&version=3.0&format=json&instanceid=your_instance_id&requestid=your_request_id" -H 'Authorization: Bearer your_access_token' -H 'Content-type: audio/wav; codec="audio/pcm"; samplerate=16000' --data-binary @your_wave_file


$audioFile = array();
$audioFilename = "";
$audioFile = isset($_FILES['audioFile']) ? $_FILES['audioFile'] : array();
$audioFilename = isset($audioFile['name']) ? trim($audioFile['name']) : "";


function buildRequestString($appId, $appKey, $id)
{
        // set the base URL
        $url = 'https://dictation.nuancemobility.net/NMDPAsrCmdServlet/dictation';
        // set the name/value pairs to be passed in as part of the URI
        $fields = array(
                                                        'appId'=>urlencode($appId),
                                                        'appKey'=>urlencode($appKey),
                                                        'id'=>urlencode($id),
                                );
        // Build the name=value string
        $fields_string = "";
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        $fields_string = rtrim($fields_string,'&');
        // Decorate the URL with the name/value pairs
        $url .= '?' . $fields_string;
        // And we're done.
        return $url;
}

/*
 * This function executes the HTTP Request
 *
 * The HTTP response will be returned to the calling routine
 *
 */
function executeRequest($url, $header, $audio)
{
        // First, we need to set a few SSL options
        $sslOptions = array();
        $sslOptions['verifypeer'] = "0";
        $sslOptions['verifyhost'] = "0";
        // Create an HttpRequest object
        $r = new HttpRequest($url, HttpRequest::METH_POST);
        // Set the SSL options, Headers, Content-Type, and body
        $r->setSslOptions($sslOptions);
        $r->setHeaders($header);
        $r->setContentType($header['Content-Type']);
        $r->setBody($audio);
        try {
                // Send the request
                $m = $r->send();
                // Return the response object
                return $m;
        } catch (HttpException $ex) {
                // If an error occurs, just display it to the web page
            echo '<br><br><font color="red" Exception: ' . $ex . '</font><br><br>';
        }
}



function getToken($subscriptionKey, $url_token, $audio)
{
	//Setting Headers
        $header = array();
        $header['Content-Type'] = 'application/x-www-form-urlencoded';
        $header['Content-Length'] = '0';
        $header['Ocp-Apim-Subscription-Key'] = $subscriptionKey;
        // And we're done.

	$resp = executeRequest($url_token, $header, $audio);
	//var_dump($resp);
	$parsed_body = http_parse_message($resp);
	$parsed_headers = http_parse_headers($resp);
	//print_r($parsed_headers);
	//$request_id=$parsed_body->"Apim-Request-Id";
	$request_id = array_values($parsed_headers)[9];
	$token = $parsed_body->body;
	//echo '<a> This is the token: ' . $token . '</a>'; 
        return array($token, $request_id);
}


function sendAudio($subscriptionKey, $url_cognitive, $auth_data, $locale, $deviceos, $version_cog, $format_cog, $app_id_cognitive, $audio)
{
//curl -v -X POST "https://speech.platform.bing.com/recognize?scenarios=smd&appid=D4D52672-91D7-4C74-8AD8-42B1D98141A5&locale=your_locale&device.os=your_device_os&version=3.0&format=json&instanceid=your_instance_id&requestid=your_request_id" -H 'Authorization: Bearer your_access_token' -H 'Content-type: audio/wav; codec="audio/pcm"; samplerate=16000' --data-binary @your_wave_file

##                https://speech.platform.bing.com/recognize?scenarios=smd&appid=D4D52672-91D7-4C74-8AD8-42B1D98141A5&locale=en-US&device.os=wp7&version=3.0&format=json&instanceid=f819ad8a-ffd6-4782-bfa7-53ffdbf4eb26&requestid=f819ad8a-ffd6-4782-bfa7-53ffdbf4eb26
###                                               /recognize?scenarios=smd&appid=D4D52672-91D7-4C74-8AD8-42B1D98141A5&locale=en-US&device.os=wp7&version=3.0&format=json&instanceid=6b5dd634-58eb-411d-bd4c-406f7ba04f40&requestid=6b5dd634-58eb-411d-bd4c-406f7ba04f40
#POST                                             /recognize?scenarios=catsearch&appid=f84e364c-ec34-4773-a783-73707bd9a585&locale=en-US&device.os=wp7&version=3.0&format=xml&requestid=1d4b6030-9099-11e0-91e4-0800200c9a66&instanceid=1d4b6030-9099-11e0-91e4-0800200c9a66 HTTP/1.1
	 //Setting Headers
        $header = array();
        $header['Content-Type'] = 'audio/wav; codec="audio/pcm"; samplerate=16000';
        $header['Authorization'] = 'Authorization: Bearer ' . $auth_data[0] ;
        $header['Ocp-Apim-Subscription-Key'] = $subscriptionKey;
        // And we're done.
	$scenarios="smd";
	$appid=$auth_data[0];
	
	$url_info=$url_cognitive . "recognize?scenarios=". $scenarios . "&appid=" . $app_id_cognitive .
	"&locale=" . $locale . "&device.os=" . $deviceos . "&version=" . $version_cog . 
	"&format=" . $format_cog . "&instanceid=" . $auth_data[1] . "&requestid=" . $auth_data[1];
	
	//echo $url_info;
	$resp = executeRequest($url_info, $header, $audio);
	return $resp;
}

function processResults($m)
{
        // Grab the Response headers for display
        // In a real-world application, it's important to grab the nuance-generated session id for debug purposes
        $respHeaders = var_export($m->getHeaders(), true);
        // Grab Response Code and Status for display. Again, in a real-world app, you would use these to determine
        //      how to respond to the calling application or user. Check the technical documentation for error c ode 
	//      and error status details.
        $respCode = $m->getResponseCode();
        $respStatus = $m->getResponseStatus();
        // Results come back as a list of text strings separated by a new-line
        $respResults = nl2br( $m->getBody() );
	$status = array_values(json_decode($respResults, true))[1]["status"];
	$process_respResults = array_values(json_decode($respResults, true))[1]["name"];
        // We'll simply display the response details in an HTML table
        echo '
        <div class="element-input">
                        <div class="item-cont" align="center">
                                <table>
                                <tr><td><font size=6><b>Translation</b></font></td></tr>
                                </table>
                        </div>
        </div>
        <div class="element-input">
                        <div class="item-cont" align="center">
                                <table>
                                <tr><td>' . $process_respResults . '</td></tr>
                                </table>
                        </div>
        </div>
        ';
        // Done.
}
?>


<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Bing Speech Uploader</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="blurBg-false" style="background-color:#EBEBEB">

<!-- Display our Web Page with a Form to collect HTTP Client Parameters -->


<!-- Start Formoid form-->
<link rel="stylesheet" href="/microsoft/resources/formoid-solid-blue.css" type="text/css" />
<script type="text/javascript" src="/static/jquery.min.js"></script>
<form class="formoid-solid-blue" style="background-color:#FFFFFF;font-size:14px;font-family:'Roboto',Arial,Helvetica,sans-serif;color:#34495E;max-width:900px;min-width:150px" method="post" action="index.php" enctype="multipart/form-data">
	<div class="title">
		<h2 style="text-align: center">Bing Speech Uploader</h2>
	</div>
	<div class="element-input">
		<label class="title"></label>
		<!-- audio file -->
			<div class="item-cont" align="center">
				<input type="hidden" name="translate" value="yes">
				<table>
				<tr><td>Audio File: </td><td><input type="file" name="audioFile"></td></tr>
				<!-- language -->
				</table>

			</div>
	</div>


<div class="submit">
	<input type="submit" name="commit" value="Translate"/>
</div>

<?php
if( isset($_POST['translate']) ) 
{
	//Creating Token for Bing Speeh API
       //$data_filename = implode("<br>", $audioFile);
        echo '
        <div class="element-input">
                        <div class="item-cont" align="center">
                                <table>
                                <tr><td><font size=6><b>File Details</b></font></td></tr>
                                </table>
                        </div>
        </div>
        <div class="element-input">
                        <div class="item-cont" align="center">
                                <table>
                                <tr><td>Audio File: ' .  $audioFile['name'] . '</td></tr>
                                </table>
                        </div>
        </div>
        ';
	 $contentLength = (strlen($audioFilename) > 0) ?  $audioFile['size'] : 0;
         if( !$contentLength )
         {
            echo "<br><br>Please provide an audio file<br><br>";
         }
	 else
	 {
	 	$audio = ($contentLength > 0) ? file_get_contents($audioFile['tmp_name']) : null;
	 	$auth_data = getToken($subscriptionKey, $url_token, $audio);
		//echo '<a> Grab the token: ';
	 	//print_r($auth_data);	
	 	$resp_info = sendAudio($subscriptionKey, $url_cognitive, $auth_data, $locale, $deviceos, $version_cog, $format_cog, $app_id_cognitive, $audio);
}
		processResults($resp_info);
	 }
?>


</form>
</body>
</html>
