<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My extends CI_Controller {

    // Public Variable
    public $custom_curl;
    public $tables, $request;

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
        $device_id = $this->input->get("device_id") ?: "";
        $offset = ($page * 100);
        $limit = 100;

        if (!isset($device_id) || empty($device_id))
            return $this->request->res(400, null, "Parameter tidak benar",
            null);

        $results = $this->customSQL->query("
            SELECT id, device_id, pokemon_id, name FROM my_pokemon
            WHERE device_id = '".$device_id."'
            LIMIT $limit OFFSET $offset
        ")->result_array();

        $total = $this->customSQL->query("
            SELECT COUNT(id) as total FROM my_pokemon
            WHERE device_id = '".$device_id."'
            LIMIT $limit OFFSET $offset
        ")->row()->total;

        return $this->request->res(200, $results, "Berhasil memuat data pokemon", array(
            "page" => $page,
            "next_page" => ($page + 1),
            "total_fetch" => count($results),
            "limit" => $limit,
            "total_page" => (int)($total / $limit) + 1
        ));
	}
}
