<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
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
    
    // Do Sign In
    public function signIn()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) || 
            !isset($req["password"]) || empty($req["password"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $phone_number = $req["phone_number"];
            $password = md5($req["phone_number"] . $req["password"]);

            $getTempUser = $this->customSQL->query("
                SELECT `phone_number`
                FROM `m_users`
                WHERE `phone_number` = '$phone_number' OR `username` = '$phone_number'
            ")->result_array();

            if (count($getTempUser) != 1) 
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);

            $getTempUser = $getTempUser[0];
            $password = md5($getTempUser["phone_number"] . $req["password"]);

            $tempUser = $this->customSQL->query("
                SELECT `full_name`, `type`, `status`, `token`
                FROM `m_users`
                WHERE (`phone_number` = '$phone_number' OR `username` = '$phone_number') AND `password` = '$password'
            ")->result_array();

            if (count($tempUser) == 1) {
                $tempUser = $tempUser[0];
                // Create Log
                $this->customSQL->log("Berhasil Login", $tempUser["full_name"] . " Berhasil melakukan otentikasi");

                // Response Success
                return $this->request
                ->res(200, $tempUser, $tempUser["full_name"] . " Berhasil melakukan otentikasi", null);
            }

            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Do Sign Up
    public function signUp()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) ||
            !isset($req["password"]) || empty($req["password"]) || 
            !isset($req["email"]) || empty($req["email"]) || 
            !isset($req["full_name"]) || empty($req["full_name"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $phone_number = $req["phone_number"];
            $password = md5($req["phone_number"] . $req["password"]);
            $full_name = $req["full_name"];
            $email = $req["email"];
            $token = md5($req["phone_number"] . $req["password"] . $req["email"]);

            $tempUser = $this->customSQL->query("
                SELECT `full_name`, `type`, `status`, `token`
                FROM `m_users`
                WHERE `phone_number` = '$phone_number'
            ")->result_array();

            if (count($tempUser) == 0) {   
                // Create Account
                $checkID = $this->customSQL->create(
                    array(
                        "phone_number" => $phone_number,
                        "full_name" => $full_name,
                        "email" => $email,
                        "password" => $password,
                        "token" => $token,
                        "created_at" => date("Y-m-d H:i:s"), 
                        "updated_at" => date("Y-m-d H:i:s")
                    ), 
                    "m_users"
                );

                if ($checkID == -1)
                    return $this->request
                    ->res(500, null, "Gagal Registrasi, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

                // Create Log
                $this->customSQL->log("Berhasil Registrasi", $full_name . " Berhasil melakukan otentikasi");

                // Response Success
                return $this->request
                ->res(200, array(
                    "full_name" => $full_name,
                    "token" => $token,
                    "type" => "guest",
                    "status" => "not activate yet"
                ), $full_name . " Berhasil melakukan otentikasi", null);
            }

            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun sudah terdaftar sebelumnya", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
