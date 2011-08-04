<?
  $geostock_api_token = 'YOUR API TOKEN HERE';
  $geostock_api_secret = 'YOUR API SECRET HERE';

  function geostockUriBase($api_token) {
    $geostock_uri_base = 'http://api.geostock.jp';
	return $geostock_uri_base . '/' . $api_token;
  }

  function geostockGet($api_token, $cmd, $params) {
	$uri = geostockUriBase($api_token);
	return geostockGetFrom($uri . '/' . $cmd, $params);
  }
  function geostockGetFrom($uri, $params) {
	$q = geostockParamsToQuery($params);
	return geostockHttpGet($uri . $q);
  }
  function geostockHttpGet($uri) {
	$response = file_get_contents($uri);
	list($version,$status_code,$msg) = explode(' ',$http_response_header[0], 3);
	$j = json_decode($response, true);
	return array(
	  'code' => $status_code,
	  'result' => $j
	);
  }

  function geostockPost($api_token, $api_secret_token, $cmd, $params) {
	$uri = geostockUriBase($api_token);
	$params = array(
	  'signed_request' => geostockSignedRequest($params, $api_secret_token)
	);
	return geostockPostTo($uri . '/' . $cmd, $params);
  }

  function geostockPostTo($uri, $params) {
	$context = build_basic_post_context($params);
    $response = file_get_contents($uri, false, $context);
	list($version,$status_code,$msg) = explode(' ',$http_response_header[0], 3);
    $j = json_decode($response, true);
    return array(
      'code' => $status_code,
      'result' => $j
    );
  }

  function build_basic_post_context($params) {
	$request = array('http' =>
	    array(
          'method' => 'POST',
          'header' => 'Content-Type: application/x-www-form-urlencoded',
          'content' => http_build_query($params)
	    )
	);
	return stream_context_create($request);
  }

  // TODO: $key and $value should be cgi escaped.
  function geostockParamsToQuery($params) {
	if (is_null($params)) { return ""; }
	$q = "";
	foreach ($params as $key => $val) {
		if ($q == "") {
			$q = '?' . $key . '=' . $val;
		} else {
			$q = $q . '&' . $key . '=' . $val;
		}
	}
	return $q;
  }

  function geostockSignedRequest($value, $api_secret_token) {
    $text = geostockB64Encode(json_encode($value));
    $time10 = intval(time() / (60*10)) . '';
    $source = (string)($text . $time10);
    $digest = hash_hmac('sha256', $source, $api_secret_token, false);
    return $digest . '.' . $text;
  }

  function geostockB64Encode($text) {
    $text = base64_encode($text);
    $text = str_replace('+', '-', $text);
    $text = str_replace('/', '_', $text);
    $text = str_replace("\n", '', $text);
    return $text;
  }

  function geostockB64Decode($text) {
    $text = base64_decode($text);
    $text = str_replace('-', '+', $text);
    $text = str_replace('_', '/', $text);
    $text = str_replace("\n", '', $text);
    return $text;
  }

  ///////////// public functions
  function geostockGetCollections($api_token) {
	return geostockGet($api_token, 'collections', null);
  }
  function geostockUpdateCollection($api_token, $api_secret_token, $collection_name) {
	return geostockPost($api_token, $api_secret_token, 'collections/update', array($collection_name));
  }
  function geostockDeleteCollection($api_token, $api_secret_token, $collection_name) {
	return geostockPost($api_token, $api_secret_token, 'collections/delete', array($collection_name));
  }
  function geostockUpdatePoi($api_token, $api_secret_token, $poi) {
	return geostockPost($api_token, $api_secret_token, 'pois/update', $poi);
  }
  function geostockDeletePoi($api_token, $api_secret_token, $poi) {
	return geostockPost($api_token, $api_secret_token, 'pois/delete', $poi);
  }

  function echo_dump($label, $var) {
	echo $label . ":";
	var_dump($var);
  }
?>
<?
$resp = geostockGetCollections($geostock_api_token);
echo_dump('get-collections:resp', $resp);
$resp = geostockUpdateCollection($geostock_api_token, $geostock_api_secret,
                                 'test-a');
echo_dump('update-cllection:resp', $resp);
$resp = geostockUpdateCollection($geostock_api_token, $geostock_api_secret,
                                 'test-b');
echo_dump('update-cllection:resp', $resp);
$resp = geostockDeleteCollection($geostock_api_token, $geostock_api_secret,
                                 'test-a');
echo_dump('delete-cllection:resp', $resp);
$resp = geostockGetCollections($geostock_api_token);
echo_dump('get-collections:resp', $resp);

$resp = geostockUpdatePoi($geostock_api_token, $geostock_api_secret, array(
  'test-b' => array(
    array(
      'uid' => '100', 'll' => '38.12345,139.22222', 'attrs' => array(
        'name' => 'メロン', 'url' => 'http://www.yahoo.co.jp/', 'desc' => 'Even when their top of life.'
      )
    ),
    array(
      'uid' => '101', 'll' => '38.12345,139.22222', 'attrs' => array(
        'name' => 'Pan', 'url' => 'http://www.yahoo.co.jp/', 'desc' => 'Even when their top of life.'
      )
    )
  )
));
echo_dump('update-poi:resp', $resp);
$resp = geostockDeletePoi($geostock_api_token, $geostock_api_secret, array(
  'test-b' => array('100')
));
echo_dump('delete-poi:resp', $resp);
$resp = geostockDeletePoi($geostock_api_token, $geostock_api_secret, array(
  'test-b' => '101'
));
echo_dump('delete-poi:resp', $resp);
?>
