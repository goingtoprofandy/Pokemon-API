<?php
defined('BASEPATH') or exit('No direct script access allowed');

class KWH1 extends CI_Controller
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

    private function getDataDevice($device_token) {
        if (empty($device_token))
        return $this->request
        ->res(401, null, "Device ID tidak ditemukan", null);

        $dataDevice = $this->customSQL->query("
            SELECT * FROM `m_devices`
            WHERE `device_token` = '".$device_token."' AND `device_type` = 'kwh-1-phase'
        ")->result_array();

        if (count($dataDevice) != 1)
        return $this->request
        ->res(500, null, "Perangkat tidak ditemukan", null);

        $dataDevice = $dataDevice[0];

        if ($dataDevice["device_status"] == "not active yet")
        return $this->request
        ->res(500, null, "Perangkat belum diaktifasi", null);

        if ($dataDevice["device_status"] == "blocked")
        return $this->request
        ->res(500, null, "Perangkat diblokir", null);

        return $dataDevice;
    }

    public function day() {
        $device_token = $this->input->get("device_token", TRUE) ?: "";

        $dataDevice = $this->getDataDevice($device_token);

        $date = $this->input->get("date", TRUE) ?: date("Y-m-d");
        $time = $this->input->get("time", TRUE) ?: date("H:i:s");

        if ($date != date("Y-m-d")) $time = "23:59:59";

        $date_ex = explode(":", $time);
        $h = (int) $date_ex[0];
        $m = $date_ex[1];
        $s = $date_ex[2];
        $f = 0;

        $param = $this->input->get("param", TRUE) ?: "kwh";

        $res = array();

        // Get First Data
        $first = $this->getFirstFull($dataDevice, "%$date%");
        if (!empty($first)) {
            $first = (double) $first[$param];
            $first = number_format($first, 2, '.', '');
        } else 
        $first = number_format(0.0, 2, '.', '');

        $dateSplite = explode("-", $date);
        $firstDay = $this->getFirst($dataDevice, $param, $dateSplite[0] . "-" . $dateSplite[1], "%%");
        $lastDay = $this->getLast($dataDevice, $param, $dateSplite[0] . "-" . $dateSplite[1], "%%");
        $firstDayOfTheMonth = number_format(($lastDay - $firstDay), 2, '.', '');

        for ($i = $f; $i <= $h; $i++) {
            $currDay = (($i) < 10) ? "0".($i) : "".($i);
            $temp = array(
                "date" => $date,
                "time" => $currDay,
                "sum" => 0,
                "avg" => 0,
                "last" => 0,
                "first" => $first,
                "first_day_of_month" => $firstDayOfTheMonth
            );

            // Get Sum
            $temp["sum"] = number_format($this->getSUM($dataDevice, $param, $temp["date"], $currDay.":%:%"), 2, '.', '');
            $temp["avg"] = number_format($this->getAVG($dataDevice, $param, $temp["date"], $currDay.":%:%"), 2, '.', '');
            $temp["last"] = number_format($this->getLast($dataDevice, $param, $temp["date"], $currDay.":%:%"), 2, '.', '');
            $temp["items"] = $this->getUnique($dataDevice, $param, $temp["date"], $currDay.":%:%");

            $res[] = $temp;
        }

        return $this->request
            ->res(200, $res, "Berhasil memuat data perangkat $date", array(
                "first_date" => $date,
                "last_date" => $date,
                "param" => $param,
                "device_token" => $device_token
            ));
    }

    public function month() {
        $device_token = $this->input->get("device_token", TRUE) ?: "";

        $dataDevice = $this->getDataDevice($device_token);

        $date = $this->input->get("date", TRUE) ?: date("Y-m-d");
        $date_ex = explode("-", $date);
        $y = $date_ex[0];
        $m = $date_ex[1];
        $d = (int) $date_ex[2];
        $f = 1;

        $param = $this->input->get("param", TRUE) ?: "kwh";

        $res = array();

        // Get First Data
        $first = $this->getFirstFull($dataDevice, $y."-".$m."-%");
        if (!empty($first)) {
            $date = $first["date"];
            $first = (double) $first[$param];
            $first = number_format($first, 2, '.', '');
        } else
        $first = number_format(0.0, 2, '.', '');

        $firstDetail = $this->getUnique($dataDevice, $param, $date, "%:%:%");

        for ($i = 0; $i < $d; $i++) {
            $currDay = (($i + $f) < 10) ? "0".($i + $f) : "".($i + $f);
            $temp = array(
                "date" => "$y-$m-$currDay",
                "sum" => 0,
                "avg" => 0,
                "last" => 0,
                "first" => $first,
                "first_detail" => $firstDetail
            );

            // Get Sum
            $temp["sum"] = number_format($this->getSUM($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');
            $temp["avg"] = number_format($this->getAVG($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');
            $temp["last"] = number_format($this->getLast($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');

            $res[] = $temp;
        }

        return $this->request
            ->res(200, $res, "Berhasil memuat data perangkat $y-$m-$f s/d $y-$m-$d", array(
                "first_date" => "$y-$m-$f",
                "last_date" => "$y-$m-$d",
                "param" => $param,
                "device_token" => $device_token
            ));
    }

    // Fix
    public function year() {
        $device_token = $this->input->get("device_token", TRUE) ?: "";

        $dataDevice = $this->getDataDevice($device_token);

        $date = $this->input->get("date", TRUE) ?: date("Y-m");
        $date_ex = explode("-", $date);
        $y = $date_ex[0];
        $m = (int) $date_ex[1];
        $f = 1;

        $param = $this->input->get("param", TRUE) ?: "kwh";

        // Get First Data
        $first = $this->getFirstFull($dataDevice, $y."-%-%");
        if (!empty($first)) {
            $tempDate = $first["date"];
            $tempDateEx = explode("-", $tempDate);
            $f = (int) $tempDateEx[1];
        }

        $res = array();

        for ($i = ($f - 1); $i < $m; $i++) {
            $currDay = (($i + 1) < 10) ? "0".($i + 1) : "".($i + 1);
            $temp = array(
                "date" => "$y-$currDay",
                "sum" => 0,
                "avg" => 0,
                "last" => 0,
                "first" => 0
            );

            // Get Sum
            $temp["sum"] = number_format($this->getSUM($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');
            $temp["avg"] = number_format($this->getAVG($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');
            $temp["last"] = number_format($this->getLast($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');
            $temp["first"] = number_format($this->getFirst($dataDevice, $param, $temp["date"], "%%"), 2, '.', '');

            $res[] = $temp;
        }

        return $this->request
            ->res(200, $res, "Berhasil memuat data perangkat $y-$f s/d $y-$m", array(
                "first_date" => "$y-$f",
                "last_date" => "$y-$m",
                "param" => $param,
                "device_token" => $device_token
            ));
    }

    private function getUnique($device, $param, $currentDate, $currentTime) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param, date, time 
            FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $param != 0
            ORDER BY id ASC
        ")->result_array();

        return $listDataDeviceTotal;
    }

    private function getSUM($device, $param, $currentDate, $currentTime) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT SUM($param) as total
            FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
        ")->row()->total;

        return (double) $listDataDeviceTotal;
    }

    private function getAVG($device, $param, $currentDate, $currentTime) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT AVG($param) as total
            FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
        ")->row()->total;

        return (double) $listDataDeviceTotal;
    }

    private function getLast($device, $param, $currentDate, $currentTime) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param
            FROM `u_device_data_kwh-1-phase`
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
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $param
            FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $param != 0
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? (double) $listDataDeviceTotal[0][$param] : 0.0;
    }

    private function getFirstFull($device, $currentDate) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT *
            FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '".$currentDate."'
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? $listDataDeviceTotal[0] : null;
    }

}
