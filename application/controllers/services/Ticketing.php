<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ticketing extends CI_Controller
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
        $type = $this->input->get("type", TRUE) ?: "";
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
                SELECT `m_users`.`full_name`, 
                `m_users`.`email`, 
                `m_ticketing`.* FROM `m_ticketing`
                JOIN `m_users` ON `m_ticketing`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_ticketing`.`status` LIKE '%".$type."%'
                ORDER BY `m_ticketing`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listTicketTotal = $this->customSQL->query("
                SELECT count(`m_ticketing`.`phone_number`) as total FROM `m_ticketing`
                JOIN `m_users` ON `m_ticketing`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_ticketing`.`status` LIKE '%".$type."%'
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
        $type = $this->input->get("type", TRUE) ?: "";
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
                WHERE `m_ticketing`.`phone_number` = '".$tempUser['phone_number']."'
                AND `m_ticketing`.`status` LIKE '%".$type."%'
                ORDER BY `m_ticketing`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listTicketTotal = $this->customSQL->query("
                SELECT count(`m_ticketing`.`phone_number`) as total FROM `m_ticketing`
                JOIN `m_users` ON `m_ticketing`.`phone_number` = `m_users`.`phone_number`
                WHERE `m_ticketing`.`phone_number` = '".$tempUser['phone_number']."'
                AND `m_ticketing`.`status` LIKE '%".$type."%'
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

    // For All User
    public function create()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["title"]) || empty($req["title"]) || 
            !isset($req["description"]) || empty($req["description"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $title = $req["title"];
            $description = $req["description"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            $data = array(
                "phone_number" => $tempUser["phone_number"],
                "title" => $title,
                "description" => $description,
                "status" => "new",
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            );

            // Do Create
            $checkID = $this->customSQL->create(
                $data,
                "m_ticketing"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal membuat tiket, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Membuat tiket", $tempUser["full_name"] . " Berhasil membuat tiket");

            // Response Success
            return $this->request
            ->res(200, $data, 
            $tempUser["full_name"] . " Berhasil membuat tiket",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For All User
    public function reply($id)
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["reply"]) || empty($req["reply"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $reply = $req["reply"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            $data = array(
                "phone_number" => $tempUser["phone_number"],
                "reply" => $reply,
                "id_m_ticketing" => $id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            );

            // Do Create
            $checkID = $this->customSQL->create(
                $data,
                "u_ticketing_reply"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal membalas tiket, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Membalas tiket", $tempUser["full_name"] . " Berhasil membalas tiket");

            // Response Success
            return $this->request
            ->res(200, $data, 
            $tempUser["full_name"] . " Berhasil membalas tiket",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // For Operator/CS/Admin
    public function status($id)
    {
        $newStatus = $this->input->get("status", TRUE) ?: "";

        // Check Request
        if (!isset($newStatus) || empty($newStatus)) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" || $tempUser["type"] == "user")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $data = array(
                "status" => $newStatus,
                "updated_at" => date("Y-m-d H:i:s")
            );

            // Do Update
            $checkID = $this->customSQL->update(
                array("id" => $id),
                $data,
                "m_ticketing"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengubah status tiket, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Mengubah status tiket", $tempUser["full_name"] . " Berhasil mengubah status tiket");

            // Response Success
            return $this->request
            ->res(200, $data, 
            $tempUser["full_name"] . " Berhasil mengubah status tiket",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
