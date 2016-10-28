<?php
$defaultAppId = "NMDPTRIAL_me_shamir_tv20161017034452";
$defaultAppKey = "fa473c0a3f2435b813f095d72b0d2499474ede134ab060ca3f43a878bc163bb65d77c09c9bf555aee6ea8611f7aaab3ff989524339b20d78f6b5f0b2cb2c6828";
$defaultDeviceId = "0000";
$defaultLanguage = "en_us";
$defaultCodec = "audio/x-wav;codec=pcm;bit=16;rate=8000";
$defaultLanguageModel = "Dictation";
$defaultResultsFormat = "text";

$audioFile = array();
$audioFilename = "";

require_once "./data/asrLanguages.php";	// Supported Languages available with this interface
require_once "./data/codecs.php";		// Supported Codecs for the submitted audio

/*
 * If this script has been POSTED to, use the submitted values.
 * Otherwise, use the default values set above.
 */

$appId = isset($_POST['appId']) ? trim($_POST['appId']) : $defaultAppId;
$appKey = isset($_POST['appKey']) ? trim($_POST['appKey']) : $defaultAppKey;
$deviceId = isset($_POST['deviceId']) ? trim($_POST['deviceId']) : $defaultDeviceId;
$language = isset($_POST['language']) ? trim($_POST['language']) : $defaultLanguage;
$codec = isset($_POST['codec']) ? trim($_POST['codec']) : $defaultCodec;
$languageModel = isset($_POST['languageModel']) ? trim($_POST['languageModel']) : $defaultLanguageModel;
$resultsFormat = isset($_POST['resultsFormat']) ? trim($_POST['resultsFormat']) : $defaultResultsFormat;
$audioFile = isset($_FILES['audioFile']) ? $_FILES['audioFile'] : array();
$audioFilename = isset($audioFile['name']) ? trim($audioFile['name']) : "";


/*
 * Create some global dropdown lists that will be used on the HTML page
 */
$languages_dropdown = "";
$codecs_dropdown = "";


/*
 * This function simply helps build the HTTP request with the required parameters. We need the following:
 *	1. appId
 *	2. appKey
 *	3. id
 *
 * Note the URI to be used in your application
 * Note the appKey must be a 128 Byte String without spaces or special characters
 *
 *	If your query fails, please be sure to review carefully what you are passing in for these
 *	name/value pairs. Misspelled names, and improper values are a VERY common mistake.
 */
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
 * This is a simpler helper function to setup the Header parameters
 *
 * If providing a complete audio file, set Content-Length in the header.
 * Otherwise, if providing an unknown sized audio stream, set Transfer-Encoding to 'chunked'
 *
 * All header values are required.
 */
function buildHttpHeader($codec, $resultsFormat, $language, $languageModel, $contentLength)
{

	$header = array();

	// Set one header option, but not both...
	if( !$contentLength || $contentLength === 0 )
		$header['Transfer-Encoding'] = "chunked";
	else
		$header['Content-Length'] = $contentLength;

	$header['Content-Type'] = $codec;
	$header['Content-Language'] = $language;
	$header['Accept-Language'] = $language;
	$header['Accept'] = ($resultsFormat == 'xml') ? 'application/xml' : 'text/plain';
	$header['Accept-Topic'] = $languageModel;	// Dictation or WebSearch

	// And we're done.
	return $header;
}

/*
 * This function executes the HTTP Request
 *
 * The HTTP response will be returned to the calling routine
 *
 */
function executeAsrRequest($url, $header, $audio)
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

/*
 * This function processes the results of a single ASR request
 *
 * The goal here is to display the response details so you can get
 * a feel for what to expect when things are working correctly and
 * when they're not...
 *
 */
function processResults($m)
{
	// Grab the Response headers for display
	// In a real-world application, it's important to grab the nuance-generated session id for debug purposes
	$respHeaders = var_export($m->getHeaders(), true);

	// Grab Response Code and Status for display. Again, in a real-world app, you would use these to determine
	//	how to respond to the calling application or user. Check the technical documentation for error code
	//	and error status details.
	$respCode = $m->getResponseCode();
	$respStatus = $m->getResponseStatus();

	// Results come back as a list of text strings separated by a new-line
	$respResults = nl2br( $m->getBody() );

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
                                <tr><td>' . $respResults . '</td></tr>
                                </table>

                        </div>
        </div>
	';

	// Done.
}

/*
 * This function is a utility to help create the dropdown lists for the HTML page
 * We need 2 lists:
 *	1. The list of languages available to choose from. These must be provided in xx_XX format.
 *	2. The list of supported codecs. Specify the format of the audio being submitted.
 */
function createDropDowns()
{
	global $asrLanguages;
	global $codecs;
	global $languages_dropdown;
	global $codecs_dropdown;
	global $language;
	global $codec;

	// For each dropdown list loop through the available set of languages and set the options.
	//	And make sure the one selected by the user (if this is a POST) is pre-selected again on re-display of the page.
	foreach($asrLanguages as $v)
	{
		/*
		 * v[0]: Language Name
		 * v[1]: Language Code (this is the value that actually gets passed to the interface)
		 */

		// Display languages as "Language Name - Language Code" (ie: "US English - en_US")
		$selected = ($language == $v[1]) ? "selected" : "";
		$languages_dropdown .= '<option '. $selected .' value="'. $v[1] .'">'. $v[0] .' - '. $v[1] .'</option>';
	}

	// Do the same for the codecs...
	foreach($codecs as $c)
	{
		/*
		 * c[0]: Codec value to use for a GET request
		 * c[1]: Codec value to use for a POST request
		 * c[2]: File extension to use
		 */

		if( $c[0] == 'wav') continue;	// this codec is not supported with ASR

		// Display codecs as "GET value - File extension" (ie: "pcm_16bit_16k - pcm")
		$selected = ($codec == $c[1]) ? "selected" : "";
		$codecs_dropdown .= '<option '. $selected .' value="'. $c[1] .'">'. $c[0] .' - '. $c[2] .'</option>';
	}
}

// Before displaying the HTML below, create the drowpdown lists...
createDropDowns();
?>



<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Nuance Uploader</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="blurBg-false" style="background-color:#EBEBEB">

<!-- Display our Web Page with a Form to collect HTTP Client Parameters -->


<!-- Start Formoid form-->
<link rel="stylesheet" href="/nuance/resources/formoid-solid-blue.css" type="text/css" />
<script type="text/javascript" src="/static/jquery.min.js"></script>
<form class="formoid-solid-blue" style="background-color:#FFFFFF;font-size:14px;font-family:'Roboto',Arial,Helvetica,sans-serif;color:#34495E;max-width:900px;min-width:150px" method="post" action="nuance_upload.php" enctype="multipart/form-data">
	<div class="title">
		<h2 style="text-align: center">Nuance Uploader</h2>
	</div>
	<div class="element-input">
		<label class="title"></label>
		<!-- audio file -->
			<div class="item-cont" align="center">
				<input type="hidden" name="translate" value="yes">
				<table>
				<tr><td>Audio File: </td><td><input type="file" name="audioFile"></td></tr>
				<!-- language -->
				<tr><td colspan="2"><font color="green"><br>Select the Language and Codec.</font></td></tr>
				<tr><td>Language:&nbsp;</td><td><select name="language" style="width:220;"><?php echo $languages_dropdown ?></select></td></tr>
				<!-- codec -->
				<tr><td>Codec:&nbsp;</td><td>
				<select name="codec" style="width:220;"><?php echo $codecs_dropdown?></select></td></tr>


				</table>

			</div>
	</div>


<?php

//if( isset($_POST['appKey']) ) {
if( isset($_POST['translate']) ) {

	// Let's display what's been provided for Form data so we can see what will be passed
	//	to the HTTP client interface
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
                                <tr><td>Language: ' . $language . '</td></tr>
                                <tr><td>Codec: ' . $codec . '</td></tr>
                                <tr><td>Audio File: ' .  $audioFile['name'] . '</td></tr>
                                </table>

                        </div>
        </div>
	';
		// If an audio file was submitted, get it's size so we can set the Content-Length header property
		$contentLength = (strlen($audioFilename) > 0) ?  $audioFile['size'] : 0;
		if( !$contentLength )
		{
			echo "<br><br>Please provide an audio file<br><br>";
		}
		else
		{
			// Get the audio data contained in the uploaded file
			$audio = ($contentLength > 0) ? file_get_contents($audioFile['tmp_name']) : null;

			// Build our HTTP Request
			$url = buildRequestString($appId, $appKey, $deviceId);
			$header = buildHttpHeader($codec, $resultsFormat, $language, $languageModel, $contentLength);

			// Submit our Dictation or WebSearch Request
			$m = executeAsrRequest($url, $header, $audio);

			// For the purposes of this sample/demo, we're just dumping the response to the web page.
			//	In practice, you'll be parsing the Response Code, Response Status, Session Id, and message body (dictation text results)
			//	to determine your application's specific business logic
			processResults($m);
			//echo "<br><br>Response: " . var_export($m, true);
		}
	echo "</fieldset>";
}
?>
<div class="submit">
	<input type="submit" name="commit" value="Translate"/>
</div>

</form>
</body>
</html>
