<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order extends CI_Controller {

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
            "category",
            "mutasi_transaction"
        );
    }

	public function index($cm_id) 
    {
        $req = $this->request->raw();

        if (!isset($req["product_id"]) || empty($req["product_id"]) ||
        !isset($req["desc"]) || empty($req["desc"])) {
            return $this->request->res(400, null, "Failed, parameter need.", null);    
        }

        $req["transaction_no"] = md5(base64_encode(date("YmdHis") . "|" . $req["product_id"] . "|" . $cm_id));

        $product = $this->customSQL->query("
            SELECT * FROM ".$this->tables[1]."
            WHERE product_id = ".$req["product_id"]."
        ")->result_array();

        if (count($product) > 0) $product = $product[0];
        else 
        return $this->request->res(400, null, "Failed, product not found.", null);    

        $req["amount"] = $product["amount"];
        $req["status"] = 1;
        $req["merchant_id"] = $product["merchant_id"];
        $req["cm_id"] = $cm_id;
        $req["date"] = date("Y-m-d");

        $this->customSQL->create($req, $this->tables[0]);

        $mutasiTRX = array();
        $mutasiTRX["no_trx"] = $req["transaction_no"];
        $mutasiTRX["amount"] = 4000;
        $mutasiTRX["description"] = $req["desc"];
        $mutasiTRX["merchant_id"] = $product["merchant_id"];
        $mutasiTRX["debit"] = 4000;
        $mutasiTRX["credit"] = 0;

        $this->customSQL->create($mutasiTRX, $this->tables[4]);

        $merchant = $this->customSQL->query("
            SELECT * FROM `merchant`
            WHERE merchant_id = ".$product['merchant_id']."
        ")->result_array()[0];

        $updateMerchant = array();
        $updateMerchant["updated_at"] = date("Y-m-d H:i:s");
        $updateMerchant["amount"] = ($merchant["amount"] - 4000);
        $this->customSQL->update(
            array("merchant_id" => $product["merchant_id"]),
            $updateMerchant,
            $this->tables[2]
        );

        $this->request->res(200, null, "Success to order.", null);
	}
}
