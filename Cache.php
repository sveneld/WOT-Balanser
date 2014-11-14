<?php
/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 21:13
 */

class Cache {

    protected static $settings = array(
        'encyclopedia/tankinfo' => 43200,
        'encyclopedia/tanks' => 43200,
        'clan/info' => 3600,
        'tanks/stats' => 3600,
        'globalwar/top' => 3600,
    );

    protected static $_instance;

    private function __construct(){}

    private function __clone(){}

    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function get($key, $unique){
        if (isset(self::$settings[$key])) {
            $fileName = 'cache/' . str_replace('/', '_', $key) . '_' . $unique;
            if (file_exists($fileName) && filemtime($fileName) + self::$settings[$key] > time()) {
                return json_decode(file_get_contents($fileName));
            } elseif (is_file($fileName)) {
                unlink($fileName);
            }
        }
        return false;
    }

    public function set($key, $unique, $data){
        if (isset(self::$settings[$key])) {
            $fileName = 'cache/' . str_replace('/', '_', $key) . '_' . $unique;
            file_put_contents($fileName, $data);
        }
    }

}
