<?php
try {
  header("Access-Control-Allow-Origin: *");
  header("Cache-control: no-cache, must-revalidate");
  header("Content-type: application/json");

  $baseURL = "https://hg.mozilla.org/";

  $validTrees = array(
    "mozilla-central" => "mozilla-central/",
    "mozilla-aurora" => "releases/mozilla-aurora/",
    "mozilla-beta" => "releases/mozilla-beta/",
    "mozilla-release" =>  "releases/mozilla-release/",
    "comm-central" => "comm-central/",
    "comm-aurora" => "releases/comm-aurora/",
    "comm-beta" => "releases/comm-beta/",
    "comm-release" => "releases/comm-release/"
  );

  if (!(array_key_exists("tree", $_GET)) OR !(array_key_exists("cset", $_GET))) {
    exit(json_encode(array("error" => "tree or cset parameter missing")));
  }

  $tree = strtolower($_GET["tree"]);

  // Error out if it's not a tree that we think needs tracked
  if (!(array_key_exists($tree, $validTrees))) {
    exit(json_encode(array("error" => "unknown tree")));
  }

  // Error out if the cset isn't in the correct format
  $cset = strtolower($_GET["cset"]);
  if (preg_match("/^(?:(?:tip)|(?:[\da-f]{12,40}))$/", $cset) !== 1) {
    exit(json_encode(array("error" => "invalid cset")));
  }

  $fileLocation = "/browser/config/version.txt";
  if (strpos($tree, "comm") === 0) {
    $fileLocation = "/mail/config/version.txt";
  }

  // Read the version from version.txt in the relevant repo
  $versionURL = $baseURL . $validTrees[$tree] . "raw-file/" . $cset . $fileLocation;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $versionURL);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec($ch);

  if (! $response) {
    exit(json_encode(array("error" => "curl error " . curl_error($ch))));
  }

  if (preg_match("/^([1-9][\d]*)(?:\.[\d]+)?/", $response, $match) !== 1) {
    exit(json_encode(array("error" => "unable to parse cset")));
  }

  $tracking = "tracking_firefox" . $match[1];
  $status = "status_firefox" . $match[1];
  if (strpos($tree, "comm") === 0) {
    $tracking = "tracking_thunderbird" . $match[1];
    $status = "status_thunderbird" . $match[1];
  }
 
  exit(json_encode(array("tracking" => $tracking, "status" => $status)));

} catch (Exception $e) {
  exit(json_encode(array("error" => $e->getMessage())));
}
?>
