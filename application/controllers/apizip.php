<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ApiZip extends CI_Controller
{
    private $hash_key = "inspection_front_user";
    const FILESIZE = 26214400; // 10MB
    private $status = array(
        array('code' => 0, 'message' => 'Success'), // 0
        array('code' => 1, 'message' => 'Failed'), // 1
        array('code' => -1, 'message' => 'Bad Credential'), // 2
        array('code' => -2, 'message' => 'Bad Request'), // 3
        array('code' => 2, 'message' => 'Non Exist User'), // 4
        array('code' => 3, 'message' => 'Wrong Password'), // 5
        array('code' => 4, 'message' => 'You haven\'t permission'), // 6
        array('code' => 5, 'message' => 'Can\'t open file'), // 7
        array('code' => 6, 'message' => 'Unknown Device'), // 8
        array('code' => 7, 'message' => 'Already exist'), // 9
        array('code' => 8, 'message' => 'Please wait until activated!'), // 10
    );

    public function __construct()
    {
        parent::__construct();

        if ($_POST) {
            $this->param = $_POST;
        } else {
            $this->param = json_decode(file_get_contents("php://input"), true);
        }
    }

    public function delete_all()
    {
        $dir_path = "resource/upload/report/";
        $files = glob($dir_path . '*');
        $this->load->helper('file');
        /*
        foreach ($files as $file) {
            if (is_file($file)) {
                if (strpos($file, 'water_intrusion_') !== false || strpos($file, 'report_')) {
                    link($file);
                }
            }
        }
        */
        delete_files($dir_path);

    }
}