<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

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
        if (!isset($req["user"]) || empty($req["user"]) ||
        !isset($req["password"]) || empty($req["password"])) {
            return $this->request->res(400, null, "Failed, parameter need.", null);    
        }

        $checkIfExists = $this->customSQL->query("
            SELECT * FROM ".$this->tables[0]."
            WHERE (username = '".$req['user']."' 
            OR email = '".$req['user']."'
            OR phone = '".$req['user']."')
            AND password = '".$req['password']."'
        ")->result_array();

        if (count($checkIfExists) == 1)
        return $this->request->res(200, $checkIfExists[0], "Success to login.", null);

        $this->request->res(404, null, "Failed, account not found.", null);
	}
}
