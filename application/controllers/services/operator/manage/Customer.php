<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer extends CI_Controller
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
        $this->antares = new Antares_helper();

        // Init Request
        $this->request->init($this->custom_curl);
    }

    // Load Lists Guest
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

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listGuest = $this->customSQL->query("
                SELECT `m_users`.*, 
                `u_user_request_activate`.`created_at` as `created_at_request`,
                `u_user_request_activate`.`updated_at` as `updated_at_request`,
                `u_user_request_activate`.`operator`,
                `u_user_request_activate`.`plot_by`,
                `u_user_request_activate`.`status` as `status_request`
                FROM `m_users`
                JOIN `u_user_request_activate` ON `u_user_request_activate`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'request activate'
                AND `u_user_request_activate`.`status` = 'process'
                AND `u_user_request_activate`.`operator` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_request_activate`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $tempGuest = array();
            foreach($listGuest as $item) {
                if (!empty($item["id_cover"])) {
                    // Load Total Perangkat
                    $cover = $this->customSQL->query("
                        SELECT uri as cover FROM `m_medias`
                        WHERE id = '".$item['id_cover']."'
                    ")->row()->cover;
    
                    $item["cover"] = $cover;
                } else $item["cover"] = null;
                $tempGuest[] = $item;
            }
            $listGuest = $tempGuest;

            $listGuestTotal = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                JOIN `u_user_request_activate` ON `u_user_request_activate`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'request activate'
                AND `u_user_request_activate`.`status` = 'process'
                AND `u_user_request_activate`.`operator` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_request_activate`.`created_at` $orderDirection
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data customer", $tempUser["full_name"] . " Berhasil memuat data customer");

            // Response Success
            return $this->request
            ->res(200, $listGuest, $tempUser["full_name"] . " Berhasil memuat data customer",
            array(
                "current_page" => $page,
                "total_fetch" => count($listGuest),
                "total_data" => $listGuestTotal,
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

    // Load Lists Activate
    public function my()
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

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listGuest = $this->customSQL->query("
                SELECT `m_users`.* FROM `m_users`
                JOIN `u_user_info` ON `u_user_info`.`phone_number` = `m_users`.`phone_number`
                WHERE `u_user_info`.`installed_by` = '".$tempUser['phone_number']."'
                AND `m_users`.`type` = 'user' AND `m_users`.`status` = 'activate'
                ORDER BY `m_users`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $tempGuest = array();
            foreach($listGuest as $item) {
                if (!empty($item["id_cover"])) {
                    // Load Total Perangkat
                    $cover = $this->customSQL->query("
                        SELECT uri as cover FROM `m_medias`
                        WHERE id = '".$item['id_cover']."'
                    ")->row()->cover;
    
                    $item["cover"] = $cover;
                } else $item["cover"] = null;
                $tempGuest[] = $item;
            }
            $listGuest = $tempGuest;

            $listGuestTotal = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                JOIN `u_user_info` ON `u_user_info`.`phone_number` = `m_users`.`phone_number`
                WHERE `u_user_info`.`installed_by` = '".$tempUser['phone_number']."'
                AND `m_users`.`type` = 'user' AND `m_users`.`status` = 'activate'
                ORDER BY `m_users`.`created_at` $orderDirection
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data customer", $tempUser["full_name"] . " Berhasil memuat data customer");

            // Response Success
            return $this->request
            ->res(200, $listGuest, $tempUser["full_name"] . " Berhasil memuat data customer",
            array(
                "current_page" => $page,
                "total_fetch" => count($listGuest),
                "total_data" => $listGuestTotal,
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

    // Activate Guest
    public function activate() 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) || 
            !isset($req["ssid_name"]) || empty($req["ssid_name"]) || 
            !isset($req["ssid_password"]) || empty($req["ssid_password"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $phone_number = $req["phone_number"];
            $ssid_name = $req["ssid_name"];
            $ssid_password = $req["ssid_password"];
            $api_key = md5($phone_number . date("Y-m-d H:i:s"));

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $checkIsAlready = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE `m_users`.`phone_number` = '$phone_number' AND `m_users`.`type` = 'guest'
                AND `m_users`.`status` = 'request activate'
            ")->result_array();

            if (count($checkIsAlready) == 0)
                return $this->request
                ->res(401, null, "Akun sudah ter-aktifasi sebelumnya atau akun tidak ditemukan", null);

            $checkIsAlready = $checkIsAlready[0];

            // Do Activate
            $checkID = $this->customSQL->create(
                array(
                    "phone_number" => $phone_number,
                    "ssid_name" => $ssid_name,
                    "ssid_password" => $ssid_password,
                    "api_key" => $api_key,
                    "installed_by" => $tempUser["phone_number"]
                ),
                "u_user_info"
            );

            if ($checkIsAlready["request_type"] == "building") {
                $this->customSQL->create(
                    array(
                        "phone_number" => $phone_number,
                        "group_label" => "My Device"
                    ),
                    "u_user_device_group"
                );
            }

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal melakukan aktifasi, terjadi kesalahan pada sisi server", null);

            // Update Status User
            $this->customSQL->update(
                array("phone_number" => $phone_number),
                array(
                    "type" => "user",
                    "status" => "activate"
                ),
                "m_users"
            );

            // Update Request User
            $this->customSQL->update(
                array("phone_number" => $phone_number),
                array(
                    "status" => "finish",
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "u_user_request_activate"
            );

            // Create Log
            $this->customSQL->log("Melakukan aktifasi", $tempUser["full_name"] . " Berhasil melakukan aktifasi terhadap " . $phone_number);

            // Response Success
            return $this->request
            ->res(200, array(
                "phone_number" => $phone_number,
                "ssid_name" => $ssid_name,
                "ssid_password" => $ssid_password,
                "api_key" => $api_key,
                "installed_by" => $tempUser["phone_number"]
            ), 
            $tempUser["full_name"] . " Berhasil melakukan aktifasi terhadap " . $phone_number,
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
