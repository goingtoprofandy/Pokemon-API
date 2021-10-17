<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Medias extends CI_Controller
{
    // Public Variable
    public $session, $custom_curl;
    public $csrf_token, $auth;
    public $topBarContent, $navBarContent;
    public $fileUpload;

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
        $this->fileUpload = new Upload_file_helper(
            array(
                "file_type" => array(
                    "png",
                    "jpg",
                    "jpeg",
                    "webp"
                ),
                "max_size"  => 200000000
            )
        );

        // Init Request
        $this->request->init($this->custom_curl);
    }

    // New
    public function create()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (!isset($_FILES["photo"]["name"])) 
                return $this->request
                ->res(400, null, "Parameter tidak benar", null);

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];
            $photo = $this->fileUpload->do_upload("photo");

            if (!$photo["status"])
                return $this->request
                ->res(500, null, "Gagal mengunggah gambar", null);

            // Upload File
            $data = array(
                "uri" => base_url("assets/dist/img/") . $photo["file_name"],
                "file_name" => $photo["file_name"],
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->create(
                $data,
                "m_medias"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengunggah foto, terjadi kesalahan pada sisi server", null);

            $data["id"] = $checkID;

            // Create Log
            $this->customSQL->log("Mengunggah foto", $tempUser["full_name"] . " Berhasil mengunggah foto");

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil mengunggah foto", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
