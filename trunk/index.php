<!DOCTYPE html>
<?php
	include "config.php";
	include "googleAnalytics.php";

	// Gets the phone user's primary language - based on the headers of the phone's browser
	// Code modified from http://www.php.net/manual/en/reserved.variables.server.php#94237
	// RFC 2616 compatible Accept Language Parser
	// http://www.ietf.org/rfc/rfc2616.txt, 14.4 Accept-Language, Page 104
	// Hypertext Transfer Protocol -- HTTP/1.1

	foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) 
	{
		$pattern = 	'/^(?P<primarytag>[a-zA-Z]{2,8})'.
						'(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
						'(?P<quantifier>\d\.\d))?$/';

		$splits = array();
		
		if (preg_match($pattern, $lang, $splits)) 
		{
			$language = $splits[primarytag];
		} 
	}
?>
<?php	
/*
	Wikipedia API Documentation at http://en.wikipedia.org/w/api.php
	http://en.wikipedia.org/w/api.php?action=query&
			prop=info|langlinks&  	//Get page info and alternate languages
			lllimit=200&				//Max number of languages to return
			llurl&						//Get the URLs of alternate languages
			titles=Rossetta_Stone&	//Title of the page
			redirects=&					//Page may redirect - so get the final page
			format=json					//Other formats are available. Leave off for human readable XML
*/
	// An .htaccess file changes example.com/Foo to example.com/?title=foo
	$request = $_GET['title'];

	// Construct the API call - this is to the *ENGLISH* Wikipedia
	$api_call = "http://en.wikipedia.org/w/api.php?action=query&prop=info|langlinks&lllimit=200&llurl&titles=$request&redirects=&format=json";

	// Use CURL to retrieve the information
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	// Set a user agent with contact information so Wikipedia Admins can see who is using the service
	curl_setopt($curl_handle, CURLOPT_USERAGENT, '');
	curl_setopt($curl_handle,CURLOPT_URL,$api_call);
	$response = curl_exec($curl_handle);
	$response_info=curl_getinfo($curl_handle);
	curl_close($curl_handle);
	
	// Decode the JSON into an array
	$results = json_decode($response,true);
?>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
		<meta name="HandheldFriendly" content="true"/>
		<link rel="stylesheet" href="style.css" type="text/css" />
		<title>QR -&gt; Wikipedia - <?php echo $response; ?></title>
	</head>
	<body>
		<h1>QR -&gt; Wikipedia! Alpha</h1>
		<?php
			if ($language)
			{
				echo "I think your phone's language is $language"; 
			}
			else
			{
				echo "I don't know what language your phone speaks.";
			}
		?>
		<div class="red">
		<?php
			// We need to find the ID of the page
			$page_id_array = $results['query']['pages'];
			$page_id = key($page_id_array);
			
			// Find out how many links were returned
			$links_array = $results['query']['pages'][$page_id]['langlinks'];
			
			// Itterate through the array
			for ($i = 0; $i <	count($links_array); $i++)
			{
				// Get the Wikipedia URL for the language
				$article_url = $results['query']['pages'][$page_id]['langlinks'][$i]['url'];
				// Quick and dirty search and replace to convert the URL into a mobile version
				$mobile_url = str_replace('.wikipedia.org', '.m.wikipedia.org', $article_url);
				// Get the language
				$article_language = $results['query']['pages'][$page_id]['langlinks'][$i]['lang'];
				// Get the title of the article in the foreign language
				$article_title = $results['query']['pages'][$page_id]['langlinks'][$i]['*'];
				
				if ($article_language == $language )
				{
					echo "THIS ONE! ";
				}

				echo 	"$i [$article_language] - " .
						"<a href='$mobile_url'>$article_title</a><br />";
			}
			// Because we requested an English page, English isn't listed as a translation. Adding it in for completeness
			echo 	"~ [en] - " .
					"<a href='http://en.m.wikipedia.org/wiki/$request'>$request</a><br />";
		?>
		</div>
		<div>
			<img src="https://chart.googleapis.com/chart?cht=qr&chld=M&chs=500x500&chl=http://QRWP.ORG/<?php echo $request; ?>" />
		</div>
		<footer>
			Site created by <a href="http://twitter.com/edent">Terence Eden</a>.
		</footer>
		<?php
			$googleAnalyticsImageUrl = googleAnalyticsGetImageUrl();
			echo '<img src="' . $googleAnalyticsImageUrl . '" />';
		?>
		</body>
</html>