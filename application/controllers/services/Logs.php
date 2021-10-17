<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Logs extends CI_Controller
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

    // For Administrator
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

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listTicket = $this->customSQL->query("
                SELECT `m_logs`.* FROM `m_logs`
                ORDER BY `m_logs`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listTicketTotal = $this->customSQL->query("
                SELECT count(`m_logs`.`id`) as total FROM `m_logs`
                ORDER BY `m_logs`.`created_at` $orderDirection
            ")->row()->total;

            // Response Success
            return $this->request
            ->res(200, $listTicket, $tempUser["full_name"] . " Berhasil memuat data aktifitas", 
            array(
                "current_page" => $page,
                "total_fetch" => count($listTicket),
                "total_data" => $listTicketTotal,
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
