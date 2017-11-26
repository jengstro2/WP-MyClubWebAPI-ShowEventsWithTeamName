<?php
// MyClub GetEvents shortcode
// shortcode [get_myclub_events_via_groupname url="https://<club>.myclub.fi/api/events/?group_id=" name="<Group/Team name>" amount="8"]
// Place Clubs Api Key into file: myclubapi.key (Api key can be asked and generated from http://myclub.fi support)
// NOTE! as the key can be used to edit/create API services, make sure that your web server configured to prevent showing *.key file content!
// To check is your key file publicly visible, try browsing directly it: www.yourwebsite.fi/wp-content/plugins/myclub-show-events-plugin/myclubapi.key
function GetMyClubEventsByName($atts)
{
	//MyClubAPI events url
	extract(shortcode_atts(array
		('url' => '',
		 'name' => '',
		 'amount' => '',
		 ),$atts));
	return getMyClubApiEventsViaGroupName($atts['url'],$atts['name'],$atts['amount']);
}
add_shortcode( 'get_myclub_events_via_groupname', 'GetMyClubEventsByName' );
	
// Weekday localization, not used
function dateElementFromEnglish($locale,$text,$strfTimeformat) {
	$saveLocale = setlocale(LC_TIME,0);setlocale(LC_TIME,$locale);
	$translation = strftime($strfTimeformat,strtotime($text));
	setlocale(LC_TIME,$saveLocale);
	return $translation;
}
// Weekday localization, not used
function weekdayFromEnglish($locale,$weekday,$short=false) {
	return ucfirst(dateElementFromEnglish($locale, $weekday, $short?"%a":"%A"));
}

// For some server? localization is not working/installed... do it hard way
function localizeWeekDay($str)
{
	switch ($str)
	{
		case "Mon": 
			return "MA";
		case "Tue": 
			return "TU";
		case "Wed": 
			return "KE";
		case "Thu": 
			return "TO";
		case "Fri": 
			return "PE";
		case "Sat": 
			return "LA";
		case "Sun": 
			return "SU";
		default:
			return $str;
	}
}

// Lame error check helper function
function CheckError($str)
{
	if (strpos($str, 'myclubapi-error') !== false) 
	{
		return true;
	}
	return false;
}

// Converts: 2017-11-25T18:15:00.000+02:00 -> Maanantaina 25.04 18:15
function TidyTimeStamp($str)
{
	$date=strtok($str, "T");
	$time=strtok("T");
	$finalDate=date_format(date_create($date),"d.m");
	$finalTime=substr($time,0,5);
	//$weekday=weekdayFromEnglish("fi_FI",date_format(date_create($date),"D"));
	$weekday=localizeWeekDay(date_format(date_create($date),"D"));
	return $weekday ." ".  $finalDate ." ". $finalTime;
}

// Converts: 2017-11-25T18:15:00.000+02:00 -> 18:15
function TidyTimeStampJustTime($str)
{
	$date=strtok($str, "T");
	$time=strtok("T");
	$finalTime=substr($time,0,5);
	return $finalTime;
}

// Create file to this php's path where MyClub API key is inside "myclubapi.key" file
function GetMyClubApiKeyHeader()
{
	$returnString="";
	// Get secret myclub token from separate file to access your club's API services
	$myClubApiKey=file_get_contents(plugin_dir_path( __FILE__ ) . "myclubapi.key");
	if (strlen($myClubApiKey)<10)
	{
		$returnString= "<div class='myclubapi-error'>";
		$returnString=$returnString . "Error: api key file not found or content is invalid</div>";
		return $returnString;
	}
	$myClubApiKey="X-myClub-token: " . $myClubApiKey;
	return $myClubApiKey;
}

// Get groupid for the events api call
function GetGroupIdByName($name,$url)
{
	$returnString="";
	$json=GetMyClubApiJson($url);
	if (CheckError($json))
	{
		return $json;
	}
	$decoded = json_decode($json);
	foreach ($decoded as $item)
	{
		if (strpos($item->group->name, $name) !== false)
		{
			return $item->group->id;	
		}
	}
	$returnString= "<div class='myclubapi-error'>";
	$returnString=$returnString . "Error: could not find groupId for '" .$name. "' </div>";
	return $returnString;
}

// Fetch myclubapi webapi json with given url
function GetMyClubApiJson($url)
{
	$returnString="";
	
	$apiKey=GetMyClubApiKeyHeader();
	if (CheckError($json))
	{
		return $json;
	}

	$headers = [
	  $apiKey,
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$serverOutput = curl_exec ($ch);
	curl_close ($ch);
	// Did anything come back
	if (strlen($serverOutput) < 20)
	{
		$returnString= "<div class='myclubapi-error'>";
		$returnString=$returnString . "MyClubApi did not respond to request: " .$url. " </div>";
		return $returnString;
	}
	return $serverOutput;
}

// Use css nth-child to color 
function getMyClubApiEventsViaGroupName($url, $groupName,$amountOfEvents)
{
	$returnString="";
	
	//form get group url from original event request url "https://<club>.myclub.fi/api/groups"
	$pu = parse_url($url);
    	$baseurl=$pu["scheme"] . "://" . $pu["host"];
	$groupurl=$baseurl . "/api/groups";
	
	// Get groupid with other api call
	$groupId=GetGroupIdByName($groupName,$groupurl);	
	
	if (CheckError($groupId))
	{
		return $groupId;
	}
	// Append group id to api call
	$url = $url . $groupId;
	
	// Get Json with given url, append start_date=YYYY-MM-DD (otherwise api will show second event from yesterday?!)
	$url=$url . "&start_date=" . date("Y-m-d");
	$json=GetMyClubApiJson($url);
	
	if (CheckError($json))
	{
		return $json;
	}
	$decoded = json_decode($json);

	$returnString = $returnString . "<div class='myclubapi-events'>";

	$i=0;
	foreach ($decoded as $item)
	{
		$returnString = $returnString . "<div id='myclubapi-event-single'>";
		$returnString = $returnString . "<p id='myclubapi-event-time'>" . TidyTimeStamp($item->event->starts_at). "-" .TidyTimeStampJustTime($item->event->ends_at) ."</p>" ;
		$returnString = $returnString . "<p id='myclubapi-event-name'>" .$item->event->name. "</p>" ;
		$returnString = $returnString . "</div>";
		if ($i>=$amountOfEvents)
			break;
		$i++;
	}
	
	$returnString = $returnString . "</div>";
	return $returnString;
}
?>