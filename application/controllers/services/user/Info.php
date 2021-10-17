<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Info extends CI_Controller
{
    // Public Variable
    public $session, $custom_curl;
    public $csrf_token, $auth;
    public $topBarContent, $navBarContent;

    public function __construct()
    {
        parent::__construct();

        // Load Model
        $this->load->model("tokenize");
        $this->load->model("customSQL");
        $this->load->model("request");

        // Load Helper
        $this->session = new Session_helper();
        $this->custom_curl = new Mycurl_helper("");

        // Init Request
        $this->request->init($this->custom_curl);
    }

    // Load Lists Guest
    public function index()
    {
        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

             // Load Total Perangkat
             $info = $this->customSQL->query("
                SELECT * FROM `u_user_info`
                WHERE phone_number = '".$tempUser['phone_number']."'
            ")->result_array();

            if (count($info) > 0) $info = $info[0];
            else $info = null;

            // Create Log
            $this->customSQL->log("Memuat detail pengguna", $tempUser["full_name"] . " Berhasil memuat detail pengguna");

            // Response Success
            return $this->request
            ->res(200, $info, $tempUser["full_name"] . " Berhasil memuat detail pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Invited
    public function changeInfo() 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["ssid_name"]) || empty($req["ssid_name"]) || 
        !isset($req["ssid_password"]) || empty($req["ssid_password"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $ssid_name = $req["ssid_name"];
            $ssid_password = $req["ssid_password"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Do Invited
            $data = array(
                "ssid_name" => $ssid_name,
                "ssid_password" => $ssid_password,
                "updated_at" => date("Y-m-d H:i:s")
            );

            $checkID = $this->customSQL->update(
                array("phone_number" => $tempUser["phone_number"]),
                $data,
                "u_user_info"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal melakukan perubahan info, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Melakukan perubahan info", $tempUser["full_name"] . " Berhasil melakukan perubahan info " . $tempUser["phone_number"]);

            // Response Success
            return $this->request
            ->res(200, $data, 
            $tempUser["full_name"] . " Berhasil melakukan perubahan info " . $tempUser["phone_number"],
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
