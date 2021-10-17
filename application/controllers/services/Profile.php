<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends CI_Controller
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
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            unset($tempUser["password"]);

            if (!empty($tempUser["id_cover"])) {
                // Load Total Perangkat
                $cover = $this->customSQL->query("
                    SELECT uri as cover FROM `m_medias`
                    WHERE id = '".$tempUser['id_cover']."'
                ")->row()->cover;

                $tempUser["cover"] = $cover;
            } else $tempUser["cover"] = null;

            // Create Log
            $this->customSQL->log("Memuat data profil", $tempUser["full_name"] . " Berhasil memuat data profil");

            // Response Success
            return $this->request
            ->res(200, $tempUser, $tempUser["full_name"] . " Berhasil memuat data profil", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Update Profile
    public function photo()
    {
        try {
            $tempUser = $this->customSQL->checkValid();
            $id_cover = $this->input->post_get('id_cover', TRUE) ?: "";

            if (empty($id_cover))
                return $this->request
                ->res(400, null, "Parameter tidak benar", null);

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            unset($tempUser["password"]);

            $data = array(
                "id_cover" => $id_cover,
                "updated_at" => date("Y-m-d H:i:s")
            );

            // Do Update
            $checkID = $this->customSQL->update(
                array("phone_number" => $tempUser["phone_number"]),
                $data,
                "m_users"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengubah foto profil, terjadi kesalahan pada sisi server", null);

            // Load Total Perangkat
            $cover = $this->customSQL->query("
                SELECT uri as cover FROM `m_medias`
                WHERE id = '".$id_cover."'
            ")->row()->cover;

            $tempUser["id_cover"] = $id_cover;
            $tempUser["cover"] = $cover;

            // Create Log
            $this->customSQL->log("Memuat data profil", $tempUser["full_name"] . " Berhasil memuat data profil");

            // Response Success
            return $this->request
            ->res(200, $tempUser, $tempUser["full_name"] . " Berhasil memuat data profil", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
