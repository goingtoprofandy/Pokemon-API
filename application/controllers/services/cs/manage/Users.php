<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Users extends CI_Controller
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
        $status = $this->input->get("status", TRUE) ?: "";
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
                LEFT JOIN `u_user_request_activate` ON `u_user_request_activate`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'request activate'
                AND `u_user_request_activate`.`status` LIKE '%".$status."%'
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
                LEFT JOIN `u_user_request_activate` ON `u_user_request_activate`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_users`.`type` = 'guest' AND `m_users`.`status` = 'request activate'
                AND `u_user_request_activate`.`status` LIKE '%".$status."%'
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

}
