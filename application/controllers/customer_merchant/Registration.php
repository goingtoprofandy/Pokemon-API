<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Registration extends CI_Controller {

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
            "customer_merchant"
        );
    }

	public function index() 
    {
        $req = $this->request->raw();
        if (!isset($req["cm_name"]) || empty($req["cm_name"]) ||
        !isset($req["username"]) || empty($req["username"]) ||
        !isset($req["password"]) || empty($req["password"]) ||
        !isset($req["email"]) || empty($req["email"]) ||
        !isset($req["phone"]) || empty($req["phone"])) {
            return $this->request->res(400, null, "Failed, parameter need.", null);    
        }

        $req["merchant_id"] = 3;

        $checkIfExists = $this->customSQL->query("
            SELECT COUNT(cm_id) as total FROM ".$this->tables[0]."
            WHERE username = '".$req['username']."' 
            OR email = '".$req['email']."'
            OR phone = '".$req['phone']."'
        ")->row()->total;

        if ($checkIfExists > 0)
        return $this->request->res(401, null, "Failed, account already exists.", null);

        $this->customSQL->create(
            $req,
            $this->tables[0]
        );

        $data = $this->customSQL->query("
            SELECT * FROM ".$this->tables[0]."
            WHERE username = '".$req['username']."' 
            OR email = '".$req['email']."'
            OR phone = '".$req['phone']."'
        ")->result_array()[0];

        $this->request->res(200, $data, "Success to registration.", null);
	}
}
