<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DeviceGuest extends CI_Controller
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
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";
        $page = $this->input->get("page", TRUE) ?: 0;
        $search = $this->input->get("search", TRUE) ?: "";
        $orderDirection = $this->input->get("order-direction", TRUE) ?: "ASC";

        if (empty($phone_number))
            return $this->request
                ->res(401, null, "Parameter tidak benar, cek kembali", null);

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

            // Check Valid User
            $checkIsAlreadyUser = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE `m_users`.`phone_number` = '$phone_number'
            ")->result_array();

            if (count($checkIsAlreadyUser) == 0)
                return $this->request
                ->res(401, null, "Akun belum ter-aktifasi sebelumnya atau akun tidak ditemukan", null);

            $checkIsAlreadyUser = $checkIsAlreadyUser[0];

            if ($checkIsAlreadyUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Akun tidak ditemukan", null);

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

    // Invited
    public function invited() 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) || 
            !isset($req["id_m_devices"]) || empty($req["id_m_devices"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $phone_number = $req["phone_number"];
            $id_m_devices = $req["id_m_devices"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Check Valid User
            $checkIsAlreadyUser = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE `m_users`.`phone_number` = '$phone_number'
            ")->result_array();

            if (count($checkIsAlreadyUser) == 0)
                return $this->request
                ->res(401, null, "Akun belum ter-aktifasi sebelumnya atau akun tidak ditemukan", null);

            $checkIsAlreadyUser = $checkIsAlreadyUser[0];

            if ($checkIsAlreadyUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Akun tidak ditemukan", null);

            // Check Is Already Invited
            $checkIsAlreadyUser = $this->customSQL->query("
                SELECT count(*) as total FROM `u_user_child_access_devices`
                WHERE `u_user_child_access_devices`.`phone_number_child` = '$phone_number' AND
                `u_user_child_access_devices`.`id_m_devices` = '$id_m_devices'
            ")->row()->total;

            if ($checkIsAlreadyUser > 0)
                return $this->request
                ->res(401, null, "Perangkat sudah pernah di invitasi", null);

            // Do Invited
            $checkID = $this->customSQL->create(
                array(
                    "phone_number_child" => $phone_number,
                    "id_m_devices" => $id_m_devices,
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "u_user_child_access_devices"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal melakukan invitasi, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Melakukan aktifasi", $tempUser["full_name"] . " Berhasil melakukan invitasi perangkat");

            // Response Success
            return $this->request
            ->res(200, array(
                "phone_number_child" => $phone_number,
                "id_m_devices" => $id_m_devices,
            ), 
            $tempUser["full_name"] . " Berhasil melakukan invitasi perangkat",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
