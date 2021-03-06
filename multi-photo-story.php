<?php
session_start();
require_once __DIR__ . '/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => 'APP_ID',
  'app_secret' => 'APP_SECRET',
  'default_graph_version' => 'v2.6'
  ]);

$helper = $fb->getRedirectLoginHelper();

// app directory could be anything but website URL must match the URL given in the developers.facebook.com/apps
define('APP_URL', 'http://WEBSITE_URL.com/fbapp/');

$permissions = ['publish_actions', 'user_photos']; // optional

try {
	if (isset($_SESSION['fb_token'])) {
		$accessToken = $_SESSION['fb_token'];
	} else {
  		$accessToken = $helper->getAccessToken();
	}
} catch(Facebook\Exceptions\FacebookResponseException $e) {
 	// When Graph returns an error
 	echo 'Graph returned an error: ' . $e->getMessage();

  	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
 	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
  	exit;
 }

if (isset($accessToken)) {
	if (isset($_SESSION['fb_token'])) {
		$fb->setDefaultAccessToken($_SESSION['fb_token']);
	} else {
		// getting short-lived access token
		$_SESSION['fb_token'] = (string) $accessToken;

	  	// OAuth 2.0 client handler
		$oAuth2Client = $fb->getOAuth2Client();

		// Exchanges a short-lived access token for a long-lived one
		$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['fb_token']);

		$_SESSION['fb_token'] = (string) $longLivedAccessToken;

		// setting default access token to be used in script
		$fb->setDefaultAccessToken($_SESSION['fb_token']);
	}

	// redirect the user back to the same page if it has "code" GET variable
	if (isset($_GET['code'])) {
		header('Location: ./');
	}

	// validating user access token
	try {
		$user = $fb->get('/me');
		$user = $user->getGraphNode()->asArray();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		session_destroy();
		// if access token is invalid or expired you can simply redirect to login page using header() function
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	// post multi-photo story
	$uploadPhoto1 = $fb->post('/me/photos', ['published' => 'false', 'url' => 'https://pbs.twimg.com/profile_images/766914502919086080/lchcXIiJ_400x400.jpg']);
	$uploadPhoto2 = $fb->post('/me/photos', ['published' => 'false', 'url' => 'https://pbs.twimg.com/profile_images/648888480974508032/66_cUYfj_400x400.jpg']);
	$uploadPhoto3 = $fb->post('/me/photos', ['published' => 'false', 'url' => 'https://pbs.twimg.com/profile_images/775931351480401921/qAGIo8Kt_400x400.jpg']);

	$uploadPhoto1 = $uploadPhoto1->getGraphNode()->asArray();
	$uploadPhoto2 = $uploadPhoto2->getGraphNode()->asArray();
	$uploadPhoto3 = $uploadPhoto3->getGraphNode()->asArray();

	$photo1 = $uploadPhoto1['id'];
	$photo2 = $uploadPhoto2['id'];
	$photo3 = $uploadPhoto3['id'];

	$multiPhotoPost = $fb->post('/me/feed', [
		'attached_media[0]' => '{"media_fbid":"'.$photo1.'"}',
		'attached_media[1]' => '{"media_fbid":"'.$photo2.'"}',
		'attached_media[2]' => '{"media_fbid":"'.$photo3.'"}',
		'message' => 'just today story'
	]);

	// Now you can redirect to another page and use the access token from $_SESSION['fb_token']
} else {
	// replace your website URL same as added in the developers.facebook.com/apps e.g. if you used http instead of https and you used non-www version or www version of your website then you must add the same here
	$loginUrl = $helper->getLoginUrl(APP_URL, $permissions);
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
}
