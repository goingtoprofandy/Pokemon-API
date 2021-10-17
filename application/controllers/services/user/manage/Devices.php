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
        $this->antares->set_key("c01538e56fc59f94:eff9cd5d2fee545c");

        // Init Request
        $this->request->init($this->custom_curl);
    }

    // Load Lists Devices
    public function index()
    {
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

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_users_devices`
                JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                ORDER BY `u_users_devices`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listDeviceTotal = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `u_users_devices`
                JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                ORDER BY `u_users_devices`.`created_at` $orderDirection
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

    // Update Device Name
    public function update($id_m_devices) 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["device_name"]) || empty($req["device_name"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $device_name = $req["device_name"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Check Is Already Invited
            $checkIsAlreadyUser = $this->customSQL->query("
                SELECT count(*) as total FROM `u_users_devices`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser["phone_number"]."' AND
                `u_users_devices`.`id_m_devices` = '$id_m_devices'
            ")->row()->total;

            if ($checkIsAlreadyUser == 0)
                return $this->request
                ->res(401, null, "Perangkat tidak ditemukan", null);

            // Do Update
            $checkID = $this->customSQL->update(
                array("id" => $id_m_devices),
                array(
                    "device_name" => $device_name,
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_devices"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengubah perangkat, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Mengubah perangkat", $tempUser["full_name"] . " Berhasil mengubah perangkat");

            // Response Success
            return $this->request
            ->res(200, array(
                "device_name" => $device_name,
                "id_m_devices" => $id_m_devices,
            ), 
            $tempUser["full_name"] . " Berhasil mengubah perangkat",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Update Device SSID AND PASSWORD
    public function updateSSID($id_m_devices) 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["ssid_name_device"]) || empty($req["ssid_name_device"]) ||
        !isset($req["ssid_password_device"]) || empty($req["ssid_password_device"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $ssid_name_device = $req["ssid_name_device"];
            $ssid_password_device = $req["ssid_password_device"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Check Is Already Invited
            $checkIsAlreadyUser = $this->customSQL->query("
                SELECT count(*) as total FROM `u_users_devices`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser["phone_number"]."' AND
                `u_users_devices`.`id_m_devices` = '$id_m_devices'
            ")->row()->total;

            if ($checkIsAlreadyUser == 0)
                return $this->request
                ->res(401, null, "Perangkat tidak ditemukan", null);

            // Do Update
            $checkID = $this->customSQL->update(
                array("id" => $id_m_devices),
                array(
                    "ssid_name_device" => $ssid_name_device,
                    "ssid_password_device" => $ssid_password_device,
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_devices"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengubah perangkat, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Mengubah perangkat", $tempUser["full_name"] . " Berhasil mengubah perangkat");

            // Response Success
            return $this->request
            ->res(200, array(
                "ssid_name_device" => $ssid_name_device,
                "ssid_password_device" => $ssid_password_device,
                "updated_at" => date("Y-m-d H:i:s")
            ), 
            $tempUser["full_name"] . " Berhasil mengubah perangkat",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Activate Device
    public function activate() 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["device_name"]) || empty($req["device_name"]) || 
            !isset($req["device_token"]) || empty($req["device_token"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $device_name = $req["device_name"];
            $device_token = $req["device_token"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user" && $tempUser["status"] != "activate")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Check Valid Decive
            $checkIsAlreadyDevice = $this->customSQL->query("
                SELECT * FROM `m_devices`
                WHERE `m_devices`.`device_token` = '$device_token'
            ")->result_array();

            if (count($checkIsAlreadyDevice) == 0)
                return $this->request
                ->res(401, null, "Perangkat tidak ditemukan", null);

            $checkIsAlreadyDevice = $checkIsAlreadyDevice[0];

            if ($checkIsAlreadyDevice["device_status"] == "active")
                return $this->request
                ->res(401, null, "Perangkat sudah di aktifasi sebelumnya", null);

            if (!empty($tempUser["request_type"]) && 
                ($checkIsAlreadyDevice["device_type"] == "kwh-1-phase" || 
                $checkIsAlreadyDevice["device_type"] == "kwh-3-phase")) {
                if ($tempUser["request_type"] == "home") {
                    // Get All Device Users
                    $getAllDeviceUser = $this->customSQL->query(
                        "SELECT `m_devices`.* FROM `u_users_devices`
                        JOIN `m_devices` ON `m_devices`.`id` = `u_users_devices`.`id_m_devices`
                        WHERE `u_users_devices`.`phone_number` = '".$tempUser["phone_number"]."'"
                    )->result_array();

                    if (count($getAllDeviceUser) > 0) {
                        $status = 0;
                        foreach ($getAllDeviceUser as $item) {
                            if ($item["device_type"] == "kwh-1-phase") $status += 1;
                            else if ($item["device_type"] == "kwh-3-phase") $status += 1;
                        }

                        if ($status > 0) 
                        return $this->request
                        ->res(500, null, "Maaf jenis subscribe anda home edition, sehingga tidak memungkinkan untuk menambah kwh meter lebih dari 1.
                        Silahkan ajukan permintaan untuk mengubah jenis subscribe anda", null);
                    }
                }
            }

            // Do Activate
            $checkID = $this->customSQL->create(
                array(
                    "phone_number" => $tempUser["phone_number"],
                    "id_m_devices" => $checkIsAlreadyDevice["id"],
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "u_users_devices"
            );

            if (!empty($tempUser["request_type"])) {
                if ($tempUser["request_type"] == "building") {
                    // Get Group
                    $group = $this->customSQL->get("id", array("phone_number" => $tempUser["phone_number"]),
                    "u_user_device_group")->row()->id;

                    $this->customSQL->create(
                        array(
                            "id_group" => $group,
                            "id_m_devices" => $checkIsAlreadyDevice["id"]
                        ),
                        "u_user_device_group_item"
                    );
                }
            }

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal melakukan aktifasi, terjadi kesalahan pada sisi server", null);

            // Update Status User
            $this->customSQL->update(
                array("device_token" => $device_token),
                array(
                    "device_name" => $device_name,
                    "device_status" => "active", 
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_devices"
            );

            if ($checkIsAlreadyDevice["device_type"] == "slca" || $checkIsAlreadyDevice["device_type"] == "pcb") {
                $createDeviceAntares = $this->antares->deviceCreate($checkIsAlreadyDevice["device_shortname"], "EnergyPowerMonitor");
                $this->customSQL->log("Membuat Devices", json_encode($createDeviceAntares));
            }

            // Create Log
            $this->customSQL->log("Melakukan aktifasi", $tempUser["full_name"] . " Berhasil melakukan aktifasi perangkat " . $device_name);

            // Response Success
            return $this->request
            ->res(200, array(
                "phone_number" => $tempUser["phone_number"],
                "device_name" => $device_name,
                "device_token" => $device_token
            ), 
            $tempUser["full_name"] . " Berhasil melakukan aktifasi perangkat " . $device_name,
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
