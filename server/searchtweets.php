<?php
header("Content-Type: application/json; charset=utf-8");
require "vendor/autoload.php";
require "secret/credential.php";
use Abraham\TwitterOAuth\TwitterOAuth;
$access_token = $_GET["access_token"];
$access_token_secret = $_GET["access_token_secret"];
$q = urldecode($_GET["q"]);
$max_id = $_GET["max_id"];
try {
  if (empty($access_token)||empty($access_token_secret)||empty($q)) {
    throw new Exception("required parameter missing");
  }
  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);
  $req_body = [
    "q" => $q,
    "result_type" => "recent",
    "count" => 100,
    "include_entities" => true,
  ];
  // もし$max_idがあったらそれもreq_bodyに入れる
  if (!empty($max_id)) {
    $req_body["max_id"] = $max_id;
  }
  $search_tweets_res_obj = $connection->get("search/tweets", $req_body);
  if (!empty($search_tweets_res_obj->errors)) {
    // エラーだったら例外を投げる
    $error_code = $search_tweets_res_obj->errors[0]->code;
    $error_message = $search_tweets_res_obj->errors[0]->message;
    throw new Exception($error_message);
  }
  $res = $search_tweets_res_obj->statuses;
  // favoritedが常にfalseを返す問題に対処するために、statuses/showからfavoritedを拾ってきて無理やり書き換える。
  /*
  foreach ($res as $index=>$r) {
    $req_body = [
      "id" => $r->id_str
    ];
    $statuses_show_res_obj = $connection->get("statuses/show", $req_body);
    if ($statuses_show_res_obj->favorited) {
      $res[$index]->favorited = true;
    }
  }
  */
  // ドキュメントをよく読んだら一括取得できるstatuses/lookupというのがあったのでそっちを使うようにした。
  $ids = "";
  foreach ($res as $r) {
    $ids .= $r->id_str.",";
  }
  $req_body = [
    "id" => $ids
  ];
  $statuses_lookup_res_obj = $connection->get("statuses/lookup", $req_body);
  // key=>id_str, val=>favoritedの配列をつくる
  $favorited_array = array();
  foreach ($statuses_lookup_res_obj as $r) {
    $favorited_array[$r->id_str] = $r->favorited;
  }
  // 書き換える
  foreach ($res as $index=>$r) {
    if ($favorited_array[$r->id_str]) {
      $res[$index]->favorited = true;
    }
  }
  //
  echo json_encode(array(
    "max_id" => $res[count($res)-1]->id,
    "tweets" => $res
  ));
} catch (Exception $e) {
  echo json_encode(array(
    "error" => $e->getMessage()
  ));
}