<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lists extends CI_Controller {

    // Public Variable
    public $custom_curl;
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

        // Init Request
        $this->request->init($this->custom_curl);

        // Init Tables
        $this->tables = array(
            "transaction",
            "product",
            "merchant",
            "category"
        );
    }

	public function index($cm_id) 
    {
        $merchant_id = 3;

        $data = $this->customSQL->query("
            SELECT ".$this->tables[0].".*, 
            ".$this->tables[1].".product_name,
            ".$this->tables[1].".amount,
            ".$this->tables[1].".unit,
            ".$this->tables[3].".category_name,
            ".$this->tables[2].".merchant_name,
            ".$this->tables[2].".merchant_address FROM ".$this->tables[0]."
            JOIN ".$this->tables[1]." ON ".$this->tables[1].".product_id = ".$this->tables[0].".product_id
            JOIN ".$this->tables[2]." ON ".$this->tables[2].".merchant_id = ".$this->tables[0].".merchant_id
            JOIN ".$this->tables[3]." ON ".$this->tables[3].".category_id = ".$this->tables[1].".product_category
            WHERE ".$this->tables[0].".merchant_id = ".$merchant_id."
            AND ".$this->tables[0].".cm_id = ".$cm_id."
            ORDER BY ".$this->tables[0].".created_at DESC
        ")->result_array();

        $this->request->res(200, $data, "Success to fetch.", null);
	}
}
