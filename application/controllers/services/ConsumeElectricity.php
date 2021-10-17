<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ConsumeElectricity extends CI_Controller
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

            $listTicket = $this->customSQL->query("
                SELECT `m_users`.`full_name`, 
                `m_users`.`email`, 
                `m_ticketing`.* FROM `m_ticketing`
                JOIN `m_users` ON `m_ticketing`.`phone_number` = `m_users`.`phone_number`
                ORDER BY `m_ticketing`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listTicketTotal = $this->customSQL->query("
                SELECT count(`m_ticketing`.`phone_number`) as total FROM `m_ticketing`
                JOIN `m_users` ON `m_ticketing`.`phone_number` = `m_users`.`phone_number`
                ORDER BY `m_ticketing`.`created_at` $orderDirection
            ")->row()->total;

            // Create Log
            $this->customSQL->log("Memuat data tiket", $tempUser["full_name"] . " Berhasil memuat data tiket");

            // Response Success
            return $this->request
            ->res(200, $listTicket, $tempUser["full_name"] . " Berhasil memuat data tiket",
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

    // For Specific User
    public function my()
    {
        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            // $listTicket = $this->customSQL->query("
                
            // ")->result_array();

            // Create Log
            $this->customSQL->log("Memuat data tiket", $tempUser["full_name"] . " Berhasil memuat data tiket");

            // Response Success
            return $this->request
            ->res(200, array(
                "first_date" => date("Y-m-01"),
                "last_date" => date("Y-m-t")
            ), $tempUser["full_name"] . " Berhasil memuat data tiket",null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
