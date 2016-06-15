<?php

	// Your webhook URL
	$webhook = '[WEBHOOK URL]';

	// Feed URL
	$feed = 'http://hn.algolia.com/api/v1/search_by_date?';

	// Specify path to  Temp data file
	$file = 'date.txt';

	// Set a last collected date
	$lastDate = file_get_contents($file);
	if(!$lastDate) {
		$lastDate = 0;
	}

	// Parameters
	$params = array(
		// Only retrieve stories (not polls or comments)
		'tags' =>  'story',
		// Get any stories created after the lastDate integer
		'numericFilters' => 'created_at_i>' . $lastDate,
		// Only return 1 item
		'hitsPerPage' => 1
	);

	// Build the full path using http_build_query
	$url = $feed . http_build_query($params);

	// Get the API data
	$data = json_decode(file_get_contents($url));

	// Check to see if there are any new stories
	if(!count($data->hits))
		exit('No stories');

	// Isolate the story
	$story = $data->hits[0];

	// Format the date - pass an @ if using timestamp
	$date = new DateTime('@' . $story->created_at_i);

	// Encode the data
	$payload = 'payload=' . json_encode(array(
		// Username and nice icon
		'username' => 'Hacker News',
		'icon_emoji' => ':fax:',

		// Required fallback and some pretext
		'pretext' => 'A new story from Hacker News',
		'fallback' => 'New hack news story - ' . $story->url,

		// Title as a link, date and author of the news story
		'fields' => array(
			array('title' => 'Title', 'value' => '<' . $story->url . '|' . $story->title . '>'),
			array('title' => 'Date', 'value' => $date->format('jS F Y g:ia'), 'short' => true),
			array('title' => 'Author', 'value' => $story->author, 'short' => true),
		)
	));

	// PHP cURL POST request
	$ch = curl_init($webhook);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);

	file_put_contents($file, $story->created_at_i);

	echo $result;