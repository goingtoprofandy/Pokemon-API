<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Detail extends CI_Controller {

    // Public Variable
    public $custom_curl, $creator;
    public $tables;

    public function __construct()
    {
        parent::__construct();

        // Load Model
        $this->load->model("customSQL");
        $this->load->model("request");

        // Load Helper
        $this->custom_curl = new Mycurl_helper("");
        $this->creator = new Json_creator_helper("assets/json/posts/");

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array(
            "post" => "m_posts"
        );
    }

	public function index($uid) 
    {
        $data = $this->customSQL->query("
            SELECT * FROM ".$this->tables['post']."
            WHERE uid = '$uid'
        ")->result_array();

        if (count($data) == 0)
            $this->request->res(404, null, "Data not found", null);

        $data = $data[0];

        $temp = $this->creator->read($data["file_name"]);
        $temp["meta"] = $data;

        $this->request->res(200, $temp, "Success to fetch detail.", null);
	}
}
