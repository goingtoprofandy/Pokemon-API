<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Views extends CI_Controller
{
    // Public Variable
    public $session;
    public $csrf_token, $auth;

    public function __construct()
    {
        parent::__construct();
    }

    // ------------------------------ PORTAL
    
    // Index
    public function index()
    {
        die(json_encode(
            array(
                "version" => "1.0",
                "message" => "Welcome to POKEMON API"
            )
        ));
    }

}
