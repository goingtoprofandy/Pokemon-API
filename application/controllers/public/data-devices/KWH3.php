<?php
defined('BASEPATH') or exit('No direct script access allowed');

class KWH3 extends CI_Controller
{
    // Public Variable
    public $session, $custom_curl;
    public $csrf_token, $auth;
    public $topBarContent, $navBarContent, $collectionParams;

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

        $this->collectionParams = array(
            "v" => "va, vb, vc",
            "v2" => "vab, vbc, vca",
            "i" => "ia, ib, ic",
            "p" => "pa, pb, pc, pt",
            "q" => "qa, qb, qc, qt",
            "s" => "sa, sb, sc, st",
            "pf" => "pfa, pfb, pfc",
            "freq" => "freq",
            "e" => "ep, eq"
        );
    }

    private function getDataDevice($device_token) {
        if (empty($device_token))
        return $this->request
        ->res(401, null, "Device ID tidak ditemukan", null);

        $dataDevice = $this->customSQL->query("
            SELECT * FROM `m_devices`
            WHERE `device_token` = '".$device_token."' AND `device_type` = 'kwh-3-phase'
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
        $time = $this->input->get("time", TRUE) ?: "";

        if ($date != date("Y-m-d")) {
            if ($time == "") $time = "23:59:59";
        } else $time = date("H:i:s");

        $date_ex = explode(":", $time);
        $h = (int) $date_ex[0];
        $m = $date_ex[1];
        $s = $date_ex[2];
        $f = 0;

        $param = $this->input->get("param", TRUE) ?: "e";

        $res = array();

        // Get First Data
        $first = $this->getFirstFull($dataDevice, "%$date%");
        $selectParam = $this->collectionParams[$param];
        $splitParam = explode(", ", $selectParam);
        $defaultValue = [];
        if (!empty($first)) {
            $temp = array();
            foreach ($splitParam as $item) {
                $defaultValue[$item] = 0;
                $temp[$item] = (double) $first[$item];
                $temp[$item] = number_format($temp[$item], 2, '.', '');
            }
            $first = $temp;
        } else {
            $temp = array();
            foreach ($splitParam as $item) {
                $defaultValue[$item] = 0;
                $temp[$item] = number_format(0.0, 2, '.', '');
            }
            $first = $temp;
        }

        $dateSplite = explode("-", $date);
        $firstDay = $this->getFirst($dataDevice, $param, $dateSplite[0] . "-" . $dateSplite[1], "%%");
        $lastDay = $this->getLast($dataDevice, $param, $dateSplite[0] . "-" . $dateSplite[1], "%%");
        
        $firstDayOfTheMonth = array();
        foreach ($lastDay as $key => $value) {
            $valueFirst = $firstDay[$key];
            $firstDayOfTheMonth[$key] = number_format(($value - $valueFirst), 2, '.', '');
        }

        for ($i = $f; $i <= $h; $i++) {
            $currDay = (($i) < 10) ? "0".($i) : "".($i);
            $temp = array(
                "date" => $date,
                "time" => $currDay,
                "sum" => $defaultValue,
                "avg" => $defaultValue,
                "last" => $defaultValue,
                "first" => $first,
                "first_day_of_month" => $firstDayOfTheMonth
            );

            // Get Sum
            $tempData = $this->getSUM($dataDevice, $param, $temp["date"], $currDay.":%:%");
            foreach ($tempData as $key => $value) {
                $temp["sum"][$key] = number_format($value, 2, '.', '');
            }

            // Get AVG
            $tempData = $this->getAVG($dataDevice, $param, $temp["date"], $currDay.":%:%");
            foreach ($tempData as $key => $value) {
                $temp["avg"][$key] = number_format($value, 2, '.', '');
            }

            // Get Last
            $tempData = $this->getLast($dataDevice, $param, $temp["date"], $currDay.":%:%");
            foreach ($tempData as $key => $value) {
                $temp["last"][$key] = number_format($value, 2, '.', '');
            }

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

        $param = $this->input->get("param", TRUE) ?: "e";

        $res = array();

        // Get First Data
        $first = $this->getFirstFull($dataDevice, $y."-".$m."-%");
        $selectParam = $this->collectionParams[$param];
        $splitParam = explode(", ", $selectParam);
        $defaultValue = [];
        if (!empty($first)) {
            $date = $first["date"];
            $temp = array();
            foreach ($splitParam as $item) {
                $defaultValue[$item] = 0;
                $temp[$item] = (double) $first[$item];
                $temp[$item] = number_format($temp[$item], 2, '.', '');
            }
            $first = $temp;
        } else {
            $temp = array();
            foreach ($splitParam as $item) {
                $defaultValue[$item] = 0;
                $temp[$item] = number_format(0.0, 2, '.', '');
            }
            $first = $temp;
        }

        $firstDetail = $this->getUnique($dataDevice, $param, $date, "%:%:%");

        for ($i = 0; $i < $d; $i++) {
            $currDay = (($i + $f) < 10) ? "0".($i + $f) : "".($i + $f);
            $temp = array(
                "date" => "$y-$m-$currDay",
                "sum" => $defaultValue,
                "avg" => $defaultValue,
                "last" => $defaultValue,
                "first" => $first,
                "first_detail" => $firstDetail
            );

            // Get Sum
            $tempData = $this->getSUM($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["sum"][$key] = number_format($value, 2, '.', '');
            }
            // Get AVG
            $tempData = $this->getAVG($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["avg"][$key] = number_format($value, 2, '.', '');
            }
            // Get Last
            $tempData = $this->getLast($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["last"][$key] = number_format($value, 2, '.', '');
            }

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

        $param = $this->input->get("param", TRUE) ?: "e";

        // Get First Data
        $first = $this->getFirstFull($dataDevice, $y."-%-%");
        if (!empty($first)) {
            $tempDate = $first["date"];
            $tempDateEx = explode("-", $tempDate);
            $f = (int) $tempDateEx[1];
        }

        $res = array();
        $selectParam = $this->collectionParams[$param];
        $splitParam = explode(", ", $selectParam);
        $defaultValue = [];
        foreach ($splitParam as $item) {
            $defaultValue[$item] = 0;
        }

        for ($i = ($f - 1); $i < $m; $i++) {
            $currDay = (($i + 1) < 10) ? "0".($i + 1) : "".($i + 1);
            $temp = array(
                "date" => "$y-$currDay",
                "sum" => $defaultValue,
                "avg" => $defaultValue,
                "last" => $defaultValue,
                "first" => $defaultValue
            );

            // Get Sum
            $tempData = $this->getSUM($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["sum"][$key] = number_format($value, 2, '.', '');
            }
            // Get Avg
            $tempData = $this->getAVG($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["avg"][$key] = number_format($value, 2, '.', '');
            }
            // Get Last
            $tempData = $this->getLast($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["last"][$key] = number_format($value, 2, '.', '');
            }
            // Get First
            $tempData = $this->getFirst($dataDevice, $param, $temp["date"], "%%");
            foreach ($tempData as $key => $value) {
                $temp["first"][$key] = number_format($value, 2, '.', '');
            }
            
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
        $selectParam = $this->collectionParams[$param];
        $splitParam = explode(", ", $selectParam);
        $checker = $splitParam[0] . " != 0";
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $selectParam
            FROM `u_device_data_kwh-3-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $checker
            ORDER BY id ASC
        ")->result_array();

        return $listDataDeviceTotal;
    }

    private function getSUM($device, $param, $currentDate, $currentTime) {
        $selectParam = $this->collectionParams[$param];
        $temp = array();
        $splitParam = explode(", ", $selectParam);
        foreach ($splitParam as $item) {
            $listDataDeviceTotal = $this->customSQL->query("
                SELECT SUM($item) as total
                FROM `u_device_data_kwh-3-phase`
                WHERE `id_m_devices` = '".$device['id']."'
                AND `date` LIKE '%".$currentDate."%'
                AND `time` LIKE '".$currentTime."'
            ")->row()->total;
            $temp[$item] = (double) $listDataDeviceTotal;
        }

        return $temp;
    }

    private function getAVG($device, $param, $currentDate, $currentTime) {
        $selectParam = $this->collectionParams[$param];
        $temp = array();
        $splitParam = explode(", ", $selectParam);
        foreach ($splitParam as $item) {
            $listDataDeviceTotal = $this->customSQL->query("
                SELECT AVG($item) as total
                FROM `u_device_data_kwh-3-phase`
                WHERE `id_m_devices` = '".$device['id']."'
                AND `date` LIKE '%".$currentDate."%'
                AND `time` LIKE '".$currentTime."'
            ")->row()->total;
            $temp[$item] = (double) $listDataDeviceTotal;
        }

        return $temp;
    }

    private function getLast($device, $param, $currentDate, $currentTime) {
        $selectParam = $this->collectionParams[$param];
        $splitParam = explode(", ", $selectParam);
        $checker = $splitParam[0] . " != 0";
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $selectParam
            FROM `u_device_data_kwh-3-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            AND $checker
            ORDER BY `id` DESC
            LIMIT 1
        ")->result_array();

        $temp = array();
        foreach ($splitParam as $item) {
            $temp[$item] = (count($listDataDeviceTotal) > 0) ? (double) $listDataDeviceTotal[0][$item] : 0.0;
        }

        return $temp;
    }

    private function getFirst($device, $param, $currentDate, $currentTime) {
        $selectParam = $this->collectionParams[$param];
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT $selectParam
            FROM `u_device_data_kwh-3-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '%".$currentDate."%'
            AND `time` LIKE '".$currentTime."'
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        $temp = array();
        $splitParam = explode(", ", $selectParam);
        foreach ($splitParam as $item) {
            $temp[$item] = (count($listDataDeviceTotal) > 0) ? (double) $listDataDeviceTotal[0][$item] : 0.0;
        }

        return $temp;
    }

    private function getFirstFull($device, $currentDate) {
        $listDataDeviceTotal = $this->customSQL->query("
            SELECT *
            FROM `u_device_data_kwh-3-phase`
            WHERE `id_m_devices` = '".$device['id']."'
            AND `date` LIKE '".$currentDate."'
            ORDER BY `id` ASC
            LIMIT 1
        ")->result_array();

        return (count($listDataDeviceTotal) > 0) ? $listDataDeviceTotal[0] : null;
    }

}
