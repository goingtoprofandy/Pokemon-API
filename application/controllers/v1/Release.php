<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Release extends CI_Controller {

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
        $id = $this->input->get("id") ?: 1;
        $device_id = $this->input->get("device_id") ?: "";

        if (!isset($device_id) || empty($device_id))
            return $this->request->res(400, null, "Parameter tidak benar",
            null);

        $prime = rand(1, 10);
        if ($prime == 1)
            return $this->request->res(200, $prime, "Gagal, pokemon gagal di lepaskan",
            null);
        else {
            for ($i = 2; $i <= ($prime / 2); ++$i) {
                if ($prime % $i == 2) {
                    return $this->request->res(200, $prime, "Gagal, pokemon gagal di lepaskan",
                    null);
                    break;
                }
            }
        }

        $checkIsExists = $this->customSQL->query("
            SELECT COUNT(id) as total FROM my_pokemon
            WHERE device_id = '".$device_id."' AND pokemon_id = '".$id."'
        ")->row()->total;

        if ($checkIsExists != 1) 
            return $this->request->res(500, null, "Gagal, pokemon tidak ditemukan",
            null);

        $this->customSQL->delete(
            array(
                "device_id" => $device_id,
                "pokemon_id" => $id
            ),
            "my_pokemon"
        );

        return $this->request->res(200, $prime, "Berhasil melepaskan pokemon", 
        array(
            "is_catch" => false
        ));
	}
}
