<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lists extends CI_Controller {

    // Public Variable
    public $custom_curl;
    public $tables;

    public function __construct()
    {
        parent::__construct();

        // Load Model
        $this->load->model("customSQL");
        $this->load->model("request");

        // Load Helper
        $this->custom_curl = new Mycurl_helper("");

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array(
            "media" => "m_medias"
        );
    }

	public function index() 
    {
        $page = $this->input->get("page") ?: "0";
        $limit = $this->input->get("limit") ?: "8";
        $search = $this->input->get("search") ?: "";
        
        $page = (int) $page;
        $limit = (int) $limit;
        $offset = ($page * $limit);

        $data = $this->customSQL->query("
            SELECT * FROM ".$this->tables['media']."
            WHERE file_name LIKE '%$search%' OR url LIKE '%$search%'
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ")->result_array();

        $total = $this->customSQL->query("
            SELECT COUNT(uid) as total FROM ".$this->tables['media']."
            WHERE file_name LIKE '%$search%' OR url LIKE '%$search%'
            ORDER BY created_at DESC
        ")->row()->total;

        $this->request->res(200, $data, "Success to fetch.", array(
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
