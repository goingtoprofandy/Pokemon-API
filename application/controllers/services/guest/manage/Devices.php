<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Devices extends CI_Controller
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

            if ($tempUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_user_child_access_devices`
                JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_user_child_access_devices`.`phone_number_child` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_child_access_devices`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listDeviceTotal = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `u_user_child_access_devices`
                JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_user_child_access_devices`.`phone_number_child` = '".$tempUser['phone_number']."'
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

}
