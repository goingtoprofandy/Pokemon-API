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

    // Load Lists Users
    public function index()
    {
        $type = $this->input->get("type") ?: "";
        $page = $this->input->get("page", TRUE) ?: 0;
        $search = $this->input->get("search") ?: "";
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

            if ($tempUser["type"] != "administrator")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $filter = "";
            if ($type == "guest") $filter = "`type` = 'guest' AND `status` = 'not activate yet'";
            else if ($type == "verify") $filter = "`type` = 'guest' AND `status` = 'activate'";
            else if ($type == "user-active") $filter = "`type` = 'user' AND `status` = 'activate'";
            else if ($type == "user-not-active") $filter = "`type` = 'user' AND `status` = 'banned'";
            else if ($type == "customer-service") $filter = "`type` = 'customer service'";
            else if ($type == "request-activate") $filter = "`type` = 'guest' AND `status` = 'request activate'";
            else $filter = "`type` LIKE '%".$type."%'";

            $listGuest = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE $filter
                ORDER BY `created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listGuestTotal = $this->customSQL->query("
                SELECT count(`phone_number`) as total FROM `m_users`
                ORDER BY `created_at` $orderDirection
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

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

    // Load Info For User/Guest
    public function info()
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

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

            if ($selectedUser["type"] != "guest" && $selectedUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            if ($selectedUser["type"] == "guest") {
                $selectedUser = $this->customSQL->query("
                    SELECT `phone_number_parent` as phone_number FROM `u_user_child`
                    WHERE `phone_number_child` = '".$selectedUser["phone_number"]."'
                ")->result_array();

                if (count($selectedUser) != 1)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

                $selectedUser = $selectedUser[0];
            }

            $selectedUser = $this->customSQL->query("
                SELECT * FROM `u_user_info`
                WHERE `phone_number` = '".$selectedUser["phone_number"]."'
            ")->result_array();

            if (count($selectedUser) != 1)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $selectedUser = $selectedUser[0];

            $installedBy = $this->customSQL->query("
                SELECT `full_name` FROM `m_users`
                WHERE `phone_number` = '".$selectedUser["installed_by"]."'
            ")->row()->full_name;

            $selectedUser["installed_by"] = $installedBy;

            // Create Log
            $this->customSQL->log("Memuat info pengguna", $tempUser["full_name"] . " Berhasil memuat info pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil memuat info pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Delete Guest
    public function delete()
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

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

            if ($selectedUser["type"] != "guest" && $selectedUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Do Delete
            $checkID = $this->customSQL->delete(
                array("phone_number_child" => $phone_number),
                "u_user_child_access_devices"
            );

            $checkID = $this->customSQL->delete(
                array("phone_number_child" => $phone_number),
                "u_user_child"
            );

            $checkID = $this->customSQL->delete(
                array("phone_number" => $phone_number),
                "m_users"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal membuat tiket, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Menghapus pengguna", $tempUser["full_name"] . " Berhasil menghapus pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil menghapus pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Load List Guest By User
    public function listGuestByUser()
    {
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";
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

            $selectedUser = $this->customSQL->query("
                SELECT * FROM `m_users`
                WHERE `phone_number` = '$phone_number'
            ")->result_array();

            if (count($selectedUser) != 1) 
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $selectedUser = $selectedUser[0];

            if ($selectedUser["type"] != "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $listGuest = $this->customSQL->query("
                SELECT `m_users`.`phone_number`, `m_users`.`full_name`, 
                `m_users`.`email`, 
                `m_users`.`status`, 
                `m_users`.`type`, 
                `m_users`.`created_at`, 
                `m_users`.`updated_at` FROM `u_user_child`
                JOIN `m_users` ON `u_user_child`.`phone_number_child` = `m_users`.`phone_number`
                WHERE `u_user_child`.`phone_number_parent` = '".$phone_number."'
                ORDER BY `u_user_child`.`created_at` $orderDirection
                LIMIT $limit OFFSET $offset
            ")->result_array();

            $listGuestTotal = $this->customSQL->query("
                SELECT count(`u_user_child`.`phone_number_child`) as total FROM `u_user_child`
                JOIN `m_users` ON `u_user_child`.`phone_number_child` = `m_users`.`phone_number`
                WHERE `u_user_child`.`phone_number_parent` = '".$phone_number."'
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

    // Create User All Type
    public function create()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) ||
            !isset($req["password"]) || empty($req["password"]) || 
            !isset($req["email"]) || empty($req["email"]) || 
            !isset($req["full_name"]) || empty($req["full_name"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $phone_number = $req["phone_number"];
            $password = md5($req["phone_number"] . $req["password"]);
            $full_name = $req["full_name"];
            $email = $req["email"];
            $token = md5($req["phone_number"] . $req["password"] . $req["email"]);

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $checkIsAlready = $this->customSQL->query("
                SELECT `full_name`, `type`, `status`, `token`
                FROM `m_users`
                WHERE `phone_number` = '$phone_number'
            ")->result_array();

            if (count($checkIsAlready) == 0) {   

                $type = "guest";
                $status = "not activate yet";

                if (isset($req["type"])) $type = $req["type"];
                if (isset($req["status"])) $status = $req["status"];

                if ($type == "user") {
                    // Check Request
                    if (!isset($req["ssid_name"]) || empty($req["ssid_name"]) || 
                        !isset($req["ssid_password"]) || empty($req["ssid_password"])) {
                        return $this->request
                        ->res(400, null, "Parameter tidak benar, cek kembali", null);
                    }
                }

                // Create Account
                $checkID = $this->customSQL->create(
                    array(
                        "phone_number" => $phone_number,
                        "full_name" => $full_name,
                        "email" => $email,
                        "password" => $password,
                        "status" => $status,
                        "type" => $type,
                        "token" => $token,
                        "created_at" => date("Y-m-d H:i:s"), 
                        "updated_at" => date("Y-m-d H:i:s")
                    ), 
                    "m_users"
                );

                if ($checkID == -1)
                    return $this->request
                    ->res(500, null, "Gagal Registrasi, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

                if ($type == "user") {
                    $ssid_name = $req["ssid_name"];
                    $ssid_password = $req["ssid_password"];
                    $api_key = md5($phone_number . date("Y-m-d H:i:s"));

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
                }

                // Create Log
                $this->customSQL->log("Berhasil Registrasi", $full_name . " Berhasil melakukan otentikasi");

                // Response Success
                return $this->request
                ->res(200, null, $full_name . " Berhasil membuat user", null);
            }

            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun sudah terdaftar sebelumnya", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Update User All Type
    public function update()
    {
        $req = $this->request->raw();

        // Check Request
        if (!isset($req["phone_number"]) || empty($req["phone_number"]) ||
            !isset($req["password"]) || empty($req["password"]) || 
            !isset($req["email"]) || empty($req["email"]) || 
            !isset($req["full_name"]) || empty($req["full_name"])) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Check Valid Data
            $phone_number = $req["phone_number"];
            $password = md5($req["phone_number"] . $req["password"]);
            $full_name = $req["full_name"];
            $email = $req["email"];

            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            $checkIsAlready = $this->customSQL->query("
                SELECT *
                FROM `m_users`
                WHERE `phone_number` = '$phone_number'
            ")->result_array();

            if (count($checkIsAlready) == 1) {   

                $checkIsAlready = $checkIsAlready[0];

                $type = "guest";

                if (isset($req["type"])) $type = $req["type"];

                if ($type == "user") {
                    // Check Request
                    if (!isset($req["ssid_name"]) || empty($req["ssid_name"]) || 
                        !isset($req["ssid_password"]) || empty($req["ssid_password"])) {
                        return $this->request
                        ->res(400, null, "Parameter tidak benar, cek kembali", null);
                    }
                }

                $data = array(
                    "full_name" => $full_name,
                    "email" => $email, 
                    "updated_at" => date("Y-m-d H:i:s")
                );

                if ($req["password"] != $checkIsAlready["password"]) $data["password"] = $password;

                // Create Account
                $checkID = $this->customSQL->update(
                    array("phone_number" => $phone_number),
                    $data, 
                    "m_users"
                );

                if ($checkID == -1)
                    return $this->request
                    ->res(500, null, "Gagal Registrasi, Terjadi suatu kesalahan, silahkan ulangi beberapa saat lagi", null);

                if ($type == "user") {
                    $ssid_name = $req["ssid_name"];
                    $ssid_password = $req["ssid_password"];
                    $api_key = md5($phone_number . date("Y-m-d H:i:s"));

                    $checkID = $this->customSQL->update(
                        array("phone_number" => $phone_number),
                        array(
                            "ssid_name" => $ssid_name,
                            "ssid_password" => $ssid_password,
                            "api_key" => $api_key
                        ),
                        "u_user_info"
                    );
                }

                // Create Log
                $this->customSQL->log("Berhasil perubahan", $full_name . " Berhasil melakukan perubahan");

                // Response Success
                return $this->request
                ->res(200, null, $full_name . " Berhasil mengubah user", null);
            }

            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Change Status Without Admin
    public function banned()
    {
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";

        // Check Request
        if (!isset($phone_number) || empty($phone_number)) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

            if ($selectedUser["status"] != "activate")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Do Delete
            $checkID = $this->customSQL->update(
                array("phone_number" => $phone_number),
                array(
                    "status" => "banned",
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_users"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal memblokir pengguna, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Memblokir pengguna", $tempUser["full_name"] . " Berhasil memblokir pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil memblokir pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    // Change Status Without Admin
    public function activate()
    {
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";

        // Check Request
        if (!isset($phone_number) || empty($phone_number)) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

            if ($selectedUser["status"] != "banned")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Do Delete
            $checkID = $this->customSQL->update(
                array("phone_number" => $phone_number),
                array(
                    "status" => "activate",
                    "updated_at" => date("Y-m-d H:i:s")
                ),
                "m_users"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal memblokir pengguna, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Memblokir pengguna", $tempUser["full_name"] . " Berhasil memblokir pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil memblokir pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    public function plotCustomerToOperator()
    {
        $phone_number = $this->input->get("phone_number", TRUE) ?: "";
        $phone_number_operator = $this->input->get("phone_number_operator", TRUE) ?: "";

        // Check Request
        if (!isset($phone_number) || empty($phone_number) ||
            !isset($phone_number_operator) || empty($phone_number_operator)) {
            return $this->request
            ->res(400, null, "Parameter tidak benar, cek kembali", null);
        }

        try {
            // Preparing Filter
            $tempUser = $this->customSQL->checkValid();

            if (count($tempUser) == 0)
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
            
            $tempUser = $tempUser[0];

            if ($tempUser["type"] == "guest" && $tempUser["type"] == "user")
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

            if ($selectedUser["type"] != "guest")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            if ($selectedUser["status"] != "request activate")
                return $this->request
                ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

            // Do Delete
            $checkID = $this->customSQL->update(
                array("phone_number" => $selectedUser["phone_number"]),
                array(
                    "updated_at" => date("Y-m-d H:i:s"),
                    "status" => "process",
                    "operator" => $phone_number_operator,
                    "plot_by" => $tempUser["phone_number"]
                ),
                "u_user_request_activate"
            );

            if ($checkID == -1)
            return $this->request
            ->res(500, null, "Gagal memploting pelanggan, terjadi kesalahan pada sisi server", null);

            // Create Log
            $this->customSQL->log("Memploting pengguna", $tempUser["full_name"] . " Berhasil memploting pengguna");

            // Response Success
            return $this->request
            ->res(200, $selectedUser, $tempUser["full_name"] . " Berhasil memploting pengguna", null);

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

}
