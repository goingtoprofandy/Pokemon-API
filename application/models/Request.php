<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Request extends CI_Model {
    private $custom_curl;

    public function init($custom_curl) {
        $this->custom_curl = $custom_curl;
    }

    public function raw() {
        return json_decode($this->input->raw_input_stream, true);
    }

    public function res($code, $data, $message, $meta) {
        $temp = array(
            "code" => $code,
            "message" => $message
        );

        if (isset($data) || !empty($data)) $temp["data"] = $data;
        if (isset($meta) || !empty($meta)) $temp["meta"] = $meta;

        $this->output->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($temp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();

        die();
    }

    // Set Header
    public function header($header) {
        $this->custom_curl->setHeader($header);
    }

    // Post
    public function post($data, $path) {
        $this->custom_curl->setPost($data);
        $this->custom_curl->createCurl(API_URI . $path);

        die($this->custom_curl->__tostring());
    }

    // Put
    public function put($data, $path) {
        $this->custom_curl->setPut($data);
        $this->custom_curl->createCurl(API_URI . $path);

        die($this->custom_curl->__tostring());
    }

    // Get
    public function get($path) {
        $this->custom_curl->createCurl(API_URI . $path);

        return $this->custom_curl->__tostring();
    }

    // Get
    public function getCustom($path) {

        $ch = curl_init ($path);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $raw=curl_exec($ch);
        curl_close ($ch);

        header('Content-type: image/png');
        $image = base64_encode($raw);
        die($raw);
    }

    // Delete
    public function delete($path) {
        $this->custom_curl->setDelete();
        $this->custom_curl->createCurl(API_URI . $path);

        die($this->custom_curl->__tostring());
    }
}