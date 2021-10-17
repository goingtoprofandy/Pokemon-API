<?php
defined('BASEPATH') or exit('No direct script access allowed');

class RatesCategory extends CI_Controller
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

    // Load Lists Rates
    public function index()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listDevice = $this->customSQL->query("
                SELECT * FROM `m_rates_category`
                ORDER BY `created_at` DESC
            ")->result_array();

            // Create Log
            $this->customSQL->log("Memuat data list category rates", $tempUser["full_name"] . " Berhasil memuat category rates");

            // Response Success
            return $this->request
            ->res(200, $listDevice, $tempUser["full_name"] . " Berhasil memuat category rates", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Create Rates
    public function create()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["category"]) || empty($req["category"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $category = $req["category"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            // Create Device
            $data = array(
                "category" => $category,
                "created_at" => date("Y-m-d H:i:s"), 
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->create(
                $data, 
                "m_rates_category"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal membuat kategori biaya tenaga listrik, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil membuat kategori", $tempUser["full_name"] . " Berhasil membuat kategori biaya tenaga listrik");

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil membuat kategori biaya tenaga listrik", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Ubdate Rates
    public function update($id)
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["category"]) || empty($req["category"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $category = $req["category"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            // Create Device
            $data = array(
                "category" => $category,
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->update(
                array("id" => $id),
                $data, 
                "m_rates_category"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal mengubah kategori biaya tenaga listrik, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil mengubah kategori", $tempUser["full_name"] . " Berhasil mengubah kategori biaya tenaga listrik");

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil mengubah kategori biaya tenaga listrik", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Hapus Rates
    public function remove($id)
    {
        try {
            // Check Valid Data
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            // Create Device
            $checkID = $this->customSQL->delete(
                array("id" => $id),
                "m_rates_category"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal menghapus kategori biaya tenaga listrik, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil menghapus kategori", $tempUser["full_name"] . " Berhasil mengubah menghapus biaya tenaga listrik");

            // Response Success
            return $this->request
            ->res(200, null, $tempUser["full_name"] . " Berhasil menghapus kategori biaya tenaga listrik", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }
}
