<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Detail extends CI_Controller {

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

        $checkIsExists = $this->customSQL->query("
            SELECT my_pokemon.* FROM my_pokemon
            WHERE my_pokemon.device_id = '".$device_id."' AND my_pokemon.pokemon_id = '".$id."'
        ")->result_array();

        $data = array();
        $status = false;

        if (count($checkIsExists) == 0) {
            $raw = $this->request->get("/$id/");
            $raw = json_decode($raw, true);
            $data["content"] = $raw;
            $data["febs"] = array();
        } else {
            $status = true;
            $checkIsExists = $checkIsExists[0];
            $fibs = $this->customSQL->query("
                SELECT * FROM my_pokemon_rename_fib
                WHERE id_my_pokemon = '".$checkIsExists['id']."'
            ")->result_array();
            $data["id"] = $checkIsExists["id"];
            $data["device_id"] = $checkIsExists["device_id"];
            $data["pokemon_id"] = $checkIsExists["pokemon_id"];
            $data["name"] = $checkIsExists["name"];
            $data["content"] = json_decode($checkIsExists['raw']);
            $data["febs"] = $fibs;
        }

        return $this->request->res(200, $data, "Berhasil memuat data pokemon",
        array(
            "is_catch" => $status
        ));
	}
}
