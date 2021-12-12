<?php
header("Content-Type: application/json; charset=utf-8");
require "vendor/autoload.php";
require "secret/credential.php";
use Abraham\TwitterOAuth\TwitterOAuth;
$access_token = $_GET["access_token"];
$access_token_secret = $_GET["access_token_secret"];
$tweet_id = $_GET["tweetid"];
try {
  if (empty($access_token)||empty($access_token_secret)||empty($tweet_id)) {
    throw new Exception("required parameter missing");
  }
  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);
  $req_body = [
    "id" => $tweet_id,
    "include_entities" => true,
  ];
  $favorites_create_res_obj = $connection->post("favorites/create", $req_body);
  if (!empty($favorites_create_res_obj->errors)) {
    // エラーだったら例外を投げる
    $error_code = $favorites_create_res_obj->errors[0]->code;
    $error_message = $favorites_create_res_obj->errors[0]->message;
    throw new Exception($error_message);
  }
  echo json_encode(array(
    "res" => $favorites_create_res_obj
  ));
} catch (Exception $e) {
  echo json_encode(array(
    "error" => $e->getMessage()
  ));
}