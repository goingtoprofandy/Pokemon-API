<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Group extends CI_Controller
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
                SELECT `u_user_device_group`.* FROM `u_user_device_group`
                WHERE `u_user_device_group`.`phone_number` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_device_group`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listDeviceTotal = $this->customSQL->query("
                SELECT count(`u_user_device_group`.`id`) as total FROM `u_user_device_group`
                WHERE `u_user_device_group`.`phone_number` = '".$tempUser['phone_number']."'
                ORDER BY `u_user_device_group`.`created_at` $orderDirection
            ")->row()->total;

            $tempData = array();
            foreach ($listDevice as $item) {
                $getTotal = $this->customSQL->query("
                SELECT count(`u_user_device_group_item`.`id`) as total FROM `u_user_device_group_item`
                WHERE `u_user_device_group_item`.`id_group` = '".$item['id']."'
                ")->row()->total;
                $item["total_device"] = $getTotal;
                $tempData[] = $item;
            }

            $listDevice = $tempData;

            // Create Log
            $this->customSQL->log("Memuat data group", $tempUser["full_name"] . " Berhasil memuat data group");

            // Response Success
            return $this->request
            ->res(200, $listDevice, $tempUser["full_name"] . " Berhasil memuat data group",
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

    public function filterPreviewDevice($id_group)
    {
        $page = $this->input->get("page", TRUE) ?: 0;
        $search = $this->input->get("search", TRUE) ?: "";
        $type = $this->input->get("type", TRUE) ?: "";
        $isAlreadyAdded = $this->input->get("isAlreadyAdded", TRUE) ?: "normal";
        $orderDirection = $this->input->get("order-direction", TRUE) ?: "ASC";

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

        $where = "";
        if (!empty($type)) $where = " AND `m_devices`.`device_type` = '$type'";

        $dataDevice = $this->customSQL->query("
            SELECT `m_devices`.*, `u_user_device_group_item`.`id_group` FROM `u_users_devices`
            JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
            LEFT JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
            WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."' $where
            AND `m_devices`.`device_name` LIKE '%$search%'
            ORDER BY `m_devices`.`id` $orderDirection
        ")->result_array();

        $tempDataIn = array();
        $tempDataOut = array();
        foreach ($dataDevice as $item) {
            $temp = $item;

            if ($item["id_group"] == $id_group) {
                $yesOrNo = "yes";
                unset($item["id_group"]);
                $temp["is_already_added"] = $yesOrNo;
                $tempDataIn[] = $temp;
            }
            else if ($item["id_group"] != $id_group) {
                $yesOrNo = "no";
                unset($item["id_group"]);
                $temp["is_already_added"] = $yesOrNo;
                $tempDataOut[] = $temp;
            }
        }

        foreach ($tempDataOut as $item) {
            $checkIsOut = FALSE;
            foreach ($tempDataIn as $itemOut) {
                if ($itemOut["id"] == $item["id"]) $checkIsOut = TRUE;
            }
            if ($checkIsOut == FALSE) $tempDataIn[] = $item; 
        }

        $tempDataIn = array_reverse($tempDataIn);

        $tempData = array();
        for ($i = $offset; $i < count($tempDataIn); $i++) {
            if (count($tempData) >= $limit) break;
            else {
                $item = $tempDataIn[$i];
                if ($isAlreadyAdded != "normal") {
                    if ($isAlreadyAdded == $item["is_already_added"]) 
                    $tempData[] = $item;
                } else {
                    $tempData[] = $item;
                }
            }
        }

        $dataDevice = $tempData;

        $listDeviceTotal = $this->customSQL->query("
            SELECT count(`m_devices`.`id`) as total FROM `u_users_devices`
            JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
            LEFT JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
            WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
            ORDER BY `m_devices`.`id` $orderDirection
        ")->row()->total;

        // Create Log
        $this->customSQL->log("Memuat data group", $tempUser["full_name"] . " Berhasil memuat data group");

        // Response Success
        return $this->request
        ->res(200, $dataDevice, $tempUser["full_name"] . " Berhasil memuat data group",
        array(
            "current_page" => $page,
            "total_fetch" => count($dataDevice),
            "total_data" => $listDeviceTotal,
            "search" => $search,
            "type" => $type,
            "isAlreadyAdded" => $isAlreadyAdded,
            "order-direction" => $orderDirection
        ));
    }

    public function create()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["group_label"]) || empty($req["group_label"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $group_label = $req["group_label"];

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
                SELECT count(*) as total FROM `u_user_device_group`
                WHERE `u_user_device_group`.`phone_number` = '".$tempUser["phone_number"]."' AND
                `u_user_device_group`.`group_label` = '$group_label'
            ")->row()->total;

            if ($checkIsAlreadyUser > 0)
                return $this->request
                ->res(401, null, "Group sudah ada", null);

            // Do Update
            $checkID = $this->customSQL->create(
                array(
                    "group_label" => $group_label,
                    "phone_number" => $tempUser["phone_number"]
                ),
                "u_user_device_group"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal membuat group, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Membuat group", $tempUser["full_name"] . " Berhasil membuat group");

            // Response Success
            return $this->request
            ->res(200, array(
                "group_label" => $group_label,
                "phone_number" => $tempUser["phone_number"]
            ), 
            $tempUser["full_name"] . " Berhasil membuat group",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    public function update($id_group)
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["group_label"]) || empty($req["group_label"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Prepare Variable
            $group_label = $req["group_label"];

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
                SELECT count(*) as total FROM `u_user_device_group`
                WHERE `u_user_device_group`.`phone_number` = '".$tempUser["phone_number"]."' AND
                `u_user_device_group`.`group_label` = '$group_label'
            ")->row()->total;

            if ($checkIsAlreadyUser > 0)
                return $this->request
                ->res(401, null, "Group sudah ada", null);

            // Do Update
            $checkID = $this->customSQL->update(
                array("id" => $id_group),
                array(
                    "group_label" => $group_label,
                    "phone_number" => $tempUser["phone_number"]
                ),
                "u_user_device_group"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal mengubah group, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Mengubah group", $tempUser["full_name"] . " Berhasil mengubah group");

            // Response Success
            return $this->request
            ->res(200, array(
                "group_label" => $group_label,
                "phone_number" => $tempUser["phone_number"]
            ), 
            $tempUser["full_name"] . " Berhasil mengubah group",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    public function detail($id_group) {
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
                JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                AND `u_user_device_group_item`.`id_group` = '$id_group'
                ORDER BY `u_users_devices`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listDeviceTotal = $this->customSQL->query("
                SELECT count(`m_devices`.`id`) as total FROM `u_users_devices`
                JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                AND `u_user_device_group_item`.`id_group` = '$id_group'
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

    public function appendDevice($id_group, $id_device) {
        try {
            // Prepare Variable

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
                SELECT count(*) as total FROM `u_user_device_group_item`
                WHERE `u_user_device_group_item`.`id_m_devices` = '".$id_device."' AND
                `u_user_device_group_item`.`id_group` = '$id_group'
            ")->row()->total;

            if ($checkIsAlreadyUser > 0)
                return $this->request
                ->res(401, null, "Perangkat sudah ditambahkan sebelumnya", null);

            // Do Update
            $checkID = $this->customSQL->create(
                array(
                    "id_group" => $id_group,
                    "id_m_devices" => $id_device
                ),
                "u_user_device_group_item"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal menambahkan perangkat ke group, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Menambahkan perangkat ke group", $tempUser["full_name"] . " Berhasil menambahkan perangkat ke group");

            // Response Success
            return $this->request
            ->res(200, array(
                "id_group" => $id_group,
                "id_m_devices" => $id_device
            ), 
            $tempUser["full_name"] . " Berhasil menambahkan perangkat ke group",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    public function popDevice($id_group, $id_device) {
        try {
            // Prepare Variable

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
                SELECT count(*) as total FROM `u_user_device_group_item`
                WHERE `u_user_device_group_item`.`id_m_devices` = '".$id_device."' AND
                `u_user_device_group_item`.`id_group` = '$id_group'
            ")->row()->total;

            if ($checkIsAlreadyUser == 0)
                return $this->request
                ->res(401, null, "Perangkat sudah dihapus sebelumnya", null);

            // Do Update
            $checkID = $this->customSQL->delete(
                array(
                    "id_group" => $id_group,
                    "id_m_devices" => $id_device
                ),
                "u_user_device_group_item"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal menghapus perangkat ke group, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Menghapus perangkat ke group", $tempUser["full_name"] . " Berhasil menghapus perangkat ke group");

            // Response Success
            return $this->request
            ->res(200, array(
                "id_group" => $id_group,
                "id_m_devices" => $id_device
            ), 
            $tempUser["full_name"] . " Berhasil menghapus perangkat ke group",
            null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
