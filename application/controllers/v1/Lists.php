<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lists extends CI_Controller {

    // Public Variable
    public $custom_curl;
    public $tables;

    public function __construct()
    {
        parent::__construct();
		
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

        // Load Model
        $this->load->model("customSQL");
        $this->load->model("request");

        // Load Helper
        $this->custom_curl = new Mycurl_helper("");

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array();
    }

	public function index()
    {
        $page = $this->input->get("page") ?: 0;
        $offset = ($page * 100) + 1;
        $limit = 100;

        $this->request->get("?limit=$limit&offset=$offset");
	}
}
