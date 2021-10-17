<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rates extends CI_Controller
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

    // Load Lists Devices
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
                SELECT `m_rates`.*, `m_rates_category`.`category` FROM `m_rates`
                JOIN `m_rates_category` ON `m_rates`.`id_m_rates_category`= `m_rates_category`.`id`
                ORDER BY `created_at` DESC
            ")->result_array();

            // Create Log
            $this->customSQL->log("Memuat data rates", $tempUser["full_name"] . " Berhasil memuat data rates");

            // Response Success
            return $this->request
            ->res(200, $listDevice, $tempUser["full_name"] . " Berhasil memuat data rates", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Create Device
    public function create()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["id_m_rates_category"]) || empty($req["id_m_rates_category"]) ||
            !isset($req["tariff_group"]) || empty($req["tariff_group"]) ||
            !isset($req["power_limit"]) || empty($req["power_limit"]) ||
            !isset($req["usage_cost"]) || empty($req["usage_cost"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $id_m_rates_category = $req["id_m_rates_category"];
            $tariff_group = $req["tariff_group"];
            $power_limit = $req["power_limit"];
            $usage_cost = $req["usage_cost"];

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
                "id_m_rates_category" => $id_m_rates_category,
                "tariff_group" => $tariff_group,
                "power_limit" => $power_limit,
                "usage_cost" => $usage_cost,
                "created_at" => date("Y-m-d H:i:s"), 
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->create(
                $data, 
                "m_rates"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal membuat rates, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil membuat rates", $tempUser["full_name"] . " Berhasil membuat rates");

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil membuat rates", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Update Device
    public function update($id)
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["id_m_rates_category"]) || empty($req["id_m_rates_category"]) ||
            !isset($req["tariff_group"]) || empty($req["tariff_group"]) ||
            !isset($req["power_limit"]) || empty($req["power_limit"]) ||
            !isset($req["usage_cost"]) || empty($req["usage_cost"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $id_m_rates_category = $req["id_m_rates_category"];
            $tariff_group = $req["tariff_group"];
            $power_limit = $req["power_limit"];
            $usage_cost = $req["usage_cost"];

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
                "id_m_rates_category" => $id_m_rates_category,
                "tariff_group" => $tariff_group,
                "power_limit" => $power_limit,
                "usage_cost" => $usage_cost,
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->update(
                array("id" => $id),
                $data, 
                "m_rates"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal mengubah rates, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil mengubah rates", $tempUser["full_name"] . " Berhasil mengubah rates");

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil mengubah rates", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Update Device
    public function remove($id)
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
            
            // Create Device
            $checkID = $this->customSQL->delete(
                array("id" => $id),
                "m_rates"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal menghapus rates, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil menghapus rates", $tempUser["full_name"] . " Berhasil menghapus rates");

            // Response Success
            return $this->request
            ->res(200, null, $tempUser["full_name"] . " Berhasil menghapus rates", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
