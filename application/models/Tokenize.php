<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tokenize extends CI_Model {
    // Generate
    public function generate() {
        $date = date("YmdHis");
        return base64_encode($date . md5($date));
    }

    // Check Have Token
    public function isValid() {
        $token = $this->input->post_get("tokenize", TRUE) ?: "";
        return !empty($token);
    }
}