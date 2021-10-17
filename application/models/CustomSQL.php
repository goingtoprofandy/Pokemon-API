<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CustomSQL extends CI_Model {
    // Query
    function query($sql) {
        return $this->db->query($sql);
    }

    // Log
    function log($title, $description) {
        $ipAddr = $_SERVER['REMOTE_ADDR'];
        $createdAt = date("Y-m-d H:i:s");
        $updatedAt = date("Y-m-d H:i:s");
        $this->query("
            INSERT INTO `m_logs` 
            VALUES('', '$ipAddr', '$title', '$description', '$createdAt', '$updatedAt')
        ");
    }

    // Check Valid Access
    function checkValid() {
        $token = $this->input->get_request_header('Authorization', TRUE) ?: "";

        $tempUser = $this->customSQL->query("
            SELECT * FROM `m_users`
            WHERE `token` = '$token'
        ")->result_array();

        return $tempUser;
    }

    // Get
    function get($select, $where, $table) {
        $this->db->select($select);
        $this->db->where($where);
        return $this->db->get($table);
    }

    // Create
    function create($data, $table) {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == 1) return $this->db->insert_id();
        return -1;
    }

    // Delete
    public function delete($where, $table) {
        $this->db->where($where);
        $this->db->delete($table);
        return ($this->db->affected_rows() == 1) ? 1 : -1;
    }

    // Update
    public function update($where, $data, $table) {
        $this->db->where($where);
        $this->db->update($table, $data);
        return ($this->db->affected_rows() == 1) ? 1 : -1;
    }
}