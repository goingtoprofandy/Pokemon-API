<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscribe extends CI_Controller
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

    public function index() 
    {
        print_r("HELLO WORD");
    }

    private function parser() {
        $req = $this->request->raw();
        $temp = json_decode($this->input->raw_input_stream, TRUE);

        $temp = $temp["m2m:sgn"]["m2m:nev"]["m2m:rep"]["m2m:cin"]["con"];
        $temp = json_decode($temp, TRUE);
        return $temp;
    }

    public function push($device_token)
    {
        $req = $this->parser();
        try {

            $dataDevice = $this->customSQL->query("
                SELECT * FROM `m_devices`
                WHERE `device_token` = '".$device_token."'
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

            if ($dataDevice["device_is_connect"] == 0) {
                $this->customSQL->update(
                    array("id" => $dataDevice["id"]),
                    array("device_is_connect" => 1),
                    "m_devices"
                );
            }

            switch ($dataDevice["device_type"]) {
                case 'pcb': return $this->pushPCB($req, $dataDevice);
                case 'sensors': return $this->pushSensors($req, $dataDevice);
                case 'slca': return $this->pushSLCA($req, $dataDevice);
                case 'kwh-1-phase': return $this->pushKWH1Phase($req, $dataDevice);
                case 'kwh-3-phase': return $this->pushKWH3Phase($req, $dataDevice);
                default: 
                return $this->request
                ->res(500, null, "Jenis perangkat tidak didukung", null);
            }

        } catch (Exception $e) {
            // Create Log
            $this->customSQL->log("Terjadi kesalahan", $e->getMessage());

            return $this->request
            ->res(500, null, "Sesuatu salah, terjadi kesalahan pada sisi server : " . $e->getMessage(), null);
        }
    }

    private function pushPCB($req, $device)
    {
        $mode = isset($req["mode"]) ? $req["mode"] : "OFF";
        $date = isset($req["date"]) ? $req["date"] : date("Y-m-d");
        $time = isset($req["time"]) ? $req["time"] : date("H:i:s");
        $id_m_devices = $device["id"];

        // Check Is Already Inserted
        $check = $this->customSQL->query("
            SELECT count(*) as total FROM `u_device_data_pcb`
            WHERE `id_m_devices` = '$id_m_devices'
            AND `mode` = '$mode' AND `date` = '$date' AND `time` = '$time'
        ")->row()->total;

        // Success False
        if ($check > 0)
        return $this->request
        ->res(200, null, "Data sudah ada sebelumnya", null);

        // Success True
        $checkID = $this->customSQL->create(
            array(
                "id_m_devices" => $id_m_devices,
                "mode" => $mode,
                "date" => $date,
                "time" => $time
            ),
            "u_device_data_pcb"
        );

        // Create Log
        $this->customSQL->log("Mengirim data perangkat", $device["device_name"] . " Berhasil mengirim data perangkat");

        // Response Success
        return $this->request
        ->res(200, $device, $device["device_name"] . " Berhasil mengirim data perangkat", null);
    }

    private function pushSLCA($req, $device)
    {
        $iac = isset($req["Amps RMS"]) ? $req["Amps RMS"] : 0;
        $power = isset($req["Watt"]) ? $req["Watt"] : 0;
        $date = isset($req["date"]) ? $req["date"] : date("Y-m-d");
        $time = isset($req["time"]) ? $req["time"] : date("H:i:s");
        $id_m_devices = $device["id"];

        // Check Is Already Inserted
        $check = $this->customSQL->query("
            SELECT count(*) as total FROM `u_device_data_slca`
            WHERE `id_m_devices` = '$id_m_devices'
            AND `iac` = '$iac' AND `power` = '$power' 
            AND `date` = '$date' AND `time` = '$time'
        ")->row()->total;

        // Success False
        if ($check > 0)
        return $this->request
        ->res(200, null, "Data sudah ada sebelumnya", null);

        // Success True
        $checkID = $this->customSQL->create(
            array(
                "id_m_devices" => $id_m_devices,
                "iac" => $iac,
                "power" => $power,
                "date" => $date,
                "time" => $time
            ),
            "u_device_data_slca"
        );

        // Create Log
        $this->customSQL->log("Mengirim data perangkat", $device["device_name"] . " Berhasil mengirim data perangkat");

        // Response Success
        return $this->request
        ->res(200, $device, $device["device_name"] . " Berhasil mengirim data perangkat", null);
    }

    private function pushSensors($req, $device)
    {
        $pir = isset($req["pir"]) ? $req["pir"] : 0;
        $temp = isset($req["temp"]) ? $req["temp"] : 0;
        $hum = isset($req["hum"]) ? $req["hum"] : 0;
        $lux = isset($req["lux"]) ? $req["lux"] : 0;
        $date = isset($req["date"]) ? $req["date"] : date("Y-m-d");
        $time = isset($req["time"]) ? $req["time"] : date("H:i:s");
        $id_m_devices = $device["id"];

        // Check Is Already Inserted
        $check = $this->customSQL->query("
            SELECT count(*) as total FROM `u_device_data_sensors`
            WHERE `id_m_devices` = '$id_m_devices'
            AND `pir` = '$pir' AND `temp` = '$temp' AND `hum` = '$hum' AND `lux` = '$lux' 
            AND `date` = '$date' AND `time` = '$time'
        ")->row()->total;

        // Success False
        if ($check > 0)
        return $this->request
        ->res(200, null, "Data sudah ada sebelumnya", null);

        // Success True
        $checkID = $this->customSQL->create(
            array(
                "id_m_devices" => $id_m_devices,
                "pir" => $pir,
                "temp" => $temp,
                "hum" => $hum,
                "lux" => $lux,
                "date" => $date,
                "time" => $time
            ),
            "u_device_data_sensors"
        );

        // Create Log
        $this->customSQL->log("Mengirim data perangkat", $device["device_name"] . " Berhasil mengirim data perangkat");

        // Response Success
        return $this->request
        ->res(200, $device, $device["device_name"] . " Berhasil mengirim data perangkat", null);
    }

    private function pushKWH1Phase($req, $device)
    {
        $i = isset($req["i"]) ? $req["i"] : isset($req["I"]) ? $req["I"] : 0;
        $v = isset($req["v"]) ? $req["v"] : isset($req["V"]) ? $req["V"] : 0;
        $pa = isset($req["pa"]) ? $req["pa"] : isset($req["P"]) ? $req["P"] : 0;
        $pr = isset($req["pr"]) ? $req["pr"] : isset($req["PR"]) ? $req["PR"] : 0;
        $ap = isset($req["ap"]) ? $req["ap"] : isset($req["AP"]) ? $req["AP"] : 0;
        $pf = isset($req["pf"]) ? $req["pf"] : isset($req["PF"]) ? $req["PF"] : 0;
        $f = isset($req["f"]) ? $req["f"] : isset($req["F"]) ? $req["F"] : 0;
        $kwh = isset($req["kwh"]) ? $req["kwh"] : isset($req["E"]) ? $req["E"] : 0;
        $q = isset($req["q"]) ? $req["q"] : isset($req["Q"]) ? $req["Q"] : 0;
        $s = isset($req["s"]) ? $req["s"] : isset($req["S"]) ? $req["S"] : 0;
        $date = isset($req["date"]) ? $req["date"] : date("Y-m-d");
        $time = isset($req["time"]) ? $req["time"] : date("H:i:s");
        $id_m_devices = $device["id"];

        // Check Is Already Inserted
        $check = $this->customSQL->query("
            SELECT count(*) as total FROM `u_device_data_kwh-1-phase`
            WHERE `id_m_devices` = '$id_m_devices'
            AND `i` = '$i' AND `v` = '$v' AND `pa` = '$pa' AND `pr` = '$pr' 
            AND `ap` = '$ap' AND `pf` = '$pf' AND `f` = '$f' AND `kwh` = '$kwh' 
            AND `q` = '$q' AND `s` = '$s'  
            AND `date` = '$date' AND `time` = '$time'
        ")->row()->total;

        // Success False
        if ($check > 0)
        return $this->request
        ->res(200, null, "Data sudah ada sebelumnya", null);

        // Success True
        $checkID = $this->customSQL->create(
            array(
                "id_m_devices" => $id_m_devices,
                "i" => $i,
                "v" => $v,
                "pa" => $pa,
                "pr" => $pr,
                "ap" => $ap,
                "pf" => $pf,
                "f" => $f,
                "q" => $q,
                "s" => $s,
                "kwh" => $kwh,
                "date" => $date,
                "time" => $time
            ),
            "u_device_data_kwh-1-phase"
        );

        // Create Log
        $this->customSQL->log("Mengirim data perangkat", $device["device_name"] . " Berhasil mengirim data perangkat");

        // Response Success
        return $this->request
        ->res(200, $device, $device["device_name"] . " Berhasil mengirim data perangkat", null);
    }

    private function pushKWH3Phase($req, $device)
    {
        $va = isset($req["va"]) ? $req["va"] : isset($req["Va"]) ? $req["Va"] : 0;
        $vb = isset($req["vb"]) ? $req["vb"] : isset($req["Vb"]) ? $req["Vb"] : 0;
        $vc = isset($req["vc"]) ? $req["vc"] : isset($req["Vc"]) ? $req["Vc"] : 0;
        $vab = isset($req["vab"]) ? $req["vab"] : isset($req["Vab"]) ? $req["Vab"] : 0;
        $vbc = isset($req["vbc"]) ? $req["vbc"] : isset($req["Vbc"]) ? $req["Vbc"] : 0;
        $vca = isset($req["vca"]) ? $req["vca"] : isset($req["Vca"]) ? $req["Vca"] : 0;
        $ia = isset($req["ia"]) ? $req["ia"] : isset($req["Ia"]) ? $req["Ia"] : 0;
        $ib = isset($req["ib"]) ? $req["ib"] : isset($req["Ib"]) ? $req["Ib"] : 0;
        $ic = isset($req["ic"]) ? $req["ic"] : isset($req["Ic"]) ? $req["Ic"] : 0;
        $pa = isset($req["pa"]) ? $req["pa"] : isset($req["Pa"]) ? $req["Pa"] : 0;
        $pb = isset($req["pb"]) ? $req["pb"] : isset($req["Pb"]) ? $req["Pb"] : 0;
        $pc = isset($req["pc"]) ? $req["pc"] : isset($req["Pc"]) ? $req["Pc"] : 0;
        $pt = isset($req["pt"]) ? $req["pt"] : isset($req["Pt"]) ? $req["Pt"] : 0;
        $qa = isset($req["qa"]) ? $req["qa"] : isset($req["Qa"]) ? $req["Qa"] : 0;
        $qb = isset($req["qb"]) ? $req["qb"] : isset($req["Qb"]) ? $req["Qb"] : 0;
        $qc = isset($req["qc"]) ? $req["qc"] : isset($req["Qc"]) ? $req["Qc"] : 0;
        $qt = isset($req["qt"]) ? $req["qt"] : isset($req["Qt"]) ? $req["Qt"] : 0;
        $sa = isset($req["sa"]) ? $req["sa"] : isset($req["Sa"]) ? $req["Sa"] : 0;
        $sb = isset($req["sb"]) ? $req["sb"] : isset($req["Sb"]) ? $req["Sb"] : 0;
        $sc = isset($req["sc"]) ? $req["sc"] : isset($req["Sc"]) ? $req["Sc"] : 0;
        $st = isset($req["st"]) ? $req["st"] : isset($req["St"]) ? $req["St"] : 0;
        $pfa = isset($req["pfa"]) ? $req["pfa"] : isset($req["PFa"]) ? $req["PFa"] : 0;
        $pfb = isset($req["pfb"]) ? $req["pfb"] : isset($req["PFb"]) ? $req["PFb"] : 0;
        $pfc = isset($req["pfc"]) ? $req["pfc"] : isset($req["PFc"]) ? $req["PFc"] : 0;
        $freq = isset($req["freq"]) ? $req["freq"] : isset($req["Freq"]) ? $req["Freq"] : 0;
        $ep = isset($req["ep"]) ? $req["ep"] : isset($req["Ep"]) ? $req["Ep"] : 0;
        $eq = isset($req["eq"]) ? $req["eq"] : isset($req["Eq"]) ? $req["Eq"] : 0;
        $date = isset($req["date"]) ? $req["date"] : date("Y-m-d");
        $time = isset($req["time"]) ? $req["time"] : date("H:i:s");
        $id_m_devices = $device["id"];

        // Check Is Already Inserted
        $check = $this->customSQL->query("
            SELECT count(*) as total FROM `u_device_data_kwh-3-phase`
            WHERE `id_m_devices` = '$id_m_devices'
            AND `va` = '$va' AND `vb` = '$vb' AND `vc` = '$vc'
            AND `vab` = '$vab' AND `vbc` = '$vbc' AND `vca` = '$vca'
            AND `ia` = '$ia' AND `ib` = '$ib' AND `ic` = '$ic'
            AND `pa` = '$pa' AND `pb` = '$pb' AND `pc` = '$pc'
            AND `qa` = '$qa' AND `qb` = '$qb' AND `qc` = '$qc'
            AND `sa` = '$sa' AND `sb` = '$sb' AND `sc` = '$sc'
            AND `pfa` = '$pfa' AND `pfb` = '$pfb' AND `pfc` = '$pfc'
            AND `freq` = '$freq' AND `ep` = '$ep'
            AND `date` = '$date' AND `time` = '$time'
        ")->row()->total;

        // Success False
        if ($check > 0)
        return $this->request
        ->res(200, null, "Data sudah ada sebelumnya", null);

        // Success True
        $checkID = $this->customSQL->create(
            array(
                "id_m_devices" => $id_m_devices,
                "va" => $va,
                "vb" => $vb,
                "vc" => $vc,
                "vab" => $vab,
                "vbc" => $vbc,
                "vca" => $vca,
                "ia" => $ia,
                "ib" => $ib,
                "ic" => $ic,
                "pa" => $pa,
                "pb" => $pb,
                "pc" => $pc,
                "pt" => $pt,
                "qa" => $qa,
                "qb" => $qb,
                "qc" => $qc,
                "qt" => $qt,
                "sa" => $sa,
                "sb" => $sb,
                "sc" => $sc,
                "st" => $st,
                "pfa" => $pfa,
                "pfb" => $pfb,
                "pfc" => $pfc,
                "freq" => $freq,
                "ep" => $ep,
                "eq" => $eq,
                "date" => $date,
                "time" => $time
            ),
            "u_device_data_kwh-3-phase"
        );

        // Create Log
        $this->customSQL->log("Mengirim data perangkat", $device["device_name"] . " Berhasil mengirim data perangkat");

        // Response Success
        return $this->request
        ->res(200, $device, $device["device_name"] . " Berhasil mengirim data perangkat", null);
    }

}
