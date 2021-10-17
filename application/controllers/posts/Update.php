<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Update extends CI_Controller {

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
        $req = $this->request->raw();
        if (!isset($req["title"]) || empty($req["title"]) ||
            !isset($req["content"]) || empty($req["content"]) ||
            !isset($req["action"]) || empty($req["action"])) {
                $this->request->res(400, null, "Parameters not correct.", null);
            }

        $data = $this->customSQL->query("
            SELECT file_name FROM ".$this->tables['post']."
            WHERE uid = '$uid'
        ")->result_array();

        if (count($data) == 0)
            $this->request->res(404, null, "Data not found.", null);

        $data = $data[0];
        
        $is_upload = $this->creator->update($data["file_name"], $req);
        if ($is_upload) {
            $is_update = $this->customSQL->update(
                array("uid" => $uid),
                array(
                    "title" => $req["title"],
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                $this->tables['post']
            );

            if (!$is_update)
                $this->request->res(500, null, "Failed to update, something went wrong.", null);

            $this->request->res(200, null, "Success to update.", null);
        }
        
        $this->request->res(500, null, "Failed to update.", null);
	}
}
