<?php
namespace Mifiel;

use GuzzleHttp\Psr7\Request;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\RequestSigner;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Mifiel\Digest\ApiAuthGemDigest;

class ApiClient {

  private static $appId;
  private static $appSecret;
  private static $client;
  private static $url;

  public static function setTokens($appId, $appSecret) {
    self::$appId = $appId;
    self::$appSecret = $appSecret;
    self::$url = 'http://genaro-book.local:3000/api/v1/';
    self::setClient();
  }

  public static function get($path, $params=array()) {
    return self::request('GET', $path, $params);
  }

  public static function post($path, $params=array(), $multipart=false) {
    return self::request('POST', $path, $params, $multipart);
  }

  public static function delete($path) {
    return self::request('DELETE', $path, $params);
  }

  public static function put($path, $params=array(), $multipart=false) {
    return self::request('PUT', $path, $params, $multipart);
  }

  private static function request($type, $path, $params, $multipart=false) {
    if ($multipart) {
      $options = ['multipart' => self::build_multipart($params)];
    } else {
      $options = ['json' => $params];
    }

    return self::$client->request(strtoupper($type), $path, $options);
  }

  public static function build_multipart($params) {
    $multipart_arr = array();
    foreach ($params as $key => $value) {
      if (is_array($value) && $value['filename']) {
        $field = [
          'name'      => $key,
          'contents'  => $value['contents'],
          'filename'  => $value['filename']
        ];
      } else {
        $field = [ 'name' => $key, 'contents' => $value ];
      }
      array_push($multipart_arr, $field);
    }
    return $multipart_arr;
  }

  public static function url(){
    return self::$url;
  }

  public static function appId($appId=null) {
    if ($appId) {
      self::$appId = $appId;
      setClient();
      return;
    }
    return self::$appId;
  }

  public static function appSecret($appSecret=null) {
    if ($appSecret) {
      self::$appSecret = $appSecret;
      setClient();
      return;
    }
    return self::$appSecret;
  }

  public static function context(){
    return self::$client;
  }

  private static function setClient() {
    $signer = new RequestSigner(new ApiAuthGemDigest());
    $signer->setProvider('APIAuth');

    $middleware = new HmacAuthMiddleware(
      $signer,
      self::$appId,
      self::$appSecret
    );

    $stack = HandlerStack::create();
    $stack->push($middleware);

    self::$client = new Client([
      'base_uri' => self::$url,
      'handler' => $stack,
    ]);
  }
}
