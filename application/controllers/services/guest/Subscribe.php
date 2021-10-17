<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscribe extends CI_Controller
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

    // For All Users
    public function index() 
    {
        $request_type = $this->input->get("request_type", TRUE) ?: "home";
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            if ($tempUser["status"] != "not activate yet")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $checkID = $this->customSQL->create(
                array(
                    "phone_number" => $tempUser["phone_number"],
                    "status" => "new"
                ),
                "u_user_request_activate"
            );

            $checkID = $this->customSQL->update(
                array("phone_number" => $tempUser["phone_number"]),
                array(
                    "status" => "request activate",
                    "request_type" => $request_type,
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_users"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal melakukan permintaan berlangganan", null);

            // Create Log
            $this->customSQL->log("Meminta berlangganan", $tempUser["full_name"] . " Berhasil meminta berlangganan");

            // Response Success
            return $this->request
            ->res(200, $tempUser, $tempUser["full_name"] . " Berhasil meminta berlangganan", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    public function getStatus()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            if ($tempUser["status"] != "request activate")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $statusSubscribe = $this->customSQL->get(
                "*",
                array(
                    "phone_number" => $tempUser["phone_number"]
                ),
                "u_user_request_activate"
            )->result_array();

            if (count($statusSubscribe) > 0) $statusSubscribe = $statusSubscribe[0];
            else 
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Create Log
            $this->customSQL->log("Memuat status berlangganan", $tempUser["full_name"] . " Berhasil memuat status berlangganan");

            // Response Success
            return $this->request
            ->res(200, $statusSubscribe, $tempUser["full_name"] . " Berhasil memuat status berlangganan", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
