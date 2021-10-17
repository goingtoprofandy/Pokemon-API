<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CatchIt extends CI_Controller {

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

        if (rand(1, 10) <= 5) 
            return $this->request->res(200, null, "Gagal menangkap pokemon, coba lagi",
            null);

        $checkIsExists = $this->customSQL->query("
            SELECT COUNT(id) as total FROM my_pokemon
            WHERE device_id = '".$device_id."' AND pokemon_id = '".$id."'
        ")->row()->total;

        if ($checkIsExists == 1) 
            return $this->request->res(500, null, "Gagal, pokemon sudah ditangkap sebelumnya",
            null);

        return $this->request->res(200, null, "Berhasil menangkap pokemon",
        array(
            "is_catch" => true
        ));
	}

    public function save() {
        $id = $this->input->get("id") ?: 1;
        $device_id = $this->input->get("device_id") ?: "";
        $name = $this->input->get("name") ?: "";

        if (!isset($device_id) || empty($device_id) ||
        !isset($name) || empty($name))
            return $this->request->res(400, null, "Parameter tidak benar",
            null);

        $checkIsExists = $this->customSQL->query("
            SELECT COUNT(id) as total FROM my_pokemon
            WHERE device_id = '".$device_id."' AND pokemon_id = '".$id."'
        ")->row()->total;

        if ($checkIsExists == 1) 
            return $this->request->res(500, null, "Gagal, pokemon sudah ada sebelumnya",
            null);

        $raw = $this->request->get("/$id/");
        $raw = json_decode($raw, true);

        $newID = $this->customSQL->create(
            array(
                "device_id" => $device_id,
                "pokemon_id" => $id,
                "name" => $name,
                "raw" => json_encode($raw),
                "at" => date("Y-m-d H:i:s")
            ),
            "my_pokemon"
        );

        $this->customSQL->create(
            array(
                "id_my_pokemon" => $newID,
                "fib" => 0
            ),
            "my_pokemon_rename_fib"
        );

        return $this->request->res(200, null, "Berhasil menyimpan pokemon",
        array(
            "is_catch" => true
        ));
    }

    public function rename() {
        $id = $this->input->get("id") ?: 1;
        $device_id = $this->input->get("device_id") ?: "";

        if (!isset($device_id) || empty($device_id))
            return $this->request->res(400, null, "Parameter tidak benar",
            null);

        $checkIsExists = $this->customSQL->query("
            SELECT my_pokemon.* FROM my_pokemon
            WHERE my_pokemon.device_id = '".$device_id."' AND my_pokemon.pokemon_id = '".$id."'
        ")->result_array();

        if (count($checkIsExists) != 1) 
            return $this->request->res(500, null, "Gagal, pokemon tidak ditemukan",
            null);

        $checkIsExists = $checkIsExists[0];
        $fibs = $this->customSQL->query("
            SELECT * FROM my_pokemon_rename_fib
            WHERE id_my_pokemon = '".$checkIsExists['id']."'
        ")->result_array();

        $newFeb = 0;
        if (count($fibs) == 1) $newFeb = 1;
        else {
            $tempFeb1 = (int) $fibs[count($fibs) - 2]["fib"];
            $tempFeb2 = (int) $fibs[count($fibs) - 1]["fib"];
            $newFeb = ($tempFeb1 + $tempFeb2);
        }

        $this->customSQL->create(
            array(
                "id_my_pokemon" => $checkIsExists['id'],
                "fib" => $newFeb
            ),
            "my_pokemon_rename_fib"
        );

        return $this->request->res(200, null, "Berhasil mengubah nama pokemon", null);
    }
}
