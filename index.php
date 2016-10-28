<?php

$subscriptionKey = 'febcde8f1653414fa734b4ccb0f65de1';
$url_token = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';

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
function executeRequest($url, $header)
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



function getToken($subscriptionKey, $url_token)
{
	//Setting Headers
        $header = array();
        $header['Content-Type'] = 'application/x-www-form-urlencoded';
        $header['Content-Length'] = '0';
        $header['Ocp-Apim-Subscription-Key'] = $subscriptionKey;
        // And we're done.

	$resp = executeRequest($url_token, $header);
	$parsed_body = http_parse_message($resp);
	$token = $parsed_body->body;
	//echo '<a> This is the token: ' . $token . '</a>'; 
        return $token;
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
	 $token = getToken($subscriptionKey, $url_token);
	 echo '<a> Grab the token: ' . $token;	

}

?>


</form>
</body>
</html>
