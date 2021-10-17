<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload_file_helper {
    protected $config;
    protected $file_dir;

    public function __construct($config) {
        $this->config = $config;
        $this->file_dir = FCPATH . "assets/dist/img/";
    }

    // Check Size
    private function checkSize($upload_key='foo', $i = -1) {
        $defaultSize = (isset($this->config['max_size'])) ? $this->config['max_size'] : 500000;
        if ($i == -1)
            return ($_FILES[$upload_key]['size'] <= $defaultSize);
        else
            return ($_FILES[$upload_key]['size'][$i] <= $defaultSize);
    }

    // Check File Tipe
    private function checkFileType($upload_key='foo', $i = -1) {
        if ($i == -1)
            $target_file = $this->file_dir . basename($_FILES[$upload_key]["name"]);
        else
            $target_file = $this->file_dir . basename($_FILES[$upload_key]["name"][$i]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        if (isset($this->config['file_type'])) {
            $status = false;
            foreach ($this->config['file_type'] as $item) {
                if ($item == $imageFileType) $status = true;
            }
        }
        else $status = true;
        return $status;
    }

    // Do Upload
    public function do_upload($upload_key='foo') {
        if (!isset($_FILES[$upload_key]['name'])) 
            return array(
                'file_name' => null,
                'file_location' => null,
                'status' => false
            );

        $fileName = date("YmdHis") . $_FILES[$upload_key]['name'];
        $fileDir = $this->file_dir . $fileName;
        $file = $_FILES[$upload_key]['tmp_name'];
        $res = array();

        $res['file_name'] = $fileName;
        $res['file_location'] = $fileDir;
        $res['status'] = false;

        if ($this->checkSize($upload_key) && $this->checkFileType($upload_key)) {
            if (move_uploaded_file($file, $fileDir)) {
                $res['file_name'] = $fileName;
                $res['file_location'] = $fileDir;
                $res['status'] = true;
            }
        }

        return $res;
    }
    
    public function do_multiple_upload($upload_key='foo') {
        if (!isset($_FILES[$upload_key]['name'])) 
            return array(
                'file_name' => null,
                'file_location' => null,
                'status' => false
            );

        $res_multiple = array();
        for($i = 0; $i < count($_FILES[$upload_key]['name']); $i++) {
            $fileName = date("YmdHis") . $_FILES[$upload_key]['name'][$i];
            $fileDir = $this->file_dir . $fileName;
            $file = $_FILES[$upload_key]['tmp_name'][$i];
            $res = array();

            $res['file_name'] = $fileName;
            $res['file_location'] = $fileDir;
            $res['status'] = false;

            if ($this->checkSize($upload_key, $i) && $this->checkFileType($upload_key, $i)) {
                if (move_uploaded_file($file, $fileDir)) {
                    $res['file_name'] = $fileName;
                    $res['file_location'] = $fileDir;
                    $res['status'] = true;
                }
            }
            $res_multiple[] = $res;
        }

        return $res_multiple;
    }
}
