<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Guest extends CI_Controller
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

            $listGuest = $this->customSQL->query("
                SELECT `m_users`.* FROM `u_user_child`
                JOIN `m_users` ON `u_user_child`.`phone_number_child` = `m_users`.`phone_number`
                WHERE `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_child`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listGuestTotal = $this->customSQL->query("
                SELECT count(`u_user_child`.`phone_number_child`) as total FROM `u_user_child`
                JOIN `m_users` ON `u_user_child`.`phone_number_child` = `m_users`.`phone_number`
                WHERE `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_child`.`created_at` $orderDirection
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data guest", $tempUser["full_name"] . " Berhasil memuat data guest");

            // Response Success
            return $this->request
            ->res(200, $listGuest, $tempUser["full_name"] . " Berhasil memuat data guest",
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

    // Load Detail User
    public function detail()
    {
        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();
            $phone_number = $this->input->post_get("phone_number", TRUE) ?: "";

            if (!isset($phone_number) || empty($phone_number))
                return $this->request
                ->res(400, null, "Parameter tidak benar", null);

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "guest" && $tempUser["type"] != "user")
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

            if (!empty($selectedUser["id_cover"])) {
                // Load Total Perangkat
                $cover = $this->customSQL->query("
                    SELECT uri as cover FROM `m_medias`
                    WHERE id = '".$selectedUser['id_cover']."'
                ")->row()->cover;

                $selectedUser["cover"] = $cover;
            } else $selectedUser["cover"] = null;

            // Create Log
            $this->customSQL->log("Memuat detail pengguna", $tempUser["full_name"] . " Berhasil memuat detail pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil memuat detail pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Preview
    public function preview() 
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $phone_number = $req["phone_number"];

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
            $checkIsAlreadyUser2 = $this->customSQL->query("
                SELECT count(*) as total FROM `u_user_child`
                WHERE `u_user_child`.`phone_number_child` = '$phone_number' AND
                `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."'
            ")->row()->total;

            if ($checkIsAlreadyUser2 > 0)
                return $this->request
                ->res(401, null, "Akun sudah pernah di invitasi", null);

            // Create Log
            $this->customSQL->log("Melakukan preview invitasi", $tempUser["full_name"] . " Berhasil melakukan preview invitasi " . $phone_number);

            unset($checkIsAlreadyUser["password"]);

            if (!empty($checkIsAlreadyUser["id_cover"])) {
                // Load Total Perangkat
                $cover = $this->customSQL->query("
                    SELECT uri as cover FROM `m_medias`
                    WHERE id = '".$checkIsAlreadyUser['id_cover']."'
                ")->row()->cover;

                $checkIsAlreadyUser["cover"] = $cover;
            } else $checkIsAlreadyUser["cover"] = null;
            
            // Response Success
            return $this->request
            ->res(200, $checkIsAlreadyUser, 
            $tempUser["full_name"] . " Berhasil melakukan preview invitasi " . $phone_number,
            null);

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
        if (!isset($req["phone_number"]) || empty($req["phone_number"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $phone_number = $req["phone_number"];

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
                SELECT count(*) as total FROM `u_user_child`
                WHERE `u_user_child`.`phone_number_child` = '$phone_number' AND
                `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."'
            ")->row()->total;

            if ($checkIsAlreadyUser > 0)
                return $this->request
                ->res(401, null, "Akun sudah pernah di invitasi", null);

            // Do Invited
            $checkID = $this->customSQL->create(
                array(
                    "phone_number_parent" => $tempUser["phone_number"],
                    "phone_number_child" => $phone_number,
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "u_user_child"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal melakukan invitasi, terjadi kesalahan pada sisi server", null);

            // Update User To Active
            $this->customSQL->update(
                array("phone_number" => $phone_number),
                array(
                    "status" => "activate",
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_users"
            );

            // Create Log
            $this->customSQL->log("Melakukan aktifasi", $tempUser["full_name"] . " Berhasil melakukan invitasi " . $phone_number);

            // Response Success
            return $this->request
            ->res(200, array(
                "phone_number_parent" => $tempUser["phone_number"],
                "phone_number_child" => $phone_number
            ), 
            $tempUser["full_name"] . " Berhasil melakukan invitasi " . $phone_number,
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
