<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Meta extends CI_Controller
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

    // Load Total Data
    public function index() 
    {
        
    }

    // For Admin/CS/Operator
    public function totalData()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Load Total Perangkat
            $listTotalPerangkat = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
            ")->row()->total;
            // Load Total Perangkat Bulan Ini
            $listTotalPerangkatMonth = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`created_at` LIKE '".date("Y-m")."%'
            ")->row()->total;
            // Load Total Perangkat Hari Ini
            $listTotalPerangkatDay = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`created_at` LIKE '".date("Y-m-d")."%'
            ")->row()->total;

            // Load Total User
            $listTotalUser = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'guest' OR `m_users`.`type` = 'user'
            ")->row()->total;
            // Load Total User Bulan Ini
            $listTotalUserMonth = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`created_at` LIKE '".date("Y-m")."%' AND
                (`m_users`.`type` = 'guest' OR `m_users`.`type` = 'user')
            ")->row()->total;
            // Load Total User Hari Ini
            $listTotalUserDay = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`created_at` LIKE '".date("Y-m-d")."%' AND
                (`m_users`.`type` = 'guest' OR `m_users`.`type` = 'user')
            ")->row()->total;

            // Load Total Tiket
            $listTotalTicket = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'new'
            ")->row()->total;
            // Load Total Tiket Bulan Ini
            $listTotalTicketMonth = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`created_at` LIKE '".date("Y-m")."%' AND
                `m_ticketing`.`status` = 'new'
            ")->row()->total;
            // Load Total Tiket Hari Ini
            $listTotalTicketDay = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`created_at` LIKE '".date("Y-m-d")."%' AND
                `m_ticketing`.`status` = 'new'
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data meta", $tempUser["full_name"] . " Berhasil memuat data meta");

            // Response Success
            return $this->request
            ->res(200, array(
                "devices" => array(
                    "total" => $listTotalPerangkat,
                    "month" => $listTotalPerangkatMonth,
                    "day" => $listTotalPerangkatDay
                ),
                "users" => array(
                    "total" => $listTotalUser,
                    "month" => $listTotalUserMonth,
                    "day" => $listTotalUserDay
                ),
                "ticketing" => array(
                    "total" => $listTotalTicket,
                    "month" => $listTotalTicketMonth,
                    "day" => $listTotalTicketDay
                )
            ), $tempUser["full_name"] . " Berhasil memuat data meta", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For User
    public function totalDataForUser()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] != "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Load Total Perangkat
            $listTotalPerangkat = $this->customSQL->query("
                SELECT count(`u_users_devices`.phone_number) as total FROM `u_users_devices`
                WHERE `phone_number` = '".$tempUser['phone_number']."'
            ")->row()->total;
            // Load Total Perangkat Bulan Ini
            $listTotalPerangkatMonth = $this->customSQL->query("
                SELECT count(`u_users_devices`.phone_number) as total FROM `u_users_devices`
                WHERE `phone_number` = '".$tempUser['phone_number']."' AND
                `created_at` LIKE '".date("Y-m")."%'
            ")->row()->total;
            // Load Total Perangkat Hari Ini
            $listTotalPerangkatDay = $this->customSQL->query("
                SELECT count(`u_users_devices`.phone_number) as total FROM `u_users_devices`
                WHERE `phone_number` = '".$tempUser['phone_number']."' AND
                `created_at` LIKE '".date("Y-m-d")."%'
            ")->row()->total;

            // Load Total User
            $listTotalUser = $this->customSQL->query("
                SELECT count(`u_user_child`.`phone_number_parent`) as total FROM `u_user_child`
                WHERE `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."'
            ")->row()->total;
            // Load Total User Bulan Ini
            $listTotalUserMonth = $this->customSQL->query("
                SELECT count(`u_user_child`.`phone_number_parent`) as total FROM `u_user_child`
                WHERE `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."' AND
                `created_at` LIKE '".date("Y-m")."%'
            ")->row()->total;
            // Load Total User Hari Ini
            $listTotalUserDay = $this->customSQL->query("
                SELECT count(`u_user_child`.`phone_number_parent`) as total FROM `u_user_child`
                WHERE `u_user_child`.`phone_number_parent` = '".$tempUser['phone_number']."' AND
                `created_at` LIKE '".date("Y-m-d")."%'
            ")->row()->total;

            // Load Total Tiket
            $listTotalTicket = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'new' AND
                `m_ticketing`.`phone_number` = '".$tempUser['phone_number']."'
            ")->row()->total;
            // Load Total Tiket Bulan Ini
            $listTotalTicketMonth = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'new' AND
                `m_ticketing`.`phone_number` = '".$tempUser['phone_number']."' AND
                `m_ticketing`.`created_at` LIKE '".date("Y-m")."%'
            ")->row()->total;
            // Load Total Tiket Hari Ini
            $listTotalTicketDay = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'new' AND
                `m_ticketing`.`phone_number` = '".$tempUser['phone_number']."' AND
                `m_ticketing`.`created_at` LIKE '".date("Y-m-d")."%'
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data meta", $tempUser["full_name"] . " Berhasil memuat data meta");

            // Response Success
            return $this->request
            ->res(200, array(
                "devices" => array(
                    "total" => $listTotalPerangkat,
                    "month" => $listTotalPerangkatMonth,
                    "day" => $listTotalPerangkatDay
                ),
                "users" => array(
                    "total" => $listTotalUser,
                    "month" => $listTotalUserMonth,
                    "day" => $listTotalUserDay
                ),
                "ticketing" => array(
                    "total" => $listTotalTicket,
                    "month" => $listTotalTicketMonth,
                    "day" => $listTotalTicketDay
                )
            ), $tempUser["full_name"] . " Berhasil memuat data meta", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For Admin/Cs/Operator
    public function totalDataUser()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Load Total Pengguna Biasa
            $userBiasa = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'not activate yet'
            ")->row()->total;
            // Load Total Pengguna Minta Berlangganan
            $userSubscribe = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'request activate'
            ")->row()->total;
            // Load Total Pengguna Terverfikasi
            $userTerverifikasi = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'activate'
            ")->row()->total;
            // Load Total Pengguna Aktif (Pembeli)
            $userActive = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'user' AND `m_users`.`status` = 'activate'
            ")->row()->total;
            // Load Total Pengguna Tidak Aktif (Pembeli)
            $userBanned = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE (`m_users`.`type` = 'user' AND `m_users`.`status` = 'banned') OR
                (`m_users`.`type` = 'user' AND `m_users`.`status` = 'not activate yet')
            ")->row()->total;
            // Load Total Pengguna Admin
            $userAdmin = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'administrator'
            ")->row()->total;
            $userCS = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'customer service'
            ")->row()->total;
            $userOperator = $this->customSQL->query("
                SELECT count(`m_users`.`phone_number`) as total FROM `m_users`
                WHERE `m_users`.`type` = 'operator'
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data meta", $tempUser["full_name"] . " Berhasil memuat data meta");

            // Response Success
            return $this->request
            ->res(200, array(
                "guest" => $userBiasa,
                "guest_verify" => $userTerverifikasi,
                "guest_subscribe" => $userSubscribe,
                "user_active" => $userActive,
                "user_banned" => $userBanned,
                "admin" => $userAdmin,
                "operator" => $userOperator,
                "cs" => $userCS
            ), $tempUser["full_name"] . " Berhasil memuat data meta", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For Admin/CS/Operator
    public function totalDataTicket()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Load Total Tiket Baru
            $ticketBaru = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'new'
            ")->row()->total;
            // Load Total Tiket Proses
            $ticketProses = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'process'
            ")->row()->total;
            // Load Total Tiket Selesai
            $ticketSelesai = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'finish'
            ")->row()->total;
            // Load Total Tiket Tolak
            $ticketTolak = $this->customSQL->query("
                SELECT count(`m_ticketing`.`id`) as total FROM `m_ticketing`
                WHERE `m_ticketing`.`status` = 'rejected'
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data meta", $tempUser["full_name"] . " Berhasil memuat data meta");

            // Response Success
            return $this->request
            ->res(200, array(
                "new" => $ticketBaru,
                "process" => $ticketProses,
                "finish" => $ticketSelesai,
                "rejected" => $ticketTolak
            ), $tempUser["full_name"] . " Berhasil memuat data meta", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For Admin/CS/Operator
    public function totalDataDevice()
    {
        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Load Total Tiket Baru
            $pcb = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`device_type` = 'pcb'
            ")->row()->total;
            // Load Total Tiket Proses
            $sensor = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`device_type` = 'sensors'
            ")->row()->total;
            // Load Total Tiket Selesai
            $slca = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`device_type` = 'slca'
            ")->row()->total;
            // Load Total Tiket Tolak
            $kwh1phase = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`device_type` = 'kwh-1-phase'
            ")->row()->total;
            // Load Total Tiket Tolak
            $kwh3phase = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `m_devices`
                WHERE `m_devices`.`device_type` = 'kwh-3-phase'
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data meta", $tempUser["full_name"] . " Berhasil memuat data meta");

            // Response Success
            return $this->request
            ->res(200, array(
                "pcb" => $pcb,
                "sensors" => $sensor,
                "slca" => $slca,
                "kwh_1_phase" => $kwh1phase,
                "kwh_3_phase" => $kwh3phase
            ), $tempUser["full_name"] . " Berhasil memuat data meta", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
