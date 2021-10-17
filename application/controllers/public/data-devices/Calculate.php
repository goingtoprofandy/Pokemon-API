<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Calculate extends CI_Controller
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

    public function getTotal() {
        $tempUser = $this->customSQL->checkValid();

        if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
        
        $tempUser = $tempUser[0];

        if ($tempUser["type"] != "user" && $tempUser["type"] != "guest")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        $dataDevice = array();

        if ($tempUser["type"] == "user") {
            $dataDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_users_devices`
                JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
            ")->result_array();
        } else {
            $dataDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_user_child_access_devices`
                JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_user_child_access_devices`.`phone_number_child` = '".$tempUser['phone_number']."'
                AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
            ")->result_array();
        }

        if (count($dataDevice) == 0)
        return $this->request
        ->res(500, null, "Belum ada perangkat KWH yang terpasang", null);

        $tempMonthData = array();

        foreach ($dataDevice as $item) {
            $param = "ep";
            if ($item["device_type"] == "kwh-1-phase") $param = "kwh";
            $tempMonthData[] = $this->month($item, $item["device_token"], $param); 
        }

        $totalKWH = 0;
        $index = 0;
        foreach ($tempMonthData as $item) {
            $param = "ep";
            if ($dataDevice[$index]["device_type"] == "kwh-1-phase") $param = "kwh";
            $temp = $this->getKWH($item, $param);
            $totalKWH += $temp;
            $index += 1;
        }

        $price = ($totalKWH * 979);

        return $this->request
        ->res(200, array(
            "total_kwh" => $totalKWH,
            "price" => $price,
            "price_str" => "Rp" . number_format($price, 2, ',', '.')
        ), "Berhasil memuat data KWH", null);
    }

    public function getTotalOnGroup($groupID) {
        $tempUser = $this->customSQL->checkValid();

        if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
        
        $tempUser = $tempUser[0];

        if ($tempUser["type"] != "user" && $tempUser["type"] != "guest")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        $dataDevice = array();

        $dataDevice = $this->customSQL->query("
            SELECT `m_devices`.* FROM `u_users_devices`
            JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
            JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
            WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
            AND `u_user_device_group_item`.`id_group` = '".$groupID."'
            AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
        ")->result_array();

        if (count($dataDevice) == 0)
        return $this->request
        ->res(500, null, "Belum ada perangkat KWH yang terpasang", null);

        $tempMonthData = array();

        foreach ($dataDevice as $item) {
            $param = "ep";
            if ($item["device_type"] == "kwh-1-phase") $param = "kwh";
            $tempMonthData[] = $this->month($item, $item["device_token"], $param); 
        }

        $totalKWH = 0;
        $index = 0;
        foreach ($tempMonthData as $item) {
            $param = "ep";
            if ($dataDevice[$index]["device_type"] == "kwh-1-phase") $param = "kwh";
            $temp = $this->getKWH($item, $param);
            $totalKWH += $temp;
            $index += 1;
        }

        $price = ($totalKWH * 979);

        return $this->request
        ->res(200, array(
            "total_kwh" => $totalKWH,
            "price" => $price,
            "price_str" => "Rp" . number_format($price, 2, ',', '.')
        ), "Berhasil memuat data KWH", null);
    }

    public function getTotalOnce($device_token) {
        $tempUser = $this->customSQL->checkValid();

        if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
        
        $tempUser = $tempUser[0];

        if ($tempUser["type"] != "user" && $tempUser["type"] != "guest")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        $dataDevice = array();

        $dataDevice = $this->customSQL->query("
            SELECT `m_devices`.* FROM `u_users_devices`
            JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
            WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
            AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
            AND `m_devices`.`device_token` = '$device_token'
        ")->result_array();

        if (count($dataDevice) == 0)
        return $this->request
        ->res(500, null, "Perangkat KWH tidak ditemukan", null);

        $tempMonthData = array();

        foreach ($dataDevice as $item) {
            $param = "ep";
            if ($item["device_type"] == "kwh-1-phase") $param = "kwh";
            $tempMonthData[] = $this->month($item, $item["device_token"], $param); 
        }

        $totalKWH = 0;
        $index = 0;
        foreach ($tempMonthData as $item) {
            $param = "ep";
            if ($dataDevice[$index]["device_type"] == "kwh-1-phase") $param = "kwh";
            $temp = $this->getKWH($item, $param);
            $totalKWH += $temp;
            $index += 1;
        }

        $price = ($totalKWH * 979);

        return $this->request
        ->res(200, array(
            "total_kwh" => $totalKWH,
            "price" => $price,
            "price_str" => "Rp" . number_format($price, 2, ',', '.')
        ), "Berhasil memuat data KWH", null);
    }

    public function listKWH() {
        $tempUser = $this->customSQL->checkValid();

        if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
        
        $tempUser = $tempUser[0];

        if ($tempUser["type"] != "user" && $tempUser["type"] != "guest")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        $dataDevice = array();

        if ($tempUser["type"] == "user") {
            $dataDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_users_devices`
                JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
                AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
            ")->result_array();
        } else {
            $dataDevice = $this->customSQL->query("
                SELECT `m_devices`.* FROM `u_user_child_access_devices`
                JOIN `m_devices` ON `u_user_child_access_devices`.`id_m_devices` = `m_devices`.`id`
                WHERE `u_user_child_access_devices`.`phone_number_child` = '".$tempUser['phone_number']."'
                AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
            ")->result_array();
        }

        if (count($dataDevice) == 0)
        return $this->request
        ->res(500, null, "Belum ada perangkat KWH yang terpasang", null);

        $tempMonthData = array();

        foreach ($dataDevice as $item) {
            $param = "ep";
            if ($item["device_type"] == "kwh-1-phase") $param = "kwh";
            $tempMonthData[] = $this->month($item, $item["device_token"], $param); 
        }

        $totalKWH = array();
        $index = 0;
        foreach ($tempMonthData as $item) {
            $param = "ep";
            if ($dataDevice[$index]["device_type"] == "kwh-1-phase") $param = "kwh";
            $temp = $this->getKWH($item, $param);
            $tempKWH = $dataDevice[$index];
            $price = ($temp * 979);
            $tempKWH["preview"] = array(
                "total_kwh" => $temp,
                "price" => $price,
                "price_str" => "Rp" . number_format($price, 2, ',', '.')
            );
            $totalKWH[] = $tempKWH;
            $index += 1;
        }

        return $this->request
        ->res(200, $totalKWH, "Berhasil memuat data KWH", null);
    }

    public function listKWHGroup($groupID) {
        $tempUser = $this->customSQL->checkValid();

        if (count($tempUser) == 0)
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);
        
        $tempUser = $tempUser[0];

        if ($tempUser["type"] != "user" && $tempUser["type"] != "guest")
            return $this->request
            ->res(401, null, "Tidak ter-otentikasi, akun tidak ditemukan", null);

        $dataDevice = array();

        $dataDevice = $this->customSQL->query("
            SELECT `m_devices`.* FROM `u_users_devices`
            JOIN `m_devices` ON `u_users_devices`.`id_m_devices` = `m_devices`.`id`
            JOIN `u_user_device_group_item` ON `u_users_devices`.`id_m_devices` = `u_user_device_group_item`.`id_m_devices`
            WHERE `u_users_devices`.`phone_number` = '".$tempUser['phone_number']."'
            AND `u_user_device_group_item`.`id_group` = '".$groupID."'
            AND (`m_devices`.`device_type` = 'kwh-1-phase' OR `m_devices`.`device_type` = 'kwh-3-phase')
        ")->result_array();

        if (count($dataDevice) == 0)
        return $this->request
        ->res(500, null, "Belum ada perangkat KWH yang terpasang", null);

        $tempMonthData = array();

        foreach ($dataDevice as $item) {
            $param = "ep";
            if ($item["device_type"] == "kwh-1-phase") $param = "kwh";
            $tempMonthData[] = $this->month($item, $item["device_token"], $param); 
        }

        $totalKWH = array();
        $index = 0;
        foreach ($tempMonthData as $item) {
            $param = "ep";
            if ($dataDevice[$index]["device_type"] == "kwh-1-phase") $param = "kwh";
            $temp = $this->getKWH($item, $param);
            $tempKWH = $dataDevice[$index];
            $price = ($temp * 979);
            $tempKWH["preview"] = array(
                "total_kwh" => $temp,
                "price" => $price,
                "price_str" => "Rp" . number_format($price, 2, ',', '.')
            );
            $totalKWH[] = $tempKWH;
            $index += 1;
        }

        return $this->request
        ->res(200, $totalKWH, "Berhasil memuat data KWH", null);
    }

    private function getKWH($dataKWH, $param) {
        $temp = null;
        $itemsTemp = array();

        foreach ($dataKWH as $item) {
            if (empty($temp)) {
                if ($item["last"] == 0.0) {
                    if ($item["first"] != 0.0) $item["last"] = $item["first"];
                } else {
                    if ($item["first"] > $item["last"]) $item["last"] += $item["first"];
                }
                $temp = $item;
            } else {
                if ($item["last"] == 0.0) $item["last"] = $temp["last"];
                else {
                    if ($temp["last"] > $item["last"]) $item["last"] = $temp["last"];
                }
                $temp = $item;
            }
            $itemsTemp[] = $temp;
        }

        $distance = array();
        $tempSlice = array_slice($itemsTemp, 1);
        $index = 0;
        foreach ($tempSlice as $item) {
            $temp = $dataKWH[$index];
            $temp["last"] = ($item["last"] - $temp["last"]);
            $distance[] = $temp["last"];
            $index += 1;
        }

        // print_r (json_encode($distance));
        array_unshift($distance, 0.0);

        // die (json_encode($distance));

        $dataFix = array();
        $firstValue = 0.0;

        if (count($dataKWH[0]["first_detail"]) > 0) {
            $tempF = $dataKWH[0]["first_detail"][0][$param];
            $tempL = $dataKWH[0]["first_detail"][(count($dataKWH[0]["first_detail"]) - 1)][$param];
            $checker = ($tempL - $tempF) < 0 ? ($tempL - $tempF) * -1 : ($tempL - $tempF);
            $firstValue = $checker;
        }

        foreach ($distance as $item) {
            $firstValue = ($firstValue + $item);
            $dataFix[] = $firstValue;
        }

        // die(json_encode(
        //     array("distance" => $distance, "fix" => $dataFix)
        // ));

        $itemsTemp = array();
        $index = 0;
        foreach($dataKWH  as $item) {
            $temp = $item;
            $temp["last"] = $dataFix[$index];
            $index += 1;
            $itemsTemp[] = $temp;
        }

        return $itemsTemp[(count($itemsTemp) - 1)]["last"];
    } 

    private function month($dataDevice, $device_token, $param) {

        $date = date("Y-m-d");
        $date_ex = explode("-", $date);
        $y = $date_ex[0];
        $m = $date_ex[1];
        $d = (int) $date_ex[2];
        $f = 1;

        $res = array();

        // Get First Data
        $first = $this->getFirstFull($dataDevice, $param, $y."-".$m."-%");
        if (!empty($first)) {
            $date = $first["date"];
            $first = (double) $first[$param];
        } else
        $first = 0.0;

        $firstDetail = $this->getUnique($dataDevice, $param, $date, "%:%:%");

        for ($i = 0; $i < $d; $i++) {
            $currDay = (($i + $f) < 10) ? "0".($i + $f) : "".($i + $f);
            $temp = array(
                "date" => "$y-$m-$currDay",
                "last" => 0,
                "first" => $first,
                "first_detail" => $firstDetail
            );

            // Get Sum
            $temp["last"] = $this->getLast($dataDevice, $param, $temp["date"], "%%");

            $res[] = $temp;
        }

        // die(json_encode($res));

        return $res;
    }

    private function getUnique($device, $param, $currentDate, $currentTime) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $param != 0
            ORDER BY id ASC
        ")->result_array();

        return $listDataDeviceTotal;
    }

    private function getSUM($device, $param, $currentDate, $currentTime) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT SUM($param) as total
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
        ")->row()->total;

        return (double) $listDataDeviceTotal;
    }

    private function getAVG($device, $param, $currentDate, $currentTime) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT AVG($param) as total
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
        ")->row()->total;

        return (double) $listDataDeviceTotal;
    }

    private function getLast($device, $param, $currentDate, $currentTime) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $param != 0
            ORDER BY `id` DESC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? (double) $listDataDeviceTotal[0][$param] : 0.0;
    }

    private function getFirst($device, $param, $currentDate, $currentTime) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? (double) $listDataDeviceTotal[0][$param] : 0.0;
    }

    private function getFirstFull($device, $param, $currentDate) {
        $type = "";

        if ($param == "kwh") $type = "u_device_data_kwh-1-phase";
        else $type = "u_device_data_kwh-3-phase";

        $listDataDeviceTotal = $this->customSQL->query("
            SELECT *
            FROM `$type`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '".$currentDate."'
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? $listDataDeviceTotal[0] : null;
    }

}
