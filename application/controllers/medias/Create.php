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
        $this->creator = new Upload_file_helper(
            array(
                "file_type" => array(
                    "png",
                    "jpg",
                    "jpeg",
                    "webp"
                ),
                "max_size"  => 200000000
            ), "assets/img/");

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array(
            "media" => "m_medias"
        );
    }

	public function index() 
    {
        if (!isset($_FILES["photo"]["name"])) 
        $this->request->res(400, null, "Parameters not correct.", null);

        $photo = $this->creator->do_upload("photo");

        if (!$photo["status"])
            return $this->request
            ->res(500, null, "Failed to upload image.", null);

        // Upload File
        $data = array(
            "uid" => md5($photo["file_name"] . date("YmdHis")),
            "url" => base_url("assets/img/") . $photo["file_name"],
            "file_name" => $photo["file_name"],
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        );

        $is_created = $this->customSQL->create($data, $this->tables["media"]);

        if (!$is_created) {
            $this->creator->destroy($photo["file_name"]);
            $this->request->res(500, null, "Failed to create, something went wrong.", null);
        }

        $this->request->res(200, $data, "Success to create.", null);
	}
}
