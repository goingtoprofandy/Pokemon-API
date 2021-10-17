<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Devices extends CI_Controller
{
    // Public Variable
    public $session, $custom_curl;
    public $csrf_token, $auth;
    public $topBarContent, $navBarContent;
    public $antares;

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
        $this->antares = new Antares_helper();

        // Init Request
        $this->request->init($this->custom_curl);
        $this->antares->set_key("4fb615c2a8b25c95:fd981cd566191626");
    }

    // Load Lists Devices
    public function index()
    {
        $type = $this->input->get("type") ?: "";
        $page = $this->input->get("page", TRUE) ?: 0;
        $search = $this->input->get("search", TRUE) ?: "";
        $orderDirection = $this->input->get("order-direction", TRUE) ?: "ASC";

        try {
            // Preparing Filter
            $limit = 12;
            $offset = ($page * $limit);

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listDevice = $this->customSQL->query("
                SELECT * FROM `m_devices`
                WHERE `device_type` LIKE '%".$type."%'
                ORDER BY `created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listDeviceTotal = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                ORDER BY `created_at` $orderDirection
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data perangkat", $tempUser["full_name"] . " Berhasil memuat data perangkat");

            // Response Success
            return $this->request
            ->res(200, $listDevice, $tempUser["full_name"] . " Berhasil memuat data perangkat",
            array(
                "current_page" => $page,
                "total_fetch" => count($listDevice),
                "total_data" => $listDeviceTotal,
                "search" => $search,
                "order-direction" => $orderDirection
            ));

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
        if (!isset($req["device_name"]) || empty($req["device_name"]) ||
            !isset($req["device_type"]) || empty($req["device_type"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $device_name = $req["device_name"];
            $device_type = $req["device_type"];
            $device_shortname = $device_type . date_timestamp_get(date_create());
            $device_token = md5($device_shortname);

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
                "device_name" => $device_name,
                "device_shortname" => $device_shortname,
                "device_token" => $device_token,
                "device_type" => $device_type,
                "created_at" => date("Y-m-d H:i:s"), 
                "updated_at" => date("Y-m-d H:i:s")
            );
            $checkID = $this->customSQL->create(
                $data, 
                "m_devices"
            );

            if ($checkID == -1)
                return $this->request
                ->res(500, null, "Gagal membuat perangkat, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

            // Create Log
            $this->customSQL->log("Berhasil membuat perangkat", $tempUser["full_name"] . " Berhasil membuat perangkat");

            $meta = $this->antares->deviceCreate(
                $device_shortname,
                strtoupper($device_type)
            );

            // Response Success
            return $this->request
            ->res(200, $data, $tempUser["full_name"] . " Berhasil membuat perangkat", $meta);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Load Device User & Guest
    public function userOrGuest()
    {
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";
        $page = $this->input->get("page", TRUE) ?: 0;
        $search = $this->input->get("search", TRUE) ?: "";
        $orderDirection = $this->input->get("order-direction", TRUE) ?: "ASC";

        try {
            // Preparing Filter
            $limit = 12;
            $offset = ($page * $limit);

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $selectedUser = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE `phone_number` = '$phone_number'
            ")->result_array();

            if (count($selectedUser) != 1) 
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $selectedUser = $selectedUser[0];

            if ($selectedUser["type"] != "guest" && $selectedUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            if ($selectedUser["type"] == "user") {
                $listDevice = $this->customSQL->query("
                    SELECT `m_devices`.* FROM `u_users_devices`
                    JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                    WHERE `u_users_devices`.`phone_number` = '".$phone_number."'
                    ORDER BY `u_users_devices`.`created_at` $orderDirection
                    LIMIT $limit OFFSET $offset
                ")->result_array();

                $listDeviceTotal = $this->customSQL->query("
                    SELECT count(`m_devices`.`id`) as total FROM `u_users_devices`
                    JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                    WHERE `u_users_devices`.`phone_number` = '".$phone_number."'
                    ORDER BY `u_users_devices`.`created_at` $orderDirection
                ")->row()->total;
            } else {
                $listDevice = $this->customSQL->query("
                    SELECT `m_devices`.* FROM `u_user_child_access_devices`
                    JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                    WHERE `u_user_child_access_devices`.`phone_number_child` = '$phone_number'
                    ORDER BY `u_user_child_access_devices`.`created_at` $orderDirection
                    LIMIT $limit OFFSET $offset
                ")->result_array();

                $listDeviceTotal = $this->customSQL->query("
                    SELECT count(`m_devices`.`id`) as total FROM `u_user_child_access_devices`
                    JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                    WHERE `u_user_child_access_devices`.`phone_number_child` = '$phone_number'
                    ORDER BY `u_user_child_access_devices`.`created_at` $orderDirection
                ")->row()->total;
            }

            // Create Log
            $this->customSQL->log("Memuat data perangkat", $tempUser["full_name"] . " Berhasil memuat data perangkat");

            // Response Success
            return $this->request
            ->res(200, $listDevice, $tempUser["full_name"] . " Berhasil memuat data perangkat",
            array(
                "current_page" => $page,
                "total_fetch" => count($listDevice),
                "total_data" => $listDeviceTotal,
                "search" => $search,
                "order-direction" => $orderDirection
            ));

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Load Lists Devices
    public function info()
    {
        $device_token = $this->input->get("device_token", TRUE) ?: "";

        if (empty($device_token))
        return $this->request
        ->res(401, null, "Device ID tidak ditemukan", null);

        try {

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $dataDevice = $this->customSQL->query("
                SELECT `m_devices`.*, 
                `u_user_info`.`phone_number`, `u_user_info`.`ssid_name`, `u_user_info`.`ssid_password`, 
                `u_user_info`.`api_key`,
                `m_users`.`full_name`, `m_users`.`email`, `m_users`.`email`
                FROM `m_devices`
                LEFT JOIN `u_users_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                LEFT JOIN `m_users` ON `u_users_devices`.`phone_number` = `m_users`.`phone_number`
                LEFT JOIN `u_user_info` ON `u_users_devices`.`phone_number` = `u_user_info`.`phone_number`
                WHERE `m_devices`.`device_token` = '".$device_token."'
                LIMIT 1
            ")->result_array();

            if (count($dataDevice) != 1)
            return $this->request
            ->res(500, null, "Perangkat belum diaktifasi atau tidak ditemukan", null);

            $dataDevice = $dataDevice[0];

            // Create Log
            $this->customSQL->log("Memuat data info perangkat", $device_token . " Berhasil memuat data info perangkat");

            // Response Success
            return $this->request
            ->res(200, $dataDevice, $device_token . " Berhasil memuat data info perangkat", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
