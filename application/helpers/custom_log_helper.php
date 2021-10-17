<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Custom_log_helper {
    // Public variable
    public $model;

    public function __construct($model) {
        $this->model = $model;
    }

    // Return JSON Patter
    public function create($data=array()) {
        $this->model->create($data);
    }
}