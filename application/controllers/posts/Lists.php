<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lists extends CI_Controller {

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

	public function index() 
    {
        $page = $this->input->get("page") ?: "0";
        $limit = $this->input->get("limit") ?: "5";
        $search = $this->input->get("search") ?: "";
        
        $page = (int) $page;
        $limit = (int) $limit;
        $offset = ($page * $limit);

        $data = $this->customSQL->query("
            SELECT * FROM ".$this->tables['post']."
            WHERE title LIKE '%$search%'
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ")->result_array();

        $total = $this->customSQL->query("
            SELECT COUNT(uid) as total FROM ".$this->tables['post']."
            WHERE title LIKE '%$search%'
            ORDER BY created_at DESC
        ")->row()->total;

        $temps = array();
        foreach ($data as $item) {
            $temp = $this->creator->read($item["file_name"]);
            unset($temp["content"]);
            $temp["meta"] = $item;
            $temps[] = $temp;
        }

        $this->request->res(200, $temps, "Success to fetch.", array(
            "page" => $page,
            "limit" => $limit,
            "fetch" => array(
                "current" => count($data),
                "total" => $total
            ),
            "search" => $search
        ));
	}
}
