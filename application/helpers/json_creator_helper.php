<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Json_creator_helper {
    protected $file_dir;

    public function __construct($file_dir="") {
        $this->file_dir = FCPATH . isset($file_dir) ? $file_dir : "assets/json/";
    }

    public function save($file_name="", $data=[]) {
        if (count($data) > 0) {
            $fp = fopen($this->file_dir . $file_name, "w");
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
            fclose($fp);
            return true;
        }
        return false;
    }

    public function destroy($file_name="") {
        if (file_exists($this->file_dir . $file_name)) {
            unlink($this->file_dir . $file_name);
            return true;
        }
        return false;
    }

    public function read($file_name="") {
        if (file_exists($this->file_dir . $file_name)) {
            $temp = file_get_contents($this->file_dir . $file_name);
            return json_decode($temp, true);
        }
        return array();
    }

    public function update($file_name="", $data=[]) {
        if (file_exists($this->file_dir . $file_name)) {
            file_put_contents($this->file_dir . $file_name, json_encode($data));
            return true;
        }
        return false;
    }
}
