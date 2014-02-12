<?php

Class SessionCache {

  public static function get() {
    $filename = dirname(realpath(__FILE__)) . "/ascio-session.txt";
    $fp = fopen($filename, "r");
    $contents = fread($fp, filesize($filename));
    fclose($fp);
    if (trim($contents) == "false")
      $contents = false;
    return $contents;
  }

  public static function put($sessionId) {
    $filename = dirname(realpath(__FILE__)) . "/ascio-session.txt";
    $fp = fopen($filename, "w");
    fwrite($fp, $sessionId);
    fclose($fp);
  }

  public static function clear() {
    SessionCache::put("false");
  }

}

?>