<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Response_helper {
    public function __construct() {}

    // Return JSON Patter
    public function json($res, $code=200, $err=array(), $message="") {
        header('Content-Type: application/json');
        return json_encode(array(
            "data"  => $res,
            "code"  => $code,
            "error" => $err,
            "message" => $message
        ), JSON_PRETTY_PRINT);
    }

    // Check Is Have Value
    public function checkIsNotNull($req, $data) {
        $validity = array();
        foreach ($req as $item) {
            if (!isset($data[$item]) || empty($data[$item])) {
                $validity[] = $item . " is required";
            }
        }
        return $validity;
    }
}
