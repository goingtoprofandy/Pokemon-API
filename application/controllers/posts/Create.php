<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Create extends CI_Controller {

    // Public Variable
    public $custom_curl, $creator;
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
        $this->creator = new Json_creator_helper("assets/json/posts/");

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array(
            "post" => "m_posts"
        );
    }

	public function index() 
    {
        $req = $this->request->raw();
        if (!isset($req["title"]) || empty($req["title"]) ||
            !isset($req["content"]) || empty($req["content"]) ||
            !isset($req["action"]) || empty($req["action"])) {
                $this->request->res(400, null, "Parameters not correct.", null);
            }

        $file_name = md5(json_encode($req)) . ".json";
        $is_uploaded = $this->creator->save($file_name, $req);
        if ($is_uploaded) {
            $is_created = $this->customSQL->create(array(
                "uid" => md5($file_name . date("YmdHis")),
                "title" => $req["title"],
                "file_name" => $file_name,
                "is_publish" => ($req["action"] == "publish")
            ), $this->tables["post"]);

            if (!$is_created) {
                $this->creator->destroy($file_name);
                $this->request->res(500, null, "Failed to create, something went wrong.", null);
            }

            $this->request->res(200, null, "Success to create.", null);
        }
	}
}
