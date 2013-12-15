<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api extends CI_Controller
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

        $this->load->library('uuid');
        $this->load->library('m_pdf');

        $this->load->model('user_model');
        $this->load->model('utility_model');
        $this->load->model('datatable_model');

        $this->load->library('mailer/phpmailerex');
        $this->load->library('m_twilio');
        $this->load->helper('csv');

        $this->load->library('upload');
        //  error_reporting(E_ALL);
        //  error_reporting(E_ALL ^ E_NOTICE);
        //ini_set('display_errors', 1);

        if ($_POST) {
            $this->param = $_POST;
        } else {
            $this->param = json_decode(file_get_contents("php://input"), true);
        }
    }

    public function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    // version 1.0
    public function v1($method = '', $param = '', $kind = '')
    {
        $response = array(
            'status' => $this->status[1],
            'request' => array(
                'method' => $method,
                'param' => $param,
                'kind' => $kind,
                'data' => array()
            ),
            'response' => array()
        );


        $request_data = array();
        $result_data = array();

        if ($method == 'user') {
            $t = mdate('%Y%m%d%H%i%s', time());

            if ($param == 'register') {
                $first_name = $this->input->get_post('first_name');
                $last_name = $this->input->get_post('last_name');
                $email = $this->input->get_post('email');
                $phone_number = $this->input->get_post('phone_number');
                $password = $this->input->get_post('password');

                if ($email === false || $password === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $user = $this->utility_model->get('ins_user', array('email' => $email));
                    if ($user) {
                        $response['status'] = $this->status[9];
                    } else {
                        $ip = $this->get_client_ip();

                        if ($this->utility_model->insert('ins_user', array('email' => $email, 'ip_address' => $ip, 'phone_number' => $phone_number, 'first_name' => $first_name, 'last_name' => $last_name, 'password' => sha1($password . $this->hash_key), 'created_at' => $t, 'updated_at' => $t))) {
                            $response['status'] = $this->status[0];
                            $result_data = $this->utility_model->get('ins_user', array('email' => $email));

                            $mail_subject = "New Inspector is registered";
                            $mail_body = " First Name: " . $result_data['first_name'] . "\n"
                                . " Last Name: " . $result_data['last_name'] . "\n"
                                . " Email Address: " . $result_data['email'] . "\n"
                                . " Phone Number: " . $result_data['phone_number'] . "\n"
                                . "\n"
                                . " Please login admin panel and check this user. \n"
                                . " " . base_url() . " \n\n"
                                . " Regards."
                                . "\n";

                            $sender = $this->utility_model->get_list('ins_admin', array('kind' => 1, 'allow_email' => 1));
                            $this->send_mail($mail_subject, $mail_body, $sender, false);
                        } else {
                            $response['status'] = $this->status[1];
                        }
                    }
                }
            } elseif ($param == 'update') {
                $first_name = $this->input->get_post('first_name');
                $last_name = $this->input->get_post('last_name');
                $email = $this->input->get_post('email');
                $phone_number = $this->input->get_post('phone_number');
                $old_password = $this->input->get_post('old_password');
                $password = $this->input->get_post('new_password');
                $address = $this->input->get_post('address');

                if ($email === false || $password === false || $old_password === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $user = $this->utility_model->get('ins_user', array('email' => $email));
                    if ($user) {
                        if (sha1($old_password . $this->hash_key) == $user['password']) {
                            $ip = $this->get_client_ip();

                            if ($this->utility_model->update('ins_user', array('address' => $address, 'email' => $email, 'ip_address' => $ip, 'phone_number' => $phone_number, 'first_name' => $first_name, 'last_name' => $last_name, 'password' => sha1($password . $this->hash_key), 'updated_at' => $t), array('email' => $email))) {
                                $response['status'] = $this->status[0];
                                $result_data = $this->utility_model->get('ins_user', array('email' => $email));
                            } else {
                                $response['status'] = $this->status[1];
                            }
                        } else {
                            $response['status'] = $this->status[5];
                        }
                    } else {
                        $response['status'] = $this->status[4];
                    }
                }
            } elseif ($param == 'login') {
                $email = $this->input->get_post('email');
                $password = $this->input->get_post('password');

                if ($email === false || $password === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $user = $this->utility_model->get('ins_user', array('email' => $email));
                    if ($user) {
                        if (sha1($password . $this->hash_key) == $user['password']) {
                            if ($user['status'] == '0') {
                                $response['status'] = $this->status[10];
                            } else {
                                $response['status'] = $this->status[0];
                                $result_data = $user;
                            }
                        } else {
                            $response['status'] = $this->status[5];
                        }
                    } else {
                        $response['status'] = $this->status[4];
                    }
                }
            } elseif ($param == 'sign') {
                $email = $this->input->get_post('email');

                if ($email === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $user = $this->utility_model->get('ins_user', array('email' => $email));
                    if ($user) {
                        if ($user['status'] == '0') {
                            $response['status'] = $this->status[10];
                        } else {
                            $response['status'] = $this->status[0];
                            $result_data = $user;
                        }
                    } else {
                        $response['status'] = $this->status[4];
                    }
                }
            } elseif ($param == 'field_manager') {
                $region = $this->input->get_post('region');

                if ($region === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $region = $this->utility_model->decode($region);

                    $user = $this->utility_model->get_list('ins_admin', array('kind' => 2, 'status' => '1', 'region' => $region));
                    if ($user) {
                        $result_data['user'] = $user;
                    } else {
                        $result_data['user'] = array();
                    }

                    $response['status'] = $this->status[0];
                }
            } else {
                $response['status'] = $this->status[2];
            }
        } elseif ($method == 'inspection') {

            if ($kind == 'drainage' || $kind == 'lath') {
                $type = $kind == 'drainage' ? 1 : 2;

                // $user_id = $this->input->get_post('user_id');
                $user_id = $this->param['user_id'];

                if (empty($user_id)) {
                    $response['status'] = $this->status[3];
                } else {
                    // $user_id = $this->utility_model->decode($user_id);
                    $user = $this->utility_model->get('ins_user', array('id' => $user_id));
                    if ($user) {
                        if ($param == 'check') {
                            $job = $this->input->get_post('job');
                            $is_building_unit = $this->param['is_building_unit'];
                            if ($is_building_unit === false || $is_building_unit == "") {
                                $is_building_unit = "0";
                            }

                            $address = $this->param['address'];
                            if ($address === false) {
                                $address = "";
                            }

                            $edit_inspection_id = $this->param['inspection_id'];
                            if ($edit_inspection_id === false || $edit_inspection_id == "0") {
                                $edit_inspection_id = "";
                            }

                            if ($job === false) {
                                $response['status'] = $this->status[3];
                            } else {
                                $sql_t1 = "select a.*, u.id as manager_id from ins_building a "
                                    . " left join ins_admin u on concat(u.first_name, ' ', u.last_name)=a.field_manager and u.kind=2 "
                                    . " where replace(a.job_number,'-','')=replace('$job','-','') "
                                    . " order by a.updated_at desc limit 1";
                                $schedule = $this->utility_model->get__by_sql($sql_t1);
                                if ($schedule) {
                                    $result_data['is_schedule'] = 1;
                                    $result_data['schedule'] = $schedule;
                                    $result_data['schedule_sql'] = $sql_t1;
                                } else {
                                    $result_data['is_schedule'] = 0;
                                }

                                if ($is_building_unit == "1" && $address != "") {
                                    $result_data['is_bu'] = 1;
                                } else {
                                    $result_data['is_bu'] = 0;
                                }

                                $result_data['is_initials'] = 0;
                                if ($kind == 'lath') {
                                    $pass_drainage = $this->utility_model->get_count__by_sql("select a.* from ins_inspection a where replace(a.job_number,'-','')=replace('$job','-','') and ( a.result_code=1 or a.result_code=2 ) and a.type=1");     // drainage inspection with pass or pass with exception
                                    if ($pass_drainage > 0) {
                                        //                                        $result_data['is_initials'] = 0;
                                    } else {
                                        $result_data['is_initials'] = 1;
                                    }
                                }

                                $result_data['is_exist'] = 0;
                                $sql = " select "
                                    . " a.id, a.user_id, a.type, a.job_number, a.community, a.lot, a.address as addr, a.start_date as date, a.end_date as date_l, a.initials as init, a.region, a.field_manager as fm, "
                                    . " a.latitude, a.longitude, a.accuracy, a.house_ready as ready, a.overall_comments as overall, a.image_front_building,a.image_right_building,a.image_left_building,a.image_back_building,a.image_front_building_2, a.image_signature, "
                                    . " a.is_first, a.is_initials, "
                                    . " a.result_code as result, "
                                    . " a.city, a.area, a.volume, a.qn, a.wall_area as w_area, a.ceiling_area as c_area, a.design_location as des_loc, "
                                    . " a.image_testing_setup as setup, a.image_manometer as mano, "
                                    . " a.house_pressure  as pressure, a.flow, "
                                    . " a.qn_out, a.ach50, a.result_duct_leakage as duct_leakage, a.result_envelop_leakage as envelop_leakage "
                                    . " from ins_inspection a "
                                    . " where ";

                                if ($edit_inspection_id != "") {
                                    $sql .= " a.id='$edit_inspection_id' ";
                                } else {
                                    $sql .= " replace(a.job_number,'-','')=replace('$job','-','') and a.type='$type' ";

                                    if ($result_data['is_bu'] == 1) {
                                        $sql .= " and a.address='$address' and a.is_building_unit=1 ";
                                    }

                                    $sql .= " order by a.start_date desc, a.id desc "
                                        . " limit 1 ";
                                }

                                $inspection = $this->utility_model->get__by_sql($sql);

                                if ($inspection) {
                                    $result_data['is_exist'] = 1;

                                    $inspection['loc'] = array(
                                        'lat' => $inspection['latitude'],
                                        'lon' => $inspection['longitude'],
                                        'acc' => $inspection['accuracy'],
                                    );

                                    $inspection['is_exist'] = $inspection['is_first'] == '1' ? "0" : "1";

                                    if (isset($inspection['image_front_building']) && $inspection['image_front_building'] != "") {
//                                        $inspection['front'] = array(
//                                            'mode' => 2,
//                                            'img' => $inspection['image_front_building'],
//                                        );
                                        $inspection['front'] = "";
                                    } else {
                                        $inspection['front'] = "";
                                    }

                                    if (isset($inspection['image_signature']) && $inspection['image_signature'] != "") {
                                        $inspection['sign'] = array(
                                            'mode' => 2,
                                            'img' => $inspection['image_signature'],
                                        );
                                    } else {
                                        $inspection['sign'] = "";
                                    }

                                    if (isset($inspection['image_right_building']) && $inspection['image_right_building'] != "") {
                                        $inspection['right'] = array(
                                            'mode' => 2,
                                            'img' => $inspection['image_right_building'],
                                        );
                                    } else {
                                        $inspection['right'] = "";
                                    }

                                    if (isset($inspection['image_left_building']) && $inspection['image_left_building'] != "") {
                                        $inspection['left'] = array(
                                            'mode' => 2,
                                            'img' => $inspection['image_left_building'],
                                        );
                                    } else {
                                        $inspection['left'] = "";
                                    }

                                    if (isset($inspection['image_back_building']) && $inspection['image_back_building'] != "") {
                                        $inspection['back'] = array(
                                            'mode' => 2,
                                            'img' => $inspection['image_back_building'],
                                        );
                                    } else {
                                        $inspection['back'] = "";
                                    }


                                    if (isset($inspection['image_front_building_2']) && $inspection['image_front_building_2'] != "") {
                                        $inspection['front2'] = array(
                                            'mode' => 2,
                                            'img' => $inspection['image_front_building_2'],
                                        );
                                    } else {
                                        $inspection['front2'] = "";
                                    }

                                    $inspection['ex1'] = "";
                                    $inspection['ex2'] = "";
                                    $inspection['ex3'] = "";
                                    $inspection['ex4'] = "";

                                    $exception_images = $this->utility_model->get_list('ins_exception_image', array('inspection_id' => $inspection['id']));
                                    if ($exception_images) {
                                        $i = 1;
                                        foreach ($exception_images as $row) {
                                            if (isset($row['image']) && $row['image'] != "") {
                                                $inspection['ex' . $i] = array(
                                                    'mode' => 2,
                                                    'img' => $row['image'],
                                                );
                                            } else {
                                                //                                                $inspection['ex' . $i] = "";
                                            }

                                            $i++;
                                        }
                                    }

                                    $result_email = array();
                                    $emails = $this->utility_model->get_list('ins_recipient_email', array('inspection_id' => $inspection['id'], 'status' => '0'));
                                    if ($emails) {
                                        foreach ($emails as $row) {
                                            array_push($result_email, $row['email']);
                                        }
                                    }

                                    $location = array(
                                        'left' => $this->get_location($inspection['id'], 'Left', $type),
                                        'right' => $this->get_location($inspection['id'], 'Right', $type),
                                        'front' => $this->get_location($inspection['id'], 'Front', $type),
                                        'back' => $this->get_location($inspection['id'], 'Back', $type),
                                    );

                                    $result_data['inspection'] = $inspection;
                                    $result_data['email'] = $result_email;
                                    $result_data['location'] = $location;

                                    $result_data['comment'] = $this->get_comment($inspection['id']);
                                } else {
                                    // $inspection = $this->utility_model->get__by_sql("select a.id, a.user_id, a.type, a.job_number, a.community, a.lot, b.address as addr, a.start_date as date, a.end_date as date_l, a.initials as init, a.region, a.field_manager as fm, a.latitude, a.longitude, a.accuracy, a.house_ready as ready, a.overall_comments as overall, a.image_front_building, a.image_signature, a.is_first, a.is_initials, a.result_code as result
                                    //                                                 from ins_schedule b
                                    //                                                 left join ins_inspection a
                                    //                                                 on replace(b.job_number,'-','')=replace(a.job_number,'-','')
                                    //                                                 where replace(b.job_number,'-','')=replace('$job', '-','') order by a.created_at desc limit 1");
                                    // $result_data['inspection'] = $inspection;
                                }

                                $response['status'] = $this->status[0];
                            }
                        } elseif ($param == 'submit') {
                            $req = $this->param;
                            $app_version = $this->param['version'];
                            if ($app_version === false || $app_version == "") {
                                $app_version = "1.0";
                            }

                            if ($req === false) {
                                $response['status'] = $this->status[3];
                            } else {
                                $ip = $this->get_client_ip();
                                $t = mdate('%Y%m%d%H%i%s', time());

                                //$obj = json_decode($req);
                                $obj = json_decode(json_encode($this->param), FALSE);

                                $requested_inspection_id = $obj->requested_id;
                                $edit_inspection_id = isset($obj->inspection_id) ? $obj->inspection_id : "";
                                if ($edit_inspection_id === 0 || $edit_inspection_id === "0") {
                                    $edit_inspection_id = "";
                                }

                                $data = array(
                                    'user_id' => $user_id,
                                    'type' => $type,
                                    'job_number' => $obj->job_number,
                                    'lot' => $obj->lot,
                                    'community' => $obj->community,
                                    'address' => $obj->address,
                                    'initials' => $obj->initials,
                                    'region' => $obj->region ? $obj->region : 0,
                                    'field_manager' => $obj->field_manager,
                                    'latitude' => $obj->latitude,
                                    'longitude' => $obj->longitude,
                                    'accuracy' => $obj->accuracy,
                                    'image_front_building' => $obj->front_building ? $obj->front_building : "",
                                    'image_right_building' => $obj->right_building ? $obj->right_building : " ",
                                    'image_left_building' => $obj->left_building ? $obj->left_building : " ",
                                    'image_back_building' => $obj->back_building ? $obj->back_building : " ",
                                    'image_front_building_2' => $obj->front_building_2 ? $obj->front_building_2 : " ",
                                    'house_ready' => $obj->house_ready,
                                    'overall_comments' => $obj->overall_comments,
                                    'result_code' => $obj->result_code,
                                    'image_signature' => $obj->signature,
                                    'is_first' => $obj->is_first,
                                    'is_initials' => $obj->is_initials,
                                    'ip_address' => $ip,
                                    'created_at' => $t,
                                    'requested_id' => $requested_inspection_id,
                                    'app_version' => $app_version,
                                );

                                if ($edit_inspection_id != "") {

                                } else {
                                    $data['start_date'] = date('Y-m-d', time()); // $obj->start_date,
                                    $data['end_date'] = date('Y-m-d', time()); // $obj->start_date,
                                }

                                if ($edit_inspection_id != "") {

                                } else {
                                    if (isset($obj->is_building_unit)) {
                                        $data['is_building_unit'] = $obj->is_building_unit;
                                        $old_inspection = $this->utility_model->get('ins_inspection', array('type' => $type, 'job_number' => $obj->job_number, 'address' => $obj->address, 'is_building_unit' => 1));
                                        if ($old_inspection) {

                                        } else {
                                            $data['first_submitted'] = 1;
                                        }
                                    } else {
                                        $old_inspection = $this->utility_model->get('ins_inspection', array('type' => $type, 'job_number' => $obj->job_number));
                                        if ($old_inspection) {

                                        } else {
                                            $data['first_submitted'] = 1;
                                        }
                                    }
                                }

                                $inspection_id = false;
                                $this->utility_model->start();

                                if ($edit_inspection_id != "") {
                                    if ($this->utility_model->update('ins_inspection', $data, array('id' => $edit_inspection_id))) {
                                        $inspection_id = $edit_inspection_id;
                                    }
                                } else {
                                    if ($this->utility_model->insert('ins_inspection', $data)) {
                                        $inspection_id = $this->utility_model->new_id();
                                    }
                                }

                                if ($inspection_id !== false) {
                                    $this->utility_model->delete('ins_exception_image', array('inspection_id' => $inspection_id));
                                    if (isset($obj->exception_1) && $obj->exception_1 != "") {
                                        $this->utility_model->insert('ins_exception_image', array('inspection_id' => $inspection_id, 'image' => $obj->exception_1));
                                    }
                                    if (isset($obj->exception_2) && $obj->exception_2 != "") {
                                        $this->utility_model->insert('ins_exception_image', array('inspection_id' => $inspection_id, 'image' => $obj->exception_2));
                                    }
                                    if (isset($obj->exception_3) && $obj->exception_3 != "") {
                                        $this->utility_model->insert('ins_exception_image', array('inspection_id' => $inspection_id, 'image' => $obj->exception_3));
                                    }
                                    if (isset($obj->exception_4) && $obj->exception_4 != "") {
                                        $this->utility_model->insert('ins_exception_image', array('inspection_id' => $inspection_id, 'image' => $obj->exception_4));
                                    }

                                    $this->utility_model->delete('ins_recipient_email', array('inspection_id' => $inspection_id));

                                    if (count($obj->emails) > 0) {
                                        $obj_email = json_decode($obj->emails);
                                        foreach ($obj_email as $row) {
                                            $this->utility_model->insert('ins_recipient_email', array('inspection_id' => $inspection_id, 'email' => $row));
                                        }
                                    }

                                    $this->utility_model->delete('ins_location', array('inspection_id' => $inspection_id));
                                    $this->utility_model->delete('ins_checklist', array('inspection_id' => $inspection_id));


                                    if (count($obj->locations) > 0) {
                                        // print_r($obj->locations);
                                        $obj1 = json_decode($obj->locations);
                                        foreach ($obj1 as $row) {

                                            if ($this->utility_model->insert('ins_location', array('inspection_id' => $inspection_id, 'name' => $row->name))) {
                                                $location_id = $this->utility_model->new_id();

                                                if (count($row->checklist) > 0) {
                                                    foreach ($row->checklist as $row1) {

                                                        $this->utility_model->insert('ins_checklist', array('inspection_id' => $inspection_id, 'location_id' => $location_id, 'no' => $row1->no, 'status' => $row1->status, 'primary_photo' => $row1->primary, 'secondary_photo' => $row1->secondary, 'description' => $row1->description));
                                                    }
                                                }
                                            }
                                        }

                                    }

                                    $this->utility_model->delete('ins_inspection_comment', array('inspection_id' => $inspection_id));
                                    $obj_comments = json_decode($obj->comments);
                                    if (count($obj->comments) > 0) {
                                        foreach ($obj_comments as $row1) {
                                            $this->utility_model->insert('ins_inspection_comment', array('inspection_id' => $inspection_id, 'no' => $row1->no, 'status' => $row1->status, 'primary_photo' => $row1->primary, 'secondary_photo' => $row1->secondary, 'description' => $row1->description));
                                        }
                                    }

                                    $today = mdate('%Y-%m-%d', time());
                                    $this->utility_model->update('ins_inspection_requested', array('status' => 2, 'completed_at' => $today), array('id' => $requested_inspection_id));

                                    $this->utility_model->complete();

                                    $result_data['inspection_id'] = $inspection_id;
                                    $response['status'] = $this->status[0];
                                } else {
                                    $response['status'] = $this->status[1];
                                }
                            }
                        } else {
                            $response['status'] = $this->status[2];
                        }
                    } else {
                        $response['status'] = $this->status[4];
                    }
                }
            } elseif ($kind == 'wci' || $kind == 'pulte_duct') {
                if ($kind == 'wci') {
                    $type = 3;
                } else {
                    $type = 4;
                }

                $user_id = $this->param['user_id'];


                if (empty($user_id)) {
                    $response['status'] = $this->status[3];
                } else {
                    // $user_id = $this->utility_model->decode($user_id);
                    $user = $this->utility_model->get('ins_user', array('id' => $user_id));
                    if ($user) {
                        if ($param == 'check') {
                            //$response['ret1'] = $param;
                        } elseif ($param == 'submit') {
                            $req = $this->param;
                            $app_version = $this->param['version'];
                            if ($app_version === false || $app_version == "") {
                                $app_version = "1.0";
                            }

                            if ($req === false) {
                                $response['status'] = $this->status[3];
                            } else {
                                $ip = $this->get_client_ip();
                                $t = mdate('%Y%m%d%H%i%s', time());

                                // $obj = json_decode($req);
                                $obj = json_decode(json_encode($this->param), FALSE);
                                $requested_inspection_id = $obj->requested_id;

                                $data = array(
                                    'permit_number' => $obj->permit_number,
                                    'user_id' => $user_id,
                                    'type' => $type,
                                    'job_number' => $obj->job_number,
                                    'lot' => $obj->lot,
                                    'community' => $obj->community,
                                    'address' => $obj->address,
                                    'start_date' => date('Y-m-d', time()), // $obj->start_date, // date("m/d/Y", strtotime($obj->start_date)),
                                    'end_date' => date('Y-m-d', time()), //$obj->end_date, // date("m/d/Y", strtotime($obj->end_date)),
//                                    'initials' => $obj->initials,
                                    'region' => $obj->region ? $obj->region : 0,
                                    'field_manager' => $obj->field_manager,
                                    'latitude' => $obj->latitude,
                                    'longitude' => $obj->longitude,
                                    'accuracy' => $obj->accuracy,
                                    'image_front_building' => $obj->front_building,
                                    'image_right_building' => '',
                                    'image_left_building' => '',
                                    'image_back_building' => '',
                                    'house_ready' => $obj->house_ready,
                                    'overall_comments' => $obj->overall_comments,
//                                    'result_code' => $obj->result_code,
                                    'image_signature' => $obj->signature,
//                                    'is_first' => $obj->is_first,
//                                    'is_initials' => $obj->is_initials,
                                    'ip_address' => $ip,
                                    'created_at' => $t,
                                    'requested_id' => $requested_inspection_id,
                                    'city' => $obj->city,
                                    'area' => $obj->area,
                                    'volume' => $obj->volume,
                                    'qn' => $obj->qn,
                                    'wall_area' => $obj->wall_area,
                                    'ceiling_area' => $obj->ceiling_area,
                                    'design_location' => $obj->design_location,
                                    'image_testing_setup' => $obj->testing_setup,
                                    'image_manometer' => $obj->manometer,
                                    'house_pressure' => $obj->house_pressure,
                                    'flow' => $obj->flow,
                                    'result_duct_leakage' => $obj->result_duct_leakage,
                                    'result_envelop_leakage' => $obj->result_envelop_leakage,
                                    'qn_out' => $obj->qn_out,
                                    'ach50' => $obj->ach50,
                                    'app_version' => $app_version,
                                );

                                if (isset($obj->is_building_unit)) {
                                    $data['is_building_unit'] = $obj->is_building_unit;

                                    $old_inspection = $this->utility_model->get('ins_inspection', array('type' => $type, 'job_number' => $obj->job_number, 'address' => $obj->address, 'is_building_unit' => 1));
                                    if ($old_inspection) {

                                    } else {
                                        $data['first_submitted'] = 1;
                                    }
                                } else {
                                    $old_inspection = $this->utility_model->get('ins_inspection', array('type' => $type, 'job_number' => $obj->job_number));
                                    if ($old_inspection) {

                                    } else {
                                        $data['first_submitted'] = 1;
                                    }
                                }

                                if ($this->utility_model->insert('ins_inspection', $data)) {
                                    $inspection_id = $this->utility_model->new_id();
                                    $obj_unit = json_decode($obj->unit);
                                    if (count($obj->unit) > 0) {
                                        foreach ($obj_unit as $row) {
                                            $this->utility_model->insert('ins_unit', array('inspection_id' => $inspection_id, 'no' => $row->no, 'supply' => $row->supply, 'return' => $row->return));
                                        }
                                    }

                                    $today = mdate('%Y-%m-%d', time());
                                    $this->utility_model->update('ins_inspection_requested', array('status' => 2, 'completed_at' => $today), array('id' => $requested_inspection_id));

                                    $result_data['inspection_id'] = $inspection_id;
                                    $response['status'] = $this->status[0];
                                } else {

                                    $response['status'] = $this->status[1];
                                }
                            }
                        } else {
                            $response['status'] = $this->status[2];
                        }
                    } else {
                        $response['status'] = $this->status[4];
                    }
                }
            } elseif ($param == 'requested') {
                $user_id = $this->input->get_post('user_id');
                $requested_date = $this->input->get_post('date');

                $response['requested_date'] = $requested_date;

                if ($requested_date === false) {
                    $requested_date = "";
                }

                if ($user_id === false) {
                    $response['status'] = $this->status[3];

                } else {
                    // $user_id = $this->utility_model->decode($user_id);
                    $user_id = $this->input->get_post('user_id');
                    $response['user_id'] = $user_id;

                    $user = $this->utility_model->get('ins_user', array('id' => $user_id));


                    if ($user) {

                        $table = " ins_inspection_requested a "
                            . " left join ins_community c on c.community_name=a.community_name "
//                               . " left join ins_region r on c.region=r.id "
                            . " left join ins_admin m on a.manager_id=m.id "
                            . " ";

                        $sql = " select u.first_name AS e3_inpestion_first,u.last_name AS e3_inpestion_last , a.first_name, a.cell_phone, a.zip, a.state, a.email, a.id, a.category, a.reinspection, a.epo_number, a.job_number, a.lot, a.requested_at,a.permit_number, "
                            . " a.assigned_at, a.completed_at, a.manager_id, a.inspector_id, a.reassigned, "
                            . " a.time_stamp, a.ip_address, a.community_name, a.lot, a.address, a.status, a.area, a.volume, a.qn, a.is_building_unit, "
                            . " a.city as city_duct, a.wall_area, a.ceiling_area, a.design_location,a.close_escrow_date,a.start_date_requested,a.end_date_requested,a.access_instructions, "
                            . " a.inspection_id as edit_inspection_id, "
//                                . " concat(m.first_name, ' ', m.last_name) as field_managenoasr_name, "
//                                . " c1.name as category_name, "
                            . " c.community_id, c.city, c.region, "
//                                . " r.region as region_name, "
//                                . " u.first_name, u.last_name "
                            . " a.base_ach,a.leakage_type "
                            . " from ins_user u, " . $table . " where u.id=a.inspector_id and a.inspector_id='" . $user_id . "' ";

                        if (false) {

                            $vartime = strtotime("$requested_date 00:00:00"); // 2016-05-12 16:43:30
                            $first = date('Y-m-d H:i:s', strtotime("7 day", $vartime));
                            $date_7days = substr($first, 0, 10);
                            $sql .= " and ( a.requested_at >= '$requested_date' and a.requested_at <= '$date_7days' )";
                        }
                        if ($requested_date != "") {
                            $vartime = strtotime("$requested_date 00:00:00"); // 2016-05-12 16:43:30
                            $first = date('Y-m-d H:i:s', strtotime("7 day", $vartime));
                            $date_7days = substr($first, 0, 10);
                            $sql .= " and a.requested_at = '$requested_date'";
                        }

                        $sql .= " order by a.requested_at asc, a.job_number asc ";
                        $response['sql'] = $sql;
                        $requested_list = $this->utility_model->get_list__by_sql($sql);
                        $requested_list2 = array();
                        if (count($requested_list) > 0 && $requested_date != "") {
                            $community_ids = array();
                            $in_sql = "(";
                            foreach ($requested_list as $key => $value) {
                                $community_ids[] = $value['community_id'];
                                $in_sql = $in_sql . "'" . $value['community_id'] . "',";
                            }
                            $in_sql = substr($in_sql, 0, strlen($in_sql) - 1);
                            $in_sql = $in_sql . ")";
                            $table = " ins_inspection_requested a "
                                . " left join ins_community c on c.community_name=a.community_name "
                                //                               . " left join ins_region r on c.region=r.id "
                                . " left join ins_admin m on a.manager_id=m.id "
                                . " ";

                            $sql = " select  a.first_name, a.cell_phone, a.zip, a.state, a.email, a.id, a.category, a.reinspection, a.epo_number, a.job_number, a.lot, a.requested_at, a.permit_number,"
                                . " a.assigned_at, a.completed_at, a.manager_id, a.inspector_id, a.reassigned,  "
                                . " a.time_stamp, a.ip_address, a.community_name, a.lot, a.address, a.status, a.area, a.volume, a.qn, a.is_building_unit, "
                                . " a.city as city_duct, a.wall_area, a.ceiling_area, a.design_location,a.close_escrow_date,a.start_date_requested,a.end_date_requested,a.access_instructions, "
                                . " a.inspection_id as edit_inspection_id, "
                                //                                . " concat(m.first_name, ' ', m.last_name) as field_managenoasr_name, "
                                //                                . " c1.name as category_name, "
                                . " c.community_id, c.city, m.region, "
                                //                                . " r.region as region_name, "
                                //                                . " u.first_name, u.last_name "
                                . " a.base_ach,a.leakage_type "
                                . " from " . $table
                                . " where c.community_id in $in_sql"
                                . " and (a.category = 3 or a.category = 4 or a.category = 1 or a.category = 2)";

                            $vartime = strtotime("$requested_date 00:00:00"); // 2016-05-12 16:43:30
                            $first = date('Y-m-d H:i:s', strtotime("7 day", $vartime));
                            $date_7days = substr($first, 0, 10);
                            $sql .= " and ( a.requested_at > '$requested_date' and a.requested_at <= '$date_7days' )";

                            $sql .= " order by a.requested_at asc, a.job_number asc ";
                            $response['sql2'] = $sql;
                            $requested_list2 = $this->utility_model->get_list__by_sql($sql);
                        }

                        $temp_list_temp = $requested_list;
                        $requested_list = array();
                        foreach ($temp_list_temp as $key => $value) {
                            if ($value['status'] == 1) {
                                $requested_list[] = $value;
                            }
                        }

                        $result_data = array_merge($requested_list, $requested_list2);
                        $list_temp = array();
                        foreach ($result_data as $key => $value) {
                            $id = $value['id'];
                            $list_temp[$id] = $value;
                        }
                        $result_data = array();
                        foreach ($list_temp as $key => $value) {
                            $result_data[] = $value;
                        }

                        $response['status'] = $this->status[0];

                    }


                }
            } else {

                $response['status'] = $this->status[2];
            }
        } elseif ($method == 'send') {
            $user_id = $this->input->get_post('user_id');
            $inspection_id = $this->input->get_post('inspection_id');

            if ($user_id !== false && $inspection_id !== false) {
                $user_id = $this->utility_model->decode($user_id);
                $inspection_id = $this->utility_model->decode($inspection_id);
//                $user_id= 2;
//                $inspection_id = 14977;

                $inspection = $this->utility_model->get('ins_inspection', array('user_id' => $user_id, 'id' => $inspection_id));
                if ($inspection) {
                    $report = $this->send_report($user_id, $inspection_id);
                    if ($report === false) {
                        $response['status'] = $this->status[1];
                        $response['flow'] = 4;
                    } else {
                        //                        $result_data['email'] = $report;
                        $response['status'] = $this->status[0];
                        $response['flow'] = 3;
                    }
                } else {
                    $response['status'] = $this->status[3];
                    $response['flow'] = 2;
                }
            } else {
                $response['status'] = $this->status[3];
                $response['flow'] = 1;
            }
        } elseif ($method == 'community') {
            if ($param == 'check') {
                $community_id = $this->input->get_post('community_id');
                if ($community_id !== false) {
                    $community = $this->utility_model->get__by_sql(" select a.* from ins_community a, ins_region r where a.community_id='$community_id' and r.id=a.region ");
                    if ($community) {
                        $result_data['region'] = $community['region'];
                        $result_data['community_name'] = $community["community_name"];
                    } else {
                        $result_data['region'] = 0;
                        $result_data['community_name'] = "";
                        $result_data['regions'] = $this->utility_model->get_list('ins_region', array());
                    }

                    $t = mdate('%m/%d/%Y', time());
                    $result_data['date'] = $t;

                    $response['status'] = $this->status[0];
                } else {
                    $response['status'] = $this->status[3];
                }
            } else {
                $response['status'] = $this->status[3];
            }
        } elseif ($method == 'sync') {
            if ($param == 'region') {
                $ids = $this->input->get_post('ids');
                if ($ids === false || !is_array($ids)) {
                    $result_data['region'] = $this->utility_model->get_list('ins_region', array());
                    $result_data['delete'] = array();
                } else {
                    $result_data['region'] = $this->utility_model->get_list('ins_region', array());
                    $result_data['delete'] = array();

                    foreach ($ids as $row) {
                        $region = $this->utility_model->get('ins_region', array('id' => $row));
                        if ($region) {

                        } else {
                            array_push($result_data['delete'], $row);
                        }
                    }
                }

                $response['status'] = $this->status[0];
            } elseif ($param == 'field_manager') {
                $ids = $this->input->get_post('ids');

                if ($ids === false || !is_array($ids)) {
                    $result_data['fm'] = $this->utility_model->get_list('ins_admin', array('kind' => 2));
                    $result_data['delete'] = array();
                } else {
                    $result_data['fm'] = $this->utility_model->get_list('ins_admin', array('kind' => 2));
                    $result_data['delete'] = array();

                    foreach ($ids as $row) {
                        $fm = $this->utility_model->get('ins_admin', array('id' => $row));
                        if ($fm) {

                        } else {
                            array_push($result_data['delete'], $row);
                        }
                    }
                }

                $response['status'] = $this->status[0];
            } else {
                $response['status'] = $this->status[3];
            }
        } elseif ($method == 'sys') {
            if ($param == 'energy_inspection') {
                $rows = $this->utility_model->get_list__by_sql("select * from sys_energy_inspection");
                if ($rows) {
                    $result_data['rows'] = $rows;
                } else {
                    $result_data['rows'] = array();
                }

                $rows = $this->utility_model->get_list__by_sql("select * from sys_config");
                if ($rows) {
                    $result_data['sys_config'] = $rows;
                } else {
                    $result_data['sys_config'] = array();
                }

                $response['status'] = $this->status[0];
            } else {
                $response['status'] = $this->status[3];
            }
        } else {
            $response['status'] = $this->status[2];
        }

        $response['request']['data'] = $request_data;
        $response['response'] = $result_data;

        print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));
    }

    // version 2.0
    public function v2($method = '', $param = '', $kind = '')
    {
        $response = array(
            'status' => $this->status[1],
            'request' => array(
                'method' => $method,
                'param' => $param,
                'kind' => $kind,
                'data' => array()
            ),
            'response' => array()
        );

        $request_data = array();
        $result_data = array();

        if ($method == 'user') {
            if ($param == 'field_manager') {
                $region = $this->input->get_post('region');

                if ($region === false) {
                    $response['status'] = $this->status[3];
                } else {
                    $region = $this->utility_model->decode($region);

                    $user = $this->utility_model->get_list__by_sql(" select a.* from ins_admin a where a.kind=2 and a.status=1 and a.id in ( select manager_id from ins_admin_region where region='$region' || region = '0' ) ");
                    if ($user) {
                        $result_data['user'] = $user;
                    } else {
                        $result_data['user'] = array();
                    }

                    $response['status'] = $this->status[0];
                }
            } else {
                $response['status'] = $this->status[2];
            }
        } elseif ($method == 'sync') {
            if ($param == 'region') {
                $ids = $this->input->get_post('ids');
                if ($ids === false || !is_array($ids)) {
                    $result_data['region'] = $this->utility_model->get_list('ins_region', array());
                    $result_data['delete'] = array();
                } else {
                    $result_data['region'] = $this->utility_model->get_list('ins_region', array());
                    $result_data['delete'] = array();

                    foreach ($ids as $row) {
                        $region = $this->utility_model->get('ins_region', array('id' => $row));
                        if ($region) {

                        } else {
                            array_push($result_data['delete'], $row);
                        }
                    }
                }

                $response['status'] = $this->status[0];
            } elseif ($param == 'field_manager') {
                $ids = $this->input->get_post('ids');

                if ($ids === false || !is_array($ids)) {
                    $fm = $this->utility_model->get_list('ins_admin', array('kind' => 2));
                    $result_data['delete'] = array();
                } else {
                    $fm = $this->utility_model->get_list('ins_admin', array('kind' => 2));
                    $result_data['delete'] = array();

                    foreach ($ids as $row) {
                        if ($this->utility_model->get('ins_admin', array('id' => $row))) {

                        } else {
                            array_push($result_data['delete'], $row);
                        }
                    }
                }

                $fms = array();
                if (isset($fm) && is_array($fm)) {
                    foreach ($fm as $row) {
                        $region = "";

                        $ffff = $this->utility_model->get_list__by_sql(" select a.region from ins_admin_region a where a.manager_id='" . $row['id'] . "' ");
                        if ($ffff) {
                            foreach ($ffff as $rrr) {
                                $region .= "r" . $rrr['region'] . "r";
                            }
                        }

                        $row['region'] = $region;
                        array_push($fms, $row);
                    }
                }
                $result_data['fm'] = $fms;

                $response['status'] = $this->status[0];
            } else {
                $response['status'] = $this->status[3];
            }
        } elseif ($method == 'optimize') {
            $report_keep_day = 30;
            $configuration = $this->utility_model->get('sys_config', array('code' => 'report_keep_day'));
            if ($configuration) {
                $report_keep_day = intval($configuration['value']);
            }

            $current_time = time();

            $path = "resource/upload/report/";
            $files = scandir($path);
            foreach ($files as $file) {
                $full_path = $path . $file;
                if (is_file($full_path)) {
                    $ext = pathinfo($full_path, PATHINFO_EXTENSION);
                    if (strtolower($ext) == "pdf") {
                        if ($current_time - filemtime($full_path) >= 30 * 24 * 60 * 60) {
                            unlink($full_path);
                            array_push($result_data, $file);
                        }
                    }
                }
            }

            $response['status'] = $this->status[0];
        }

        $response['request']['data'] = $request_data;
        $response['response'] = $result_data;

        print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));
    }

    public function do_upload()
    {
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = 100;
        $config['max_width'] = 1024;
        $config['max_height'] = 768;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('userfile')) {
            $error = array('error' => $this->upload->display_errors());

            $this->load->view('upload_form', $error);
        } else {
            $data = array('upload_data' => $this->upload->data());

            $this->load->view('upload_success', $data);
        }
    }

    public function upload($kind = '', $type = '')
    {

        $msg = array('code' => 1, 'message' => 'Failed!', 'url' => '', 'path' => '');
        $msg['kind'] = $kind;
        $msg['type'] = $type;
        $dir_name = "";

        if ($kind != "") {
            if ($type != "") {
                $dir_name = "resource/upload/$kind/$type/";
            } else {
                $dir_name = "resource/upload/$kind/";
            }

            $msg['dir_name'] = $dir_name;
            $uu_id = $this->uuid->v4();
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            $fname = mdate('%Y%m%d%H%i%s', time()) . "_" . $uu_id . "." . $ext;
            $new_name = $dir_name . $fname;

            $msg['tmp_name'] = $_FILES;
            $msg['$new_name'] = $new_name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $new_name)) {
                $url = base_url() . $new_name;
                // http://inspdev.e3bldg.com/resource/upload/pulte_duct/manometer/20180505073952_9a0d81b0-b377-43c9-8fbb-06c2bc6561b9.jpg
                $query = "http://";
                if (substr($url, 0, strlen($query)) === $query) {
                    $url = "https://" . substr($url, strlen($query));
                }
                //echo $url;

                $msg['url'] = $url;
                $msg['path'] = $fname;
                $msg['code'] = 0;
                $msg['message'] = "Success!";
            }
        }

        print_r(json_encode($msg));
    }

    public function export($kind = '', $method = '')
    {

        $table_order = $this->input->get_post('table_order');
        $data_order = $this->input->get_post('data_order');


        if ($kind == 'inspection') {

            ini_set('memory_limit', '512M');

            $inspection_id = $this->input->get_post('id');
            //            $inspection_id = $this->utility_model->decode($inspection_id);
            $sql = "select type from ins_inspection where id = '$inspection_id'";

            $inspection = $this->utility_model->get__by_sql($sql);
            if ($inspection) {
                $inspection_type = $inspection['type'];

                $type = $this->input->get_post('type');
                if ($type === false) {
                    $type = "full";
                }

                if ($type == 'duct' || $type == 'envelop') {
                    $this->m_pdf->initialize("B4-C", "P");
                } else {
                    $this->m_pdf->initialize();

                }

                if ($inspection_type == 5) {
                    $this->m_pdf->initialize("A4-P", "D");
                }


                $html = "";
                if ($inspection_type >= 4) {
                    if ($type == 'duct') {
                        $this->m_pdf->setSize();
                        $html = $this->get_report_html__for_duct_leakage_2018($inspection_id);
                    } elseif ($type == 'envelop') {
                        $this->m_pdf->setSize();
                        $html = $this->get_report_html__for_envelop_leakage_2018($inspection_id);
                    } else {
                        $html = $this->get_report_html($inspection_id, $type);
                    }
                } else {
                    if ($type == 'duct') {
                        $html = $this->get_report_html__for_duct_leakage($inspection_id);
                    } elseif ($type == 'envelop') {
                        $html = $this->get_report_html__for_envelop_leakage($inspection_id);
                    } else {
                        $html = $this->get_report_html($inspection_id, $type);
                    }
                }


                $this->m_pdf->pdf->WriteHTML($html);
                $this->m_pdf->pdf->Output("report.pdf", "D");
            }
        }


        if ($kind == 'statistics') {
            if ($method == 'inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $description = $this->input->get_post('desc');
                    if ($description === false || $description == "") {
                        $description = "1";
                    }

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');
                    $param_order = array();
                    $cols = array("a.type", "a.region", "a.community", "a.job_number", "a.address", "u.first_name", "a.overall_comments", "a.start_date", "a.result_code", "a.house_ready");

                    if (gettype($table_order) == 'string') {
                        $tmp_pieces = explode(",", $table_order);
                        if (is_array($tmp_pieces) && count($tmp_pieces) == 2) {
                            //9,desc
                            $sCol = $tmp_pieces[0];
                            $sdir = $tmp_pieces[1];
                            $param_order = array('cols' => $cols, 'index' => $sCol, 'dir' => $sdir);
                        }
                    }
                    if ($region === false || $region == null || $region == "null") {
                        $region = "";
                    }
                    if ($community === false || $community == null || $community == "null") {
                        $community = "";
                    }
                    if ($start_date === false || $start_date == null) {
                        $start_date = "";
                    }
                    if ($end_date === false || $end_date == null) {
                        $end_date = "";
                    }
                    if ($status === false || $status == null) {
                        $status = "";
                    }
                    if ($type === false || $type == null) {
                        $type = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();
                        // te function get_report_data__for_statistics_inspection($region, $community, $start_date, $end_date, $status, $type, $is_array = false, $include_description = true , $table_order = array()) {
                        $html = $this->get_report_data__for_statistics_inspection($region, $community, $start_date, $end_date, $status, $type, false, intval($description) === 1, $param_order);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_statistics_inspection($region, $community, $start_date, $end_date, $status, $type, true, intval($description) === 1, $param_order);
                        array_to_csv($data, "report.csv");
                    }
                }
            }

            if ($method == 're_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $description = $this->input->get_post('desc');
                    if ($description === false || $description == "") {
                        $description = "1";
                    }

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');

                    $param_order = array();
                    $cols = array("a.type", "a.region", "a.community", "a.job_number", "a.address", "u.first_name", "a.overall_comments", "a.start_date", "q.epo_number", "g.inspection_count", "a.result_code");

                    if (gettype($table_order) == 'string') {
                        $tmp_pieces = explode(",", $table_order);
                        if (is_array($tmp_pieces) && count($tmp_pieces) == 2) {
                            //9,desc
                            $sCol = $tmp_pieces[0];
                            $sdir = $tmp_pieces[1];
                            $param_order = array('cols' => $cols, 'index' => $sCol, 'dir' => $sdir);
                        }
                    }

                    if ($region === false || $region == null || $region == "null") {
                        $region = "";
                    }
                    if ($community === false || $community == null || $community == "null") {
                        $community = "";
                    }
                    if ($start_date === false || $start_date == null || $start_date == "null") {
                        $start_date = "";
                    }
                    if ($end_date === false || $end_date == null || $end_date == "null") {
                        $end_date = "";
                    }
                    if ($status === false || $status == null || $status == "null") {
                        $status = "";
                    }
                    if ($type === false || $type == null || $type == "null") {
                        $type = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();

                        $html = $this->get_report_data__for_statistics_re_inspection($region, $community, $start_date, $end_date, $status, $type, false, intval($description) === 1, $param_order);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_statistics_re_inspection($region, $community, $start_date, $end_date, $status, $type, true, intval($description) === 1, $param_order);
                        array_to_csv($data, "report.csv");
                    }
                }
            }

            if ($method == 'checklist') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');


                    if ($region === false || $region == null || $region == "null") {
                        $region = "";
                    }
                    if ($community === false || $community == null || $community == "null") {
                        $community = "";
                    }
                    if ($start_date === false || $start_date == null || $start_date == "null") {
                        $start_date = "";
                    }
                    if ($end_date === false || $end_date == null || $end_date == "null") {
                        $end_date = "";
                    }
                    if ($status === false || $status == null || $status == "null") {
                        $status = "";
                    }
                    if ($type === false || $type == null || $type == "null") {
                        $type = "";
                    }


                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();
                        $html = $this->get_report_data__for_statistics_checklist($region, $community, $start_date, $end_date, $status, $type);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_statistics_checklist($region, $community, $start_date, $end_date, $status, $type, true);
                        array_to_csv($data, "report.csv");
                    }
                    //                    echo $html;
                }
            }

            if ($method == 'fieldmanager') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $region = $this->input->get_post('region');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $type = $this->input->get_post('type');
                    $community = $this->input->get_post('community');

                    if ($region === false || $region == null || $region == "null") {
                        $region = "";
                    }
                    if ($community === false || $community == null || $community == "null") {
                        $community = "";
                    }
                    if ($start_date === false || $start_date == null) {
                        $start_date = "";
                    }
                    if ($end_date === false || $end_date == null) {
                        $end_date = "";
                    }
                    if ($status === false || $status == null) {
                        $status = "";
                    }
                    if ($type === false || $type == null) {
                        $type = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();
                        $html = $this->get_report_data__for_statistics_fieldmanager($region, $start_date, $end_date, $type);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_statistics_fieldmanager($region, $start_date, $end_date, $type, true);
                        array_to_csv($data, "report.csv");
                    }

                    //                    echo $html;
                }
            }

            if ($method == 'inspector') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $region = $this->input->get_post('region');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();
                        $html = $this->get_report_data__for_statistics_inspector($region, $start_date, $end_date, $type);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_statistics_inspector($region, $start_date, $end_date, $type, true);
                        array_to_csv($data, "report.csv");
                    }
                    //                    echo $html;
                }
            }
        }

        if ($kind == 'scheduling') {
            $this->load->helper('csv');

            $region = $this->input->get_post('region');
            $community = $this->input->get_post('community');
            $start_date = $this->input->get_post('start_date');
            $end_date = $this->input->get_post('end_date');
            $inspector_id = $this->input->get_post('inspector_id');
            $ordering = $this->input->get_post('ordering');
            $status = $this->input->get_post('status');
            $data = $this->get_scheduling_data($inspector_id, $region, $community, $start_date, $end_date, $ordering, $status);

            if (count($data) > 0) {
                $filename = "schedule_" . $start_date . "_" . $end_date;

                $user = $this->utility_model->get('ins_user', array('id' => $inspector_id));
                if ($user) {
                    $filename .= "_" . $user['first_name'] . " " . $user['last_name'];
                }
                array_to_csv($data, $filename . ".csv");
            }
        }

        if ($kind == 'payable') {
            if ($method == 'payroll') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $inspector = $this->input->get_post('inspector');
                    $period = $this->input->get_post('period');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');

                    if ($inspector === false) {
                        $inspector = "";
                    }
                    if ($period === false) {
                        $period = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();
                        $html = $this->get_report_data__for_payable_payroll($inspector, $period, $start_date, $end_date);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_payable_payroll($inspector, $period, $start_date, $end_date, true);
                        array_to_csv($data, "report.csv");
                    }
                }
            }

            if ($method == 're_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');
                    $epo_status = $this->input->get_post('epo_status');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }
                    if ($epo_status === false) {
                        $epo_status = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();

                        $html = $this->get_report_data__for_payable_re_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, false);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_payable_re_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, true);
                        array_to_csv($data, "report.csv");
                    }
                }
            }

            if ($method == 'pending_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $file_format = $this->input->get_post('file_format');
                    if ($file_format === false || $file_format == "") {
                        $file_format = "pdf";
                    }

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');
                    $epo_status = $this->input->get_post('epo_status');
                    $payment_status = $this->input->get_post('payment_status');
                    $re_inspection = $this->input->get_post('re_inspection');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }
                    if ($epo_status === false) {
                        $epo_status = "";
                    }
                    if ($payment_status === false) {
                        $payment_status = "";
                    }
                    if ($re_inspection === false) {
                        $re_inspection = "";
                    }

                    if ($file_format == "pdf") {
                        $this->m_pdf->initialize();

                        $html = $this->get_report_data__for_payable_pending_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, $payment_status, $re_inspection, false);

                        $this->m_pdf->pdf->WriteHTML($html);
                        $this->m_pdf->pdf->Output("report.pdf", "D");
                    }

                    if ($file_format == "csv") {
                        $data = $this->get_report_data__for_payable_pending_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, $payment_status, $re_inspection, true);
                        array_to_csv($data, "report.csv");
                    }
                }
            }
        }

        if ($kind == 'requested_inspection') {
            if ($this->session->userdata('user_id')) {
                ini_set('memory_limit', '512M');

                $this->load->helper('csv');

                $start_date = $this->input->get_post('start_date');
                $end_date = $this->input->get_post('end_date');
                $type = $this->input->get_post('type');
                $status = $this->input->get_post('status');

                if ($start_date === false) {
                    $start_date = "";
                }
                if ($end_date === false) {
                    $end_date = "";
                }
                if ($status === false) {
                    $status = "";
                }
                if ($type === false) {
                    $type = "";
                }

                $data = $this->get_report_data__for_requested_inspection($start_date, $end_date, $status, $type, true);
                array_to_csv($data, "report.csv");
            }
        }
    }

    public function email($kind, $method = "")
    {
        $response = array('code' => -1, 'message' => 'Failed to send email');

        if ($kind == 'statistics') {
            if ($method == 'inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }


                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_statistics_inspection($region, $community, $start_date, $end_date, $status, $type);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspection Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }

            if ($method == 're_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }


                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_statistics_re_inspection($region, $community, $start_date, $end_date, $status, $type);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspection Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }

            if ($method == 'checklist') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');
                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }

                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_statistics_checklist($region, $community, $start_date, $end_date, $status, $type);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspection Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }

            if ($method == 'fieldmanager') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');
                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }

                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_statistics_fieldmanager($region, $start_date, $end_date, $type);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspection Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }

            if ($method == 'inspector') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');
                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $type = $this->input->get_post('type');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }

                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_statistics_inspector($region, $start_date, $end_date, $type);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspection Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }
        } elseif ($kind == 'inspection') {
            if ($this->session->userdata('user_id')) {
                ini_set('memory_limit', '512M');

                $inspection_id = $this->input->get_post('id');
                if ($inspection_id === false || $inspection_id == "") {
                    $response['message'] = "Invalid Inspection";
                } else {
                    $inspection = $this->utility_model->get('ins_inspection', array('id' => $inspection_id));
                    if ($inspection) {
                        $recipients = array();
                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, $addr);
                                    }
                                }
                            }
                        }

                        $report = $this->send_report($this->session->userdata('user_id'), $inspection_id, true, $recipients);
                        if ($report === false) {
                            $response = $this->status[1];
                        } else {
                            //                        $result_data['email'] = $report;
                            $response = $this->status[0];
                        }
                    } else {
                        $response['message'] = "Invalid Inspection";
                    }
                }
            }
        } elseif ($kind == 'payable') {
            if ($method == 're_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');
                    $epo_status = $this->input->get_post('epo_status');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }
                    if ($epo_status === false) {
                        $epo_status = "";
                    }

                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_payable_re_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Re-Inspections EPO", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }

            if ($method == 'pending_inspection') {
                if ($this->session->userdata('user_id') && $this->session->userdata('permission') == 1) {
                    ini_set('memory_limit', '512M');

                    $this->m_pdf->initialize();

                    $region = $this->input->get_post('region');
                    $community = $this->input->get_post('community');
                    $start_date = $this->input->get_post('start_date');
                    $end_date = $this->input->get_post('end_date');
                    $status = $this->input->get_post('status');
                    $type = $this->input->get_post('type');
                    $epo_status = $this->input->get_post('epo_status');
                    $payment_status = $this->input->get_post('payment_status');
                    $re_inspection = $this->input->get_post('re_inspection');

                    if ($region === false) {
                        $region = "";
                    }
                    if ($community === false) {
                        $community = "";
                    }
                    if ($start_date === false) {
                        $start_date = "";
                    }
                    if ($end_date === false) {
                        $end_date = "";
                    }
                    if ($status === false) {
                        $status = "";
                    }
                    if ($type === false) {
                        $type = "";
                    }
                    if ($epo_status === false) {
                        $epo_status = "";
                    }
                    if ($payment_status === false) {
                        $payment_status = "";
                    }
                    if ($re_inspection === false) {
                        $re_inspection = "";
                    }

                    $user_id = $this->session->userdata('user_id');
                    $user = $this->user_model->get_user__by_id('admin', $user_id);
                    if ($user) {
                        $uu_id = $this->uuid->v4();

                        $recipients = array();
                        array_push($recipients, array('email' => $user['email']));

                        $recipient = $this->input->get_post('recipient');
                        if ($recipient !== false && $recipient != "") {
                            $emails = explode(",", $recipient);
                            if (is_array($emails)) {
                                foreach ($emails as $row) {
                                    $addr = trim($row);
                                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                        array_push($recipients, array('email' => $addr));
                                    }
                                }
                            }
                        }

                        $html = $this->get_report_data__for_payable_pending_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, $payment_status, $re_inspection);
                        $this->m_pdf->pdf->WriteHTML($html);

                        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";
                        $this->m_pdf->pdf->Output($filename, "F");

                        $email_template = $this->get_report_html__for_mail($filename);

                        $result = $this->send_mail("Inspections Pending Payment Report", $email_template, $recipients, true);
                        if ($result == "") {
                            $response['code'] = 0;
                            $response['message'] = "Successfully Sent!";
                        } else {

                        }

                        sleep(1);
                        //                        unlink($filename);
                    }
                }
            }
        }

        print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));
    }

    private function send_report($user_id, $inspection_id, $manual_report = false, $recipients = array())
    {
        $ret = false;
        if ($manual_report) {
            $inspection = $this->utility_model->get('ins_inspection', array('id' => $inspection_id));
        } else {
            $inspection = $this->utility_model->get('ins_inspection', array('user_id' => $user_id, 'id' => $inspection_id));
        }

        if ($inspection['type'] == 3 || $inspection['type'] >= 4 || $inspection['type'] >= 5) {
            // energy
            $sender = array();
            $user = $this->utility_model->get('ins_user', array('id' => $inspection['user_id']));

            if ($manual_report) {
                $fm = $this->utility_model->get('ins_admin', array('id' => $user_id, 'allow_email' => 1));
                if ($fm) {
                    array_push($sender, array('email' => $fm['email']));
                }

                foreach ($recipients as $row) {
                    array_push($sender, array('email' => $row));
                }
            } else {
                $fm = $this->utility_model->get('ins_admin', array('id' => $inspection['field_manager'], 'allow_email' => 1));
                if ($fm) {
                    array_push($sender, array('email' => $fm['email']));
                }

                // add inspector. 6/3
                if ($user) {
                    array_push($sender, array('email' => $user['email']));
                }
            }


            $inspection_requested = $this->utility_model->get('ins_inspection_requested', array('id' => $inspection['requested_id']));
            $complete_date = $inspection['end_date'];
            if ($inspection_requested) {
                $complete_date = $inspection_requested['completed_at'];

                if (isset($inspection_requested['document_person']) && $inspection_requested['document_person'] != "") {
                    $emails = explode(",", $inspection_requested['document_person']);
                    if (is_array($emails)) {
                        foreach ($emails as $row) {
                            $addr = trim($row);
                            if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                array_push($sender, array('email' => $addr));
                            }
                        }
                    }
                }
            }

            $result_duct_leakage = $this->utility_model->get('ins_code', array('kind' => 'rst_duct', 'code' => $inspection['result_duct_leakage']));
            $result_envelop_leakage = $this->utility_model->get('ins_code', array('kind' => 'rst_envelop', 'code' => $inspection['result_envelop_leakage']));

            $subject = "Community " . $inspection['community'] . ", Lot " . $inspection['lot'] . " Duct and Envelope Leakage Inspection Results";

            $sys_emails = $this->utility_model->get_list('sys_recipient_email', array('status' => '1'));
            if ($sys_emails) {
                foreach ($sys_emails as $row) {
                    array_push($sender, $row);
                }
            }

            $file1 = '';
            $file2 = '';

            if ($inspection['type'] >= 4) {
                //
                $list_model = $this->utility_model->get_list('ins_flag_email_report', array('pulte_duct' => 0));
                $list_exception = array();
                foreach ($list_model as $model) {
                    $list_exception[] = $model['email'];
                }

                $list_tmp = array();
                foreach ($sender as $row) {
                    if (!in_array($row['email'], $list_exception)) {
                        $list_tmp[] = $row['email'];
                    }
                }
                $list_tmp = array_unique($list_tmp);
                $sender = array();
                foreach ($list_tmp as $addr) {
                    array_push($sender, array('email' => $addr));
                }


                if (($inspection['result_envelop_leakage'] == 1 || $inspection['result_envelop_leakage'] == 2)
                    && $inspection_requested) { //$result_duct_leakage['name'] == 'Pass' &&
//                    $list_model = $this->utility_model->get_list('ins_jurisdiction', array('status'=>0));
//                    foreach($list_model as $model){
//                        array_push($sender, array('email' => $model['email']));
//                    }
                    $ins_jurisdiction = $this->utility_model->get('ins_jurisdiction', array('id' => $inspection_requested['jur_id']));
                    if ($ins_jurisdiction) {
                        array_push($sender, array('email' => $ins_jurisdiction['email']));
                    }
                }

                $file1 = $this->make_pdf_for_duct_leakage($inspection_id, $inspection['type']);
                $file2 = $this->make_pdf_for_envelop_leakage($inspection_id, $inspection['type']);
            } else {
                $file1 = $this->make_pdf_for_duct_leakage($inspection_id);
                $file2 = $this->make_pdf_for_envelop_leakage($inspection_id);
            }


            // test purpose
            // $sender = array();
            // array_push($sender, array('email' => 'huangbo1117@gmail.com'));
            // exit(1);
            // var_dump($sender);
            $path1 = '/home/inspdev/public_html/';
            $path1 = $_SERVER['DOCUMENT_ROOT'] . '/';
            $files = array(
                array('path' => $path1 . $file1, 'name' => 'Duct Leakage.pdf'),
                array('path' => $path1 . $file2, 'name' => 'Envelope Leakage.pdf')
            );
            // $files = array(base_url() . $file1,base_url() . $file2);
            // var_dump($files);
            // exit(1);

            $result_duct_fail = $result_duct_leakage['name'];
            $result_envelop_fail = $result_envelop_leakage['name'];
            if (strtolower($result_duct_fail) == 'fail') {
                $result_duct_fail = '<span style="color:red">FAIL</span>';
            }
            if (strtolower($result_envelop_fail) == 'fail') {
                $result_envelop_fail = '<span style="color:red">FAIL</span>';
            }

            if ($manual_report) {
                $body = "<div>"
                    . "Duct and Envelope Leakage Inspection was completed by " . $user['first_name'] . " " . $user['last_name'] . ", on " . $complete_date . "<br>" . "<br>"
                    . "     Lot Number :  " . $inspection['lot'] . "<br>"
                    . "     Community  :  " . $inspection['community'] . "<br>"
                    . "     Address    :  " . $inspection['address'] . "<br>" . "<br>"
                    . "     Duct Leakage Test     :  " . $result_duct_fail . "<br>"
                    . "     Envelope Leakage Test  :  " . $result_envelop_fail . "<br>" . "<br>"
                    //. "Duct and Envelope Leakage Inspection was completed by " . $user['first_name'] . " " . $user['last_name'] . ", on " . $complete_date . "<br>" . "<br>"
                    . "<br>"
                    . '<a href="' . base_url() . $file1 . '">' . "Duct Leakage Report" . '</a>' . "<br>"
                    . '<a href="' . base_url() . $file2 . '">' . "Envelope Leakage Report" . '</a>' . "<br>"
                    . "<br>"
                    . "Best Regards," . "<br>"
                    . "The Inspections Team" . "<br>"
                    . "</div>";
                // echo $body;

                if ($this->send_mail_with_files($subject, $body, $sender, $files, true) === "") {
                    $ret = true;
                }
            } else {
                $body = "<div>"
                    . "Duct and Envelope Leakage Inspection was completed by " . $user['first_name'] . " " . $user['last_name'] . ", on " . $complete_date . "<br>" . "<br>"
                    . "     Lot Number :  " . $inspection['lot'] . "<br>"
                    . "     Community  :  " . $inspection['community'] . "<br>"
                    . "     Address    :  " . $inspection['address'] . "<br>" . "<br>"
                    . "     Duct Leakage Test     :  " . $result_duct_fail . "<br>"
                    . "     Envelope Leakage Test  :  " . $result_envelop_fail . "<br>" . "<br>"
                    //. "Duct and Envelope Leakage Inspection was completed by " . $user['first_name'] . " " . $user['last_name'] . ", on " . $complete_date . "<br>" . "<br>"
                    . "<br>"
                    . '<a href="' . base_url() . $file1 . '">' . "Duct Leakage Report" . '</a>' . "<br>"
                    . '<a href="' . base_url() . $file2 . '">' . "Envelope Leakage Report" . '</a>' . "<br>"
                    . "<br>"
                    . "Best Regards," . "<br>"
                    . "The Inspections Team" . "<br>"
                    . "</div>";
                // echo $body;


                if ($this->send_mail_with_files($subject, $body, $sender, $files, true) === "") {
                    $ret = true;
                }
            }

            sleep(1);
            //                unlink($file1);
//                unlink($file2);
        } else {
            // water intrusion
            $html_subject = "";

            switch ($inspection['result_code']) {
                case 1:
                    $html_subject = "Inspection - PASS";
                    break;
                case 2:
                    $html_subject = "Inspection - PASS WITH EXCEPTION";
                    break;
                default:
                    $html_subject = "Inspection - FAIL";
            }

            switch ($inspection['type']) {
                case 1:
                    $html_subject = "Drainage Plane " . $html_subject;
                    break;

                case 2:
                    $html_subject = "Lath " . $html_subject;
                    break;
            }

            $html_subject .= " with Job Number " . $inspection['job_number'] . "";

            $sender = array();

            if ($manual_report) {
                $fm = $this->utility_model->get('ins_admin', array('id' => $user_id));
                if ($fm) {
                    array_push($sender, array('email' => $fm['email']));
                }

                foreach ($recipients as $row) {
                    array_push($sender, array('email' => $row));
                }
            } else {
                $emails = $this->utility_model->get_list('ins_recipient_email', array('inspection_id' => $inspection_id));
                if ($emails) {
                    foreach ($emails as $row) {
                        array_push($sender, $row);
                    }
                }

                $emails = $this->utility_model->get_list('sys_recipient_email', array('status' => '1'));
                if ($emails) {
                    foreach ($emails as $row) {
                        array_push($sender, $row);
                    }
                }

                $fm = $this->utility_model->get('ins_admin', array('id' => $inspection['field_manager'], 'allow_email' => 1));
                if ($fm) {
                    array_push($sender, array('email' => $fm['email']));
                }

                // add inspector. 6/3
                $user = $this->utility_model->get('ins_user', array('id' => $user_id));
                if ($user) {
                    array_push($sender, array('email' => $user['email']));
                }
                // ------------------
                // add requested inspection's fm. 6/7/17.
                $requested_inspection = $this->utility_model->get('ins_inspection_requested', array('id' => $inspection['requested_id']));
                if ($requested_inspection) {
                    $fm = $this->utility_model->get('ins_admin', array('kind' => 2, 'id' => $requested_inspection['manager_id'], 'allow_email' => 1));
                    if ($fm) {
                        array_push($sender, array('email' => $fm['email']));
                    }
                }
                // ----------------------------------------
            }

            $file = $this->make_pdf($inspection_id);
            $html = $this->get_report_html__for_mail($file);

            // test purpose
            // $sender = array();
            // $sender = array_push($sender, array('email' => 'bohuang29@hotmail.com'));
            if ($manual_report) {
                if ($this->send_mail($html_subject, $html, $sender, true) === "") {
                    $ret = true;
                }
            } else {
                if ($this->send_mail($html_subject, $html, $sender, true) === "") {
                    $ret = true;
                }
            }

            sleep(1);
            //            unlink($file);
        }

        //        return $ret ? $sender : false;
        return $ret;
    }

    private function make_pdf_for_duct_leakage($inspection_id, $mode = 0)
    {
        $this->m_pdf->initialize("B4-C", "P");

        $fname = mdate('%Y-%m-%d %H%i%s', time());
        $fname = $this->utility_model->escape_filename($fname);

        $inspection = $this->utility_model->get('ins_inspection', array('id' => $inspection_id));
        if ($inspection) {
            $fname = $inspection['community'] . "_" . $inspection['job_number'] . "_duct_leakage" . "__" . $fname;
        }

        $html = '';
        if ($mode == 0) {
            $html = $this->get_report_html__for_duct_leakage($inspection_id);
        } else {
            $html = $this->get_report_html__for_duct_leakage_2018($inspection_id);
        }

        $this->m_pdf->pdf->WriteHTML($html);

        $filename = "resource/upload/report/" . $fname . ".pdf";
        $this->m_pdf->pdf->Output($filename, "F");

        return $filename;
    }

    private function make_pdf_for_envelop_leakage($inspection_id, $mode = 0)
    {
        $this->m_pdf->initialize("B4-C", "P");

        $fname = mdate('%Y-%m-%d %H%i%s', time());
        $fname = $this->utility_model->escape_filename($fname);

        $inspection = $this->utility_model->get('ins_inspection', array('id' => $inspection_id));
        if ($inspection) {
            $fname = $inspection['community'] . "_" . $inspection['job_number'] . "_envelope_leakage" . "__" . $fname;
        }

        $html = '';
        if ($mode == 0) {
            $html = $this->get_report_html__for_envelop_leakage($inspection_id);
        } else {
            $html = $this->get_report_html__for_envelop_leakage_2018($inspection_id);
        }
        $this->m_pdf->pdf->WriteHTML($html);

        $filename = "resource/upload/report/" . $fname . ".pdf";
        $this->m_pdf->pdf->Output($filename, "F");

        return $filename;
    }

    private function make_pdf($inspection_id)
    {
        $this->m_pdf->initialize();

        $fname = "";

        $inspection = $this->utility_model->get('ins_inspection', array('id' => $inspection_id));
        if ($inspection) {
            if ($inspection['type'] == 1) {
                $fname = "Drainage Plane Inspection";
            } elseif ($inspection['type'] == 2) {
                $fname = "Lath Inspection";
            } elseif ($inspection['type'] == 5) {
                $fname = "Stucco Inspection";
            }

            $result_code = $this->utility_model->get('ins_code', array('kind' => 'rst', 'code' => $inspection['result_code']));
            if ($result_code) {
                $fname .= " - " . $result_code['name'];
            }

            $fname .= " with Job Number " . $inspection['job_number'];

            $community = $this->utility_model->get('ins_community', array('community_id' => $inspection['community']));
            if ($community) {
                $fname .= " " . $community['community_name'];
            }
        }

        $fname .= "__" . mdate('%Y-%m-%d %H%i%s', time());
        $fname = $this->utility_model->escape_filename($fname);

        $html = $this->get_report_html($inspection_id, 'pass');
        $this->m_pdf->pdf->WriteHTML($html);

        $filename = "resource/upload/report/" . $fname . ".pdf";
        $this->m_pdf->pdf->Output($filename, "F");

        return $filename;
    }

    public function get_report_html($inspection_id, $type = 'full')
    {

        //$sql = " select a.*, u.email, c2.name as result_name as result_code from ins_code c2, ins_inspection a left join ins_user u on a.user_id=u.id where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code ";
        //modified by bongbong 2016/04/08
        $sql = "select a.*, u.email, c2.name as result_name,
                (select count(*) from ins_inspection d where replace(d.job_number,'-','')=replace(a.job_number,'-','') and type=1 and (d.result_code=1 or d.result_code=2)) as pass_drg_cnt
                from ins_code c2, ins_inspection a
                left join ins_user u on a.user_id=u.id where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code ";
        $inspection = $this->utility_model->get__by_sql($sql);

        $html_styles = "<style type='text/css'> .text-center{text-align:center}.row{float:left;width:100%;margin-bottom:20px}.col-50-percent{float:left;width:50%}span{color:#111;padding:2px 7px;font-weight:bold}.label-danger{background:#d9534f;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.label-success{background:#5cb85c;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.label-warning{background:#f0ad4e;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.checklist{border:1px solid #000;width:100%; border-collapse: collapse;}.location{width:100px;text-align:center}.checklist .status{width:100px;text-align:center}.checklist .item{padding:4px 8px}</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";

        $html_body = "";

        $title = "";
        if ($inspection['type'] == '1') {
            $title = "DRAINAGE PLANE INSPECTION REPORT";
        }
        if ($inspection['type'] == '2') {
            $title = "LATH INSPECTION REPORT";
        }
        if ($inspection['type'] == '5') {

            $title = "STUCCO INSPECTION REPORT";

            $sql_request = "SELECT ir.* FROM ins_inspection_requested as ir inner join ins_inspection as ii On ir.id=ii.requested_id WHERE ir.id='" . $inspection['requested_id'] . "'";
            $inspection_requested = $this->utility_model->get__by_sql($sql_request);

            $sql_images = "SELECT img.* FROM ins_inspection_requested as ir inner join ins_inspection_images as img On  ir.id=img.requested_id where ir.id='" . $inspection['requested_id'] . "'";
            $inspection_images = $this->utility_model->get__by_sql($sql_images);

            /*echo "<pre>";
            print_r($inspection);
            print_r($inspection_requested);
            print_r($inspection_images);
            */
            return $this->getStocoHtml($inspection_id, $inspection, $inspection_requested, $inspection_images);


        }

        $html_body .= "<h1 style='text-align: center; color: #00e;'>" . $title . "</h1>";

        // added logo
        $html_body .= '<div class="row text-center"><img src="' . $this->image_url_change(LOGO_PATH) . '" style="max-width: 400px; margin: auto;"></div>';

        // added by bongbong 2016/04/08
        if ($inspection["pass_drg_cnt"] == 0 && $inspection["type"] == '2') { // if there is no a Drainage Plane with pass or pass exception for this lath check
            $warning_message = "No Pass or Pass with Exception Drainage Inspection was completed for this lot";
            $html_body .= "<h4 style='text-align: center; color: #f00;'>" . $warning_message . "</h4>";
        }

        $title = $inspection['community'] . ", " . $inspection['lot'] . ", " . $inspection['start_date'];
        $html_body .= "<h3 style='text-align: right; color: #006;'>" . $title . "</h3>";

        if ($inspection['image_signature'] != "") {
            $html_body .= "<div class='row' style='text-align: right;'><img style='float: right; max-width: 150px;' src='" . $this->image_url_change($inspection['image_signature']) . "'></div>";
        }

        $html_body .= "<div class='row'><div class='col-50-percent'><table class='data-table'>";

        //        $html_body .= "<tr><td class='field-name'>Community :</td><td class='field-value'>" . $inspection['community'] . "</td></tr>";
        //        $html_body .= "<tr><td class='field-name'>LOT# :</td><td class='field-value'>" . $inspection['lot'] . "</td></tr>";
        $html_body .= "<tr><td class='field-name'>Job Number :</td><td class='field-value'>" . $inspection['job_number'] . "</td></tr>";
        $html_body .= "<tr><td class='field-name'>Address :</td><td class='field-value'>" . $inspection['address'] . "</td></tr>";
        $html_body .= "<tr><td colspan='2'>Is This House Ready For Inspection? <span>" . ($inspection['house_ready'] == '1' ? "Yes" : "No") . "</span></td></tr>";

        if ($inspection['image_front_building'] != "") {
            $html_body .= "<tr><td colspan='2' style='text-align: center;'><img style='max-height: 300px;' src='" . $this->image_url_change($inspection['image_front_building']) . "'></td></tr>";
        }

        $html_body .= "</table></div><div class='col-50-percent'><table class='data-table'> ";

        //        $html_body .= "<tr><td class='field-name'>Date :</td><td class='field-value'>" . $inspection['start_date'] . "</td></tr>";
        $html_body .= "<tr><td class='field-name'>Inspector :</td><td class='field-value'>" . $inspection['initials'] . "</td></tr>";
        $fm = $this->utility_model->get('ins_admin', array('id' => $inspection['field_manager']));
        if ($fm) {
            $html_body .= "<tr><td class='field-name'>Field Manager :</td><td class='field-value'>" . $fm['first_name'] . " " . $fm['last_name'] . "</td></tr>";
        }

        if ($inspection['latitude'] == '-1' && $inspection['longitude'] == '-1' && $inspection['accuracy'] == '-1') {

        } else {
            $google_map = "<img width='300' src='http://maps.googleapis.com/maps/api/staticmap?center=" . $inspection['latitude'] . "+" . $inspection['longitude'] . "&zoom=16&scale=false&size=300x300&maptype=roadmap&format=jpg&visual_refresh=true' alt='Google Map'>";
            $html_body .= "<tr><td colspan='2'>GPS Location : <span>Lat: " . $inspection['latitude'] . ", Lon: " . $inspection['longitude'] . ", Acc: " . $inspection['accuracy'] . "m</span></td></tr>";
            $html_body .= "<tr><td colspan='2' style='text-align: center;'>" . $google_map . "</td></tr>";
        }

        $html_body .= "</table></div></div>";

        $html_body .= "<div class='row text-center'>";

        $cls = "";
        if ($inspection['result_code'] == 1) {
            $cls = "label-success";
        }
        if ($inspection['result_code'] == 2) {
            $cls = "label-warning";
        }
        if ($inspection['result_code'] == 3) {
            $cls = "label-danger";
        }

        $html_body .= "<h4 class='" . $cls . "'>" . $inspection['result_name'] . "</h4>";
        $html_body .= "</div>";

        if ($inspection['result_code'] == 2) {
            $html_body .= "<div class='row text-center'>";
            $html_body .= "<h2 style='color:red'>ACTION REQUIRED</h2>";
            $html_body .= "</div>";
        }

        $failed_image = $this->utility_model->get_list('ins_exception_image', array('inspection_id' => $inspection_id));
        $failed_image_count = count($failed_image);

        $html_body .= '<p style="font-size: 18px;">Overall Comments: ' . $inspection['overall_comments'] . '</p>';
        if ($failed_image_count > 0) {
            $html_body .= "<div class='row'><table class='checklist'>";

            $image_percent = intval(100 / $failed_image_count * 3 / 4);

            $html_body .= "<tr><td class='text-center'>";

            foreach ($failed_image as $row) {
                $img = "<img style='max-width: " . $image_percent . "%; padding:5px; ' src='" . $this->image_url_change($row['image']) . "'>";
                $html_body .= $img;
            }

            $html_body .= "</td></tr>";

            $html_body .= "</table></div>";
        }

        $inspection_comment_list_code = "";
        $inspection_type = intval($inspection['type']);
        if ($inspection_type == 1) {
            $inspection_comment_list_code = "drg_comment";
        } else {
            $inspection_comment_list_code = "lth_comment";
        }
        if ($inspection_type == 5) {
            $inspection_comment_list_code = "stucco_comment";
        }

        $config_rows = $this->utility_model->get_list__by_sql("select * from sys_config");
        $checklist_online_link = "www.google.com";
        foreach ($config_rows as $row) {
            $code = $row['code'];
            $value = $row['value'];
            if ($code == 'checklist_online_link') {
                $checklist_online_link = $value;
            }
        }
        $comment_list = $this->utility_model->get_list__by_sql(" select a.*, c.name as comment_name from ins_inspection_comment a left join ins_code c on c.kind='$inspection_comment_list_code' and c.code=a.no where a.inspection_id='$inspection_id' order by a.no asc ");
        if (count($comment_list) > 0) {
            $html_body .= "<div class='row'><table class='checklist' border='1'>";
            $html_body .= "<thead>";
            $html_body .= "<tr><th class='text-center'>Comments</th></tr>";
            $html_body .= "</thead>";

            $html_body .= "<tbody>";
            foreach ($comment_list as $row) {
                if ($inspection_comment_list_code == 'drg_comment' && $row['no'] == '13') {
                    $html_body .= "<tr><td>";
                    $html_body .= "<span>Failed drainage plane inspection ONLY for missing windows. Proceed to Lath on areas not affected by missing windows. </span>"
                        . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use online link for Special Window Inspection</span>"
                        . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=' color: red;    ' href='$checklist_online_link'>$checklist_online_link</a>";
                    $html_body .= "</td></tr>";
                } else if ($inspection_comment_list_code == 'drg_comment' && $row['no'] == '14') {
                    $html_body .= "<tr><td>";
                    $html_body .= "<span>Failed drainage plane inspection for items other than missing windows. All non-window failures must be corrected and reinspected prior to proceeding to Lath.</span></span>"
                        . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use regular inspection request on the web portal to schedule reinspection.</span>";
                    $html_body .= "</td></tr>";
                } else if ($inspection_comment_list_code == 'lth_comment' && $row['no'] == '11') {
                    $html_body .= "<tr><td>";
                    $html_body .= "<span>Failed lath inspection ONLY on the basis of missing windows. OK to proceed to Lath on areas not affected by missing windows.</span></span>"
                        . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use online link for Special Window Inspection</span>"
                        . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=' color: red;    ' href='$checklist_online_link'>$checklist_online_link</a>";
                    $html_body .= "</td></tr>";
                } else if ($inspection_comment_list_code == 'lth_comment' && $row['no'] == '12') {
                    $html_body .= "<tr><td>";
                    $html_body .= "<span>Failed lath inspection on the basis of items other than missing windows. All non-window related items must be corrected and reinspected prior to proceeding to stucco. </span></span>"
                        . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use regular inspection request on the web portal to schedule reinspection.</span>";
                    $html_body .= "</td></tr>";
                } else {
                    $html_body .= "<tr><td>";
                    $html_body .= $row['comment_name'];
                    $html_body .= "</td></tr>";
                }
            }
            $html_body .= "</tbody>";
            $html_body .= "</table></div>";
        }

        $header_style = "background: #ddd; font-size:18px; padding: 10px 2px;";
        $body_style = "background: #f8f8f8;";

        $requested_id = $inspection['requested_id'];
        $inspection_requested = $this->utility_model->get('ins_inspection_requested', array('id' => $requested_id));

        // okok
        if ($inspection['house_ready'] == '1' || ($inspection_requested && $inspection_requested['reinspection'] != 0)) {
            $reinspection = $inspection_requested['reinspection'];

            $html_body .= "<div class='row'><table class='checklist'><tr><td class='location' style='" . $header_style . "'>Location</td><td class='item' style='" . $header_style . " text-align:center;'>Item</td><td class='status' style='" . $header_style . "'>Status</td></tr>";

            $k = $inspection['type'] == 1 ? 'drg' : 'lth';
            $locations = $this->utility_model->get_list('ins_location', array('inspection_id' => $inspection_id));
            foreach ($locations as $row) {
                $location = $row['name'];
                $checklist = $this->utility_model->get_list__by_sql("SELECT a.*, c.name as status_name, b.name as check_name FROM ins_code c, ins_checklist a JOIN ins_code b ON a.no=b.code WHERE a.status=c.code and c.kind='sts' and b.kind='" . $k . "' and a.inspection_id='" . $inspection_id . "' and a.location_id='" . $row['id'] . "'  ORDER BY a.no ");
                foreach ($checklist as $point) {
                    if ($type == 'full' || ($type == 'pass' && $point['status'] != '0' && $point['status'] != '1' && $point['status'] != '4')) {
                        $html_body .= "<tr><td class='location' style='" . $body_style . "'>" . $location . "</td><td class='item' style='" . $body_style . "'>" . $point['check_name'] . "</td><td class='status' style='" . $body_style . "'>" . $point['status_name'] . "</td></tr>";
                        if ($point['status'] == '2' || $point['status'] == '3') {
                            $html_body .= "<tr><td class='location' style='" . $body_style . "'></td><td class='item' colspan='2' style='" . $body_style . "'>Comments: " . $point['description'] . "</td></tr>";
                        }

                        if ($point['status'] == '2') {
                            $img = "";
                            if ($point['primary_photo'] != "") {
                                $img .= "<img style='max-width: 36%; padding:5px; ' src='" . $this->image_url_change($point['primary_photo']) . "'>";
                            }
                            if ($point['secondary_photo'] != "") {
                                $img .= "<img style='max-width: 36%; padding:5px; ' src='" . $this->image_url_change($point['secondary_photo']) . "'>";
                            }

                            $html_body .= "<tr><td colspan='3' class='text-center'>" . $img . "</td></tr>";
                        }
                    }
                }
            }

            $html_body .= "</table></div>";
        }

        $html_body .= "<div class='row'></div><div class='row'></div>";

        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        return $html;
    }


    public function ReportGenerateHtml()
    {

        $inspection_id = $_GET['id'];
        $type = $_GET['type'];

        $sql = "select a.*, u.email, c2.name as result_name,
                (select count(*) from ins_inspection d where replace(d.job_number,'-','')=replace(a.job_number,'-','') and type=1 and (d.result_code=1 or d.result_code=2)) as pass_drg_cnt
                from ins_code c2, ins_inspection a
                left join ins_user u on a.user_id=u.id where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code ";
        $inspection = $this->utility_model->get__by_sql($sql);


        $title = "STUCCO INSPECTION REPORT";

        $sql_request = "SELECT ir.* FROM ins_inspection_requested as ir inner join ins_inspection as ii On ir.id=ii.requested_id WHERE ir.id='" . $inspection['requested_id'] . "'";
        $inspection_requested = $this->utility_model->get__by_sql($sql_request);

        $sql_images = "SELECT img.* FROM ins_inspection_requested as ir inner join ins_inspection_images as img On  ir.id=img.requested_id where ir.id='" . $inspection['requested_id'] . "'";
        $inspection_images = $this->utility_model->get__by_sql($sql_images);


        echo $this->getStocoHtml($inspection_id, $inspection, $inspection_requested, $inspection_images);

        die;
    }


    public function stuccoImgGet($inspection_id, $stucco_label)
    {
        return $img = $this->utility_model->getImageList('ins_stucco_image', array('inspection_id' => $inspection_id, 'stucco_label' => $stucco_label, 'stucco_check' => 0));
    }

    public function getStocoHtml($inspection_id, $inspection, $inspection_requested, $inspection_images)
    {

        $html_styles = "<style type='text/css'>ul.gridImage {  width: 100%;  float: left;}label.unched {font-size: 36px;}
 label.yesy { font-size: 25px;}label.non.unched {font-size: 38px;}label.yesy.unched { font-size: 38px;}label.non.unched.yess {font-size: 24px;} body p {    margin: 5px 0px 5px 0px !important;}
 .text-center{text-align:center}.row{float:left;width:100%;margin-bottom:20px} .sectionsigle { margin-top: 10;clear: both;} .sectionsigle table { width: 100%;} .main_info_box p {  display: block !important;} .main_info_box span {    color: #111; padding: 2px 7px;  font-weight: bold;position: initial;  width: 100%;} label.non {font-size: 24px;} img { text-align: center;}  .signimage p img  {text-align: left !important;  margin: 0px !important;} 
      ul.gridImage li {margin-right: 1px;  max-width: 150px;overflow: hidden;}
     ul.gridImage li img { max-width: 150px; height: auto !important;}
     body {    font-family: arial;
    width: 1024px;
    margin: 0 auto;
    }
    @page {
        margin-top: 1cm;
        margin-bottom: 1cm;
        margin-left: 1cm;
        margin-right: 1cm;
   
    }
  @CHARSET 'UTF-8';
    .book_page {
      page-break-after: always;     
    }
    .book_page {
     page-break-before: always;
    }

    
    </style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body style='font-family: arial; width: 1024px;'>
<div style='padding: 0px 33px; width: 760px;display: table;margin: 0 auto;'>";
        $newHtml = "<div class='row text-center'><center><img width='322px' align='center' src='" . $this->image_url_change(LOGO_PATH) . "' style='text-align: center;width: 322px; margin: auto;'></center></div>

          <div style='text-align:center;margin: 40px 0 0px;'>
                   <p>Report Prepared By:</p>
                   <h3 style='margin:0;font-size: 18px;'>E3 Building Sciences</h3>
                   <p style='margin-bottom: 10px;display: inline-block;'>Date :<span style='border-bottom: 1px solid;'>" . date('Y-m-d', strtotime($inspection['created_at'])) . "</span></p>
    </div>
    <div><h1 style='text-align: center; color: #00e;font-size: 19px;'>STUCCO INSPECTION REPORT</h1></div>
  <div class='mainboxsection' >
    <div class='main_info_box'>

<table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'>
         <tr>
           <td 
 style='border:none;width: 24%;' valign='bottom'>Homeowner name :</td>
           <td  style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;word-break: break-word;    vertical-align: bottom;' valign='bottom'>" . $inspection_requested['first_name'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;    vertical-align: bottom;'>Community :</td>
           <td valign='bottom' style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['community_name'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;'>Street Address :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['address'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;'>City, State, Zip Code:</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['city'] . "," . $inspection_requested['state'] . "," . $inspection_requested['zip'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;'>Homeowner Phone :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['cell_phone'] . "</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>Homeowner E-mail :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['email'] . "</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>Close of escrow date – (built on or after 7/1/16?) :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection_requested['close_escrow_date'] . "</td>
         </tr>";
        /* $newHtml .="  <tr>
           <td style='border:none;width: 24%;'>Job_number :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['job_number']."</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>lot :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['lot']."</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>GPS Location:</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['gps_location']."</td>
         </tr>
          <tr>";
          /* $newHtml .="<td style='border:none;width: 24%;'>Lat :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['latitude']."</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>Lon :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['lot']."</td>
         </tr>
          <tr>
           <td style='border:none;width: 24%;'>Accuracy :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>".$inspection['accuracy']."</td>
         </tr>";"
         */
        $newHtml .= " </table>";

        $newHtml .= "</div>
  
    <div>
        <div class='book_page'><h3 style='margin:0 0 15px;display:block;    text-decoration: underline;'>Background:</h3></div>
     

  <table style='border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'>
         <tr>
           <td 
 style='border:none;width: 24%;' valign='bottom'>Number of stories :</td>
           <td  style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;word-break: break-word;    vertical-align: bottom;' valign='bottom'>" . $inspection['no_of_stories'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;    vertical-align: bottom;'>Hometype :</td>
           <td valign='bottom' style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection['home_type'] . "</td>
         </tr>
         <tr>
           <td style='border:none;width: 24%;'>E3 employee(s) performing inspection :</td>
           <td style='border: none;border-bottom: 1px solid black;width: 76%;word-break: break-all;
    word-break: break-word;'>" . $inspection['emp_per_inspection'] . "</td>
         </tr>
         
         
       </table>
        <p style='display: inline-block;line-height: 26px;'>Was an interior inspection performed?
        ";


        if (!empty($inspection['int_inspection'] == 1)) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non '>&#9745;</label> No";
        }


        $image_int_inspection = $this->stuccoImgGet($inspection_id, 'image_int_inspection');
        if (count($image_int_inspection) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_int_inspection as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</p><p style='display: inline-block;line-height: 26px;'>If home was built before June 1st, 2013, does it appear the stucco exterior has been re-painted?";

        if ($inspection['stucco_exterior'] == 1) {
            $newHtml .= " <label class='non unched'>&#9633;</label> Yes <label class='non unched yess'>&#9745;</label> No <label class='non unched'>&#9633;</label> N/A <label class='non unched'>&#9633;</label> Cannot Verify";
        } elseif ($inspection['stucco_exterior'] == 2) {
            $newHtml .= " <label class='non unched yess'>&#9745;</label> Yes <label class='non'>&#9633;</label> No <label class='non unched'>&#9633;</label> N/A <label class='non unched'>&#9633;</label> Cannot Verify";
        } elseif ($inspection['stucco_exterior'] == 4) {
            $newHtml .= " <label class='non unched'>&#9633;</label> Yes <label class='non unched'>&#9633;</label> No <label class='non unched'>&#9633;</label> N/A <label class='non unched yess'>&#9745;</label> Cannot Verify";
        } else {
            $newHtml .= " <label class='non unched'>&#9633;</label> Yes <label class='non unched'>&#9633;</label> No <label class='non unched yess'>&#9745;</label> N/A <label class='non unched'>&#9633;</label> N/A";
        }

        $newHtml .= "</p> </div>
    

    <div>
        <p style='margin:10px 0 15px;display:block;'><b>Notes:</b></p>
        <p style='line-height:25px;padding-left:20px;'>- All cracks to be measured and photographed with calibrated ruler in photo<br>
        - All cracks to be measured at widest point.<br>
        - Potentially' Excessive cracking is E3’s best judgement. Final determination of 'excessive' cracking to be made by Pulte <br>
        - weep screed conditions' are weep screeds improperly installed, weep screeds not installed, weep screeds lacking proper weep holes or other means to drain water, weep screeds with weep holes painted over, or any other condition that causes a weep screed to not function for its intended purposes.<br>
        - Stucco Delamination' is an apparent visual separation of any layer of stucco surface of the exterior of a home including without limitation buckling bubbling, peeling, blistering or collapse of the stucco.  </p>
    
      <p style='margin:30px 0 15px;display:block;'><b>Photo Annotation Key:</b></p>
      <p style='line-height:25px;padding-left:35px;'>C = Crack<br>
      PE = Potentially Excessive<br>
      D = Delamination<br>
      W = Water Intrusion<br>
      WS = Weep Screed Condition</p>
    </div>
       <div><h1 class='book_page' style='text-align: center;'>Front Elevation</h1></div>";
        if (!empty($inspection['image_front_building'])) {
            $newHtml .= "<div style='text-align: center;' class='pdfimage'><img height='611px' src='" . base_url("/resource/upload/stucco/front/") . '/' . $inspection['image_front_building'] . "' style='width: auto; height: 611px;  text-align: center;'></div>";
        }
        $newHtml .= "<div>
      <div class='sectionsigle' style='line-height:25px;'>1. Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?";

        if (!empty($inspection_images['check_front_building1'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br><span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16']</span>
      <br>If Yes, insert closeup photo and indicate location on elevation photo.</p>";

        $image_front_building1 = $this->stuccoImgGet($inspection_id, 'image_front_building1');
        if (count($image_front_building1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
             <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 20px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:25px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }

        $newHtml .= "<div>
      <div class='sectionsigle' style='line-height:25px;'>2.  Any cracks in stucco over wood frame that are potentially excessive?";
        if (!empty($inspection_images['check_front_building2'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br><span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16]</span>
    
<table style='border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 50%; '>If Yes, approximately how many cracks per 10 linear feet?</td><td style='width: 30%; border-bottom: 1px solid;'>" . $inspection_images['text_front_building2'] . "</td></tr></table>
       
       If Yes, insert closeup photo of representative crack and indicate location on elevation photo.<br>";

        $image_front_building2 = $this->stuccoImgGet($inspection_id, 'image_front_building2');
        if (count($image_front_building2) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building2 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
             <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>

      <div class='sectionsigle' style='line-height:25px;'>3. <b> A</b>.Any observed delamination in stucco on either frame or CMU block?";
        if (!empty($inspection_images['check_front_building3'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>If Yes, insert closeup photo of delamination and indicate location on elevation photo";

        $image_front_building2 = $this->stuccoImgGet($inspection_id, 'image_front_building3');
        if (count($image_front_building2) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building2 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<br>
      <b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the delamination";
        if (!empty($inspection_images['check_front_building3_1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>
      If Yes, insert closeup photo of Weep Screed Condition and indicate location on elevation photo.";

        $image_front_building3_1 = $this->stuccoImgGet($inspection_id, 'image_front_building3_1');
        if (count($image_front_building3_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building3_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>

      <div class='sectionsigle' style='line-height:25px;'>4. <b> A</b>.Any observed water intrusion into the home? ";
        if (!empty($inspection_images['check_front_building4'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "
      <br>

<table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 28%; '>If Yes, what evidence of intrusion? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_front_building4'] . "</td></tr></table>
      <br>
      If Yes, insert closeup photo of evidence of water intrusion and indicate location on elevation photo. If interior inspected, insert closeup photo of evidence of interior water intrusion.";

        $image_front_building4 = $this->stuccoImgGet($inspection_id, 'image_front_building4');
        if (count($image_front_building4) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building4 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<br>
      <b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the water intrusion?";
        if (!empty($inspection_images['check_front_building4_1'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>If Yes insert closeup photo of area of intrusion and indicate location on elevation photo.</p>";

        $image_front_building4_1 = $this->stuccoImgGet($inspection_id, 'image_front_building4_1');
        if (count($image_front_building4_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_front_building4_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
   <div><h1 class='book_page' style='text-align: center;'>Right Elevation</h1></div>";


        if (!empty($inspection['image_right_building'])) {
            $newHtml .= "<div style='text-align: center;' class='pdfimage'><img height='611px' src='" . base_url("/resource/upload/stucco/right/") . '/' . $inspection['image_right_building'] . "' style='width: auto; height: 611px;  text-align: center;'></div>";
        }

        $newHtml .= "<div class='sectionsigle' style='line-height:25px;'>5. Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?";
        if (!empty($inspection_images['check_right_building1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='line-height:25px;color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16']</span>
      <br>If Yes, insert closeup photo and indicate location on elevation photo.<br>
      ";

        $image_right_building1 = $this->stuccoImgGet($inspection_id, 'image_right_building1');
        if (count($image_front_building4_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
      
      <div class='sectionsigle' style='line-height:25px;'>6.  Any cracks in stucco over wood frame that are potentially excessive? ";
        if (!empty($inspection_images['check_right_building2'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16]</span> <br>
      
<table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 60%; '>If Yes, approximately how many cracks per 10 linear feet? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_right_building2'] . "</td></tr></table>
      <br>If Yes, insert closeup photo of representative crack and indicate location on elevation photo.
       ";

        $image_right_building2 = $this->stuccoImgGet($inspection_id, 'image_right_building2');
        if (count($image_right_building2) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building2 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
      
      <div class='sectionsigle' style='line-height:25px;'>7. <b> A</b>.Any observed delamination in stucco on either frame or CMU block?";
        if (!empty($inspection_images['check_right_building3'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>If Yes, insert closeup photo of delamination and indicate location on elevation photo";

        $image_right_building3 = $this->stuccoImgGet($inspection_id, 'image_right_building3');
        if (count($image_right_building3) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building3 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " <br><b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the delamination";

        if (!empty($inspection_images['check_right_building3_1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }


        $newHtml .= "<br>If Yes, insert closeup photo of Weep Screed Condition and indicate location on elevation photo.
       ";

        $image_right_building3_1 = $this->stuccoImgGet($inspection_id, 'image_right_building3_1');
        if (count($image_right_building3_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building3_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
      
      <div class='sectionsigle' style='line-height:25px;'>8. <b> A</b>.Any observed water intrusion into the home? ";
        if (!empty($inspection_images['check_right_building4'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "
                 <table><tr><td style='width: 28%; '>If Yes, what evidence of intrusion? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_right_building4'] . "</td></tr></table>

       <br>If Yes, insert closeup photo of evidence of water intrusion and indicate location on elevation photo. If interior inspected, insert closeup photo of evidence of interior water intrusion.<br>";

        $image_right_building4_1 = $this->stuccoImgGet($inspection_id, 'image_right_building4');
        if (count($image_right_building4_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building4_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<br><b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the water intrusion?";

        if (!empty($inspection_images['check_right_building4_1'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "  <br>
      If Yes insert closeup photo of area of intrusion and indicate location on elevation photo.<br>
           ";


        $image_right_building4 = $this->stuccoImgGet($inspection_id, 'image_right_building4_1');
        if (count($image_right_building4) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_right_building4 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
        <div><h1 class='book_page' style='text-align: center;'>Back Elevation</h1></div>";
        if (!empty($inspection['image_back_building'])) {
            $newHtml .= "<div style='text-align: center;' class='pdfimage'><img height='611px' src='" . base_url("/resource/upload/stucco/back/") . '/' . $inspection['image_back_building'] . "' style='width: auto; height: 611px;  text-align: center;'></div>";
        }

        $newHtml .= " <div class='sectionsigle' style='line-height:25px;'>9. Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?  ";
        if (!empty($inspection_images['check_back_building1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16']</span>
      <br>If Yes, insert closeup photo and indicate location on elevation photo.<br>
      ";

        $image_back_building1 = $this->stuccoImgGet($inspection_id, 'image_back_building1');
        if (count($image_back_building1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>
      
     <div class='sectionsigle' style='line-height:25px;'>10.  Any cracks in stucco over wood frame that are potentially excessive? ";
        if (!empty($inspection_images['check_back_building2'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16]</span>
      <br>
       

      <table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 57%; '>If Yes, approximately how many cracks per 10 linear feet? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_back_building2'] . "</td></tr></table>
      <br>If Yes, insert closeup photo of representative crack and indicate location on elevation photo.<br> ";

        $image_back_building2 = $this->stuccoImgGet($inspection_id, 'image_back_building2');
        if (count($image_back_building2) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building2 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " </div>
      <div class='sectionsigle' style='line-height:25px;'>11. <b> A</b>.Any observed delamination in stucco on either frame or CMU block? ";
        if (!empty($inspection_images['check_back_building3'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>  If Yes, insert closeup photo of delamination and indicate location on elevation photo
";

        $image_back_building3 = $this->stuccoImgGet($inspection_id, 'image_back_building3');
        if (count($image_back_building3) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building3 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<br>

      <br><b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the delamination";
        if (!empty($inspection_images['check_back_building3_1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>If Yes, insert closeup photo of Weep Screed Condition and indicate location on elevation photo.
     ";
        $image_back_building3_1 = $this->stuccoImgGet($inspection_id, 'image_back_building3_1');
        if (count($image_back_building3_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building3_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>

      <div class='sectionsigle' style='line-height:25px;'>12. <b> A</b>.Any observed water intrusion into the home? ";
        if (!empty($inspection_images['check_back_building4'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>
  
      <table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 38%; '>If Yes, what evidence of intrusion? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_back_building4'] . "</td></tr></table>
      If Yes, insert closeup photo of evidence of water intrusion and indicate location on elevation photo. If interior inspected, insert closeup photo of evidence of interior water intrusion.<br>";
        $image_back_building4 = $this->stuccoImgGet($inspection_id, 'image_back_building4');
        if (count($image_back_building4) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building4 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<b>B</b> .If Yes, is there a Weep Screed Condition adjacent to the water intrusion?";
        if (!empty($inspection_images['check_back_building4_1'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      If Yes insert closeup photo of area of intrusion and indicate location on elevation photo.
       ";


        $image_back_building4_1 = $this->stuccoImgGet($inspection_id, 'image_back_building4_1');
        if (count($image_back_building4_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_back_building4_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " </div>
      <div><h1 class='book_page' style='text-align: center;'>Left Elevation</h1></div>";
        if (!empty($inspection['image_left_building'])) {
            $newHtml .= "<div style='text-align: center;' class='pdfimage'><img height='611px' src='" . base_url("/resource/upload/stucco/left/") . '/' . $inspection['image_left_building'] . "' style='width: auto; height: 611px;  text-align: center;'></div>";
        }
        $newHtml .= "<div class='sectionsigle' style='line-height:25px;'>13. Any cracks in stucco over wood frame that are greater than or equal to [1/16]'? ";
        if (!empty($inspection_images['check_left_building1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16']</span> <br>
      If Yes, insert closeup photo and indicate location on elevation photo.<br>
       ";

        $image_left_building1 = $this->stuccoImgGet($inspection_id, 'image_left_building1');
        if (count($image_left_building1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " 
      <div class='sectionsigle' style='line-height:25px;'>14.  Any cracks in stucco over wood frame that are potentially excessive?   ";
        if (!empty($inspection_images['check_left_building2'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16]</span>
       <table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 58%; '>If Yes, approximately how many cracks per 10 linear feet?</td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_left_building2'] . "</td></tr></table>
      <br>If Yes, insert closeup photo of representative crack and indicate location on elevation photo.<br>
       ";

        $image_left_building2 = $this->stuccoImgGet($inspection_id, 'image_left_building2');
        if (count($image_left_building2) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building2 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "</div>

      <div class='sectionsigle' style='line-height:25px;'>15. <b> A</b>.Any observed delamination in stucco on either frame or CMU block? ";
        if (!empty($inspection_images['check_left_building3'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>If Yes, insert closeup photo of delamination and indicate location on elevation photo
        ";

        $image_left_building3 = $this->stuccoImgGet($inspection_id, 'image_left_building3');
        if (count($image_left_building3) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building3 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " 
      <br><b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the delamination";
        if (!empty($inspection_images['checke_left_building3_1'])) {

            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>If Yes, insert closeup photo of Weep Screed Condition and indicate location on elevation photo.
          ";

        $image_left_building3_1 = $this->stuccoImgGet($inspection_id, 'image_left_building3_1');
        if (count($image_left_building3_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building3_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= " </div>

      <div class='sectionsigle' style='line-height:25px;'>16. <b> A</b>.Any observed water intrusion into the home? ";
        if (!empty($inspection_images['check_left_building4'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }

        $newHtml .= "<br>
      <table style='text-align: left;border:none;border: none;border-collapse: separate;border-spacing: 0 30px;'><tr><td style='width: 35%; '>If Yes, what evidence of intrusion? </td><td style='width: 51%; border-bottom: 1px solid;'>" . $inspection_images['text_left_building4'] . "</td></tr></table>
      <br>If Yes, insert closeup photo of evidence of water intrusion and indicate location on elevation photo. If interior inspected, insert closeup photo of evidence of interior water intrusion.";

        $image_left_building4 = $this->stuccoImgGet($inspection_id, 'image_left_building4');
        if (count($image_left_building4) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building4 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }


        $newHtml .= "<br><b>B</b>.If Yes, is there a Weep Screed Condition adjacent to the water intrusion?";

        if (!empty($inspection_images['check_left_building4_1'])) {
            $newHtml .= " <label class='yesy'>&#9745;</label> Yes <label class='non unched'>&#9633;</label> No";
        } else {
            $newHtml .= " <label class='yesy unched'> &#9633;</label> Yes <label class='non'>&#9745;</label> No";
        }
        $newHtml .= "<br>If Yes insert closeup photo of area of intrusion and indicate location on elevation photo.</p>
       ";

        $image_left_building4_1 = $this->stuccoImgGet($inspection_id, 'image_left_building4_1');
        if (count($image_left_building4_1) > 0) {
            $newHtml .= "<div><ul class='gridImage'>";
            foreach ($image_left_building4_1 as $item) {
                $newHtml .= "<li style='width: 611px; float: left;list-style:none;display:inline-block;'>
            <img src='" . base_url() . $item['upload_link'] . $item['stucco_value_src'] . "' style='width: auto; margin-top:10px; height: 611px;  text-align: center;'>
            <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 13px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:20px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $item['stucco_comments'] . "</span></p>
   </div>
            </li>";
            }
            $newHtml .= "</ul></div>";
        }

        $newHtml .= "</div> 
   </div> 

   <div style='clear: both; margin: 30px 0 20px;border: 1px solid black;padding: 20px;'>
     <p style='margin:0 0 15px;display:block;'>Comment :</p>
     <p style='line-height:25px;padding-left:20px;'><span style='border-bottom: 1px solid;'>" . $inspection['overall_comments'] . "</span></p>
   </div>

   <div style='margin: 30px 0 20px;' class='signimage'>
     <p style='line-height:25px;padding-left:20px;'>Inspector’s Signature <span style='border-bottom: 1px solid;'>";
        if (!empty($inspection['image_signature'])) {
            $newHtml .= "<div style='text-align: left;' class='pdfimagesign'><img width='250px' src='" . base_url("/resource/upload/stucco/signature/") . '/' . $inspection['image_signature'] . "' style='width: 150px;'></div>";
        }


        $newHtml .= "</span></p>
   </div>
    
     
  </div>
</div>";
        $newHtml .= "<div class='row'></div><div class='row'></div>";

        $html_footer = "<div style='margin-top:30px;'><p style='text-align: center;line-height: 20px;font-size: 14px;color: #808080;'> 24860 Burnt Pine Drive, Suite 3 · Bonita Springs, Florida 34134 · USA · Tel.: 239.949.2405 · Fax: 239.949.3702<br> Email: <span style='color: #83b2e1;'>Info@E3BuildingSciences.com</span> Web: <span style='color:#80cfa4;'>E3GreenBuilding.com</span>   </p></div></body></html>";

        return $html = $html_header . $newHtml . $html_footer;

    }

    public function get_report_html__for_mail($file)
    {
        $html_styles = "<style type='text/css'>.text-center{text-align:center}.row{float:left;width:100%;margin-bottom:20px}.col-50-percent{float:left;width:50%}span{color:#111;padding:2px 7px;font-weight:bold}.label-danger{background:#d9534f;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.label-success{background:#5cb85c;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.label-warning{background:#f0ad4e;color:#fff;font-size:30px;font-weight:bold;padding:5px 2px;text-align:center}.checklist{border:1px solid #000;width:100%}.checklist .location{width:100px;text-align:center}.checklist .status{width:100px;text-align:center}.checklist .item{padding:4px 8px} label.unched { font-size: 36px;}</style>";
        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";

        $html_body = "";

        $template = $this->utility_model->get('sys_config', array('code' => 'report_template'));
        if ($template) {
            $html_body .= "<div class='row'>" . $template['value'] . "</div>";
        }

        $html_body .= "<div class='row'></div>";

        $url = base_url() . $file;
        $html_body .= "<div class='row'>"
            . '<a href="' . $url . '">' . "Attached File" . '</a>'
            . "</div>";

        $html_body .= "<div class='row'></div>";

        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        return $html;
    }

    private function send_mail($subject, $body, $sender, $isHTML = false)
    {
        $mail = new PHPMailer;

        $mail->SMTPDebug = 0;                               // Enable verbose debug output
        $mail->Debugoutput = 'error_log';

        $mail->Timeout = 60;
        $mail->Timelimit = 60;

        //        if (strpos(base_url(), "https://")===false) {
//            $mail->isSMTP();                                      // Set mailer to use SMTP
//        } else {
//            $mail->isMail();                                      // Set mailer to use SMTP
//        }
        $mail->isSMTP();                                      // Set mailer to use SMTP

        $mail->Host = SMTP_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = SMTP_USER;                // SMTP username
        $mail->Password = SMTP_PASSWORD;                         // SMTP password
        $mail->SMTPSecure = '';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = SMTP_PORT;                       // TCP port to connect to

        $mail->From = SMTP_FROM;
        $mail->FromName = SMTP_NAME;

        $recipients = array_map("unserialize", array_unique(array_map("serialize", $sender)));
        foreach ($recipients as $row) {
            $mail->addAddress($row['email']);     // Add a recipient
        }

        $mail->isHTML($isHTML);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "";

        if ($mail->send()) {

        } else {
            return $mail->ErrorInfo;
        }

        return "";
    }

    private function send_mail_with_file($subject, $body, $sender, $file, $isHTML = false)
    {
        $mail = new PHPMailer;

        $mail->SMTPDebug = 0;                               // Enable verbose debug output
        $mail->Debugoutput = 'error_log';

        $mail->Timeout = 60;
        $mail->Timelimit = 60;

        //        if (strpos(base_url(), "https://")===false) {
//            $mail->isSMTP();                                      // Set mailer to use SMTP
//        } else {
//            $mail->isMail();                                      // Set mailer to use SMTP
//        }
        $mail->isSMTP();                                      // Set mailer to use SMTP

        $mail->Host = SMTP_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = SMTP_USER;                // SMTP username
        $mail->Password = SMTP_PASSWORD;                         // SMTP password
        $mail->SMTPSecure = '';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = SMTP_PORT;                       // TCP port to connect to

        $mail->From = SMTP_FROM;
        $mail->FromName = SMTP_NAME;

        $recipients = array_map("unserialize", array_unique(array_map("serialize", $sender)));
        foreach ($recipients as $row) {
            $mail->addAddress($row['email']);     // Add a recipient
        }

        $mail->isHTML($isHTML);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "";

        //        $mail->addAttachment($file);

        if ($mail->send()) {

        } else {
            return $mail->ErrorInfo;
        }

        return "";
    }

    private function send_mail_with_files($subject, $body, $sender, $files, $isHTML = false)
    {
        $mail = new PHPMailer;

        $mail->SMTPDebug = 0;                               // Enable verbose debug output
        $mail->Debugoutput = 'error_log';

        $mail->Timeout = 60;
        $mail->Timelimit = 60;

        //        if (strpos(base_url(), "https://")===false) {
//            $mail->isSMTP();                                      // Set mailer to use SMTP
//        } else {
//            $mail->isMail();                                      // Set mailer to use SMTP
//        }
        $mail->isSMTP();                                      // Set mailer to use SMTP

        $mail->Host = SMTP_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = SMTP_USER;                // SMTP username
        $mail->Password = SMTP_PASSWORD;                         // SMTP password
        $mail->SMTPSecure = '';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = SMTP_PORT;                       // TCP port to connect to

        $mail->From = SMTP_FROM;
        $mail->FromName = SMTP_NAME;

        $recipients = array_map("unserialize", array_unique(array_map("serialize", $sender)));
        foreach ($recipients as $row) {
            $mail->addAddress($row['email']);     // Add a recipient
        }

        $mail->isHTML($isHTML);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "";

        foreach ($files as $file) {
            $mail->addAttachment($file['path'], $file['name']);
        }

        if ($mail->send()) {

        } else {
            return $mail->ErrorInfo;
        }

        return "";
    }

    private function get_location($inspection_id, $location_name, $type)
    {
        $result = array('omit' => 1);

        $location = $this->utility_model->get('ins_location', array('inspection_id' => $inspection_id, 'name' => $location_name));
        if ($location) {
            $location_id = $location['id'];

            $result['omit'] = 0;
            $result['front'] = 0;

            if ($location_name == 'Front') {
                $result['front'] = 1;
            }

            $c = $this->utility_model->get_count('ins_checklist', array('inspection_id' => $inspection_id, 'location_id' => $location_id));
            if ($type == 1) {
                if ($c != 21) {
                    $result['omit'] = 1;
                }
            } else {
                if ($c == 15 || $c == 13) {

                } else {
                    $result['omit'] = 1;
                }
            }

            $result['list'] = array();
            $list = $this->utility_model->get_list__by_sql(" select "
                . " a.no as kind, a.status as stat, a.description as cmt, a.primary_photo, a.secondary_photo "
                . " from ins_checklist a "
                . " where a.inspection_id='$inspection_id' and a.location_id='$location_id' order by a.no ");
            if ($list) {
                foreach ($list as $row) {
                    $row['prm'] = '';
                    if (isset($row['primary_photo']) && $row['primary_photo'] != "") {
                        $row['prm'] = array(
                            'mode' => 2,
                            'img' => $row['primary_photo'],
                        );
                    }

                    $row['snd'] = '';
                    if (isset($row['secondary_photo']) && $row['secondary_photo'] != "") {
                        $row['snd'] = array(
                            'mode' => 2,
                            'img' => $row['secondary_photo'],
                        );
                    }

                    array_push($result['list'], $row);
                }
            }

            return $result;
        }

        return "";
    }

    private function get_comment($inspection_id)
    {
        $list = $this->utility_model->get_list__by_sql(" select "
            . " a.no as kind, a.status as stat, a.description as cmt, a.primary_photo, a.secondary_photo "
            . " from ins_inspection_comment a "
            . " where a.inspection_id='$inspection_id' order by a.no ");

        if ($list) {
            $result = array();
            $result['list'] = array();

            foreach ($list as $row) {
                $row['submit'] = '1';

                $row['prm'] = '';
                if (isset($row['primary_photo']) && $row['primary_photo'] != "") {
                    $row['prm'] = array(
                        'mode' => 2,
                        'img' => $row['primary_photo'],
                    );
                }

                $row['snd'] = '';
                if (isset($row['secondary_photo']) && $row['secondary_photo'] != "") {
                    $row['snd'] = array(
                        'mode' => 2,
                        'img' => $row['secondary_photo'],
                    );
                }

                array_push($result['list'], $row);
            }

            return $result;
        }

        return "";
    }

    private function get_report_data__for_statistics_inspection($region, $community, $start_date, $end_date, $status, $type, $is_array = false, $include_description = true, $table_order = array())
    {
        $reports = array();

        $table = " select  a.*, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " u.first_name, u.last_name, '' as additional "
            . " from ins_region r, ins_code c1, ins_code c2, ins_inspection a "
            . " left join ins_admin u on a.field_manager=u.id and u.kind=2 "
            . " left join ins_community tt on tt.community_id=a.community "
            . " where a.region=r.id and c1.kind='ins' and c1.code=a.type and c2.kind='rst' and c2.code=a.result_code  ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            $param = $region;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.region in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.region in $ids_str ";
                }
            }
        }

        if ($community != "") {
            $param = $community;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.community in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.community in $ids_str ";
                }
            }
        }

        if ($status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.result_code='$status' ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }


        $sql = $table;

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        //array('cols' => $cols, 'index'=>$sCol,'dir'=>$sdir);
        $order_by_part = "";
        if (count($table_order) == 3) {
            $cols = $table_order['cols'];
            $index = $table_order['index'];
            $sdir = $table_order['dir'];
            if ($index < 0 && $index > count($cols) - 1) {
                if (count($cols) < 8) {
                    $index = 7;
                } else {
                    $index = count($cols) - 1;
                }
            }
            $dir = "asc";
            $colName = $cols[$index];
            if ($sdir && strlen($sdir) > 0) {
                if ($sdir != "asc") {
                    $dir = "desc";
                }
            }

            $order_by_part = " order by " . $colName . " " . $dir . " ";
        }

        $count_sql = " select count(*) from ( " . $sql . " ) t ";
        $total = $this->datatable_model->get_count($count_sql);

        $count_text = "<h4 class='total-inspection'>Total: " . $total . "";

        $count_sql = " SELECT c.name AS result_name, t.result_code, t.tnt "
            . " FROM ins_code c, ( select a.result_code, count(*) as tnt from ( $sql ) a group by a.result_code ) t "
            . " WHERE c.kind='rst' AND c.code=t.result_code ORDER BY c.code ";

        $tnt = $this->utility_model->get_list__by_sql($count_sql);
        if ($tnt && is_array($tnt)) {
            foreach ($tnt as $row) {
                if ($count_text != "") {
                    $count_text .= ", ";
                }

                $count_text .= '<span class="total-' . $row['result_code'] . '">';
                $count_text .= $row['result_name'] . ": " . $row['tnt'];
                if ($total != 0) {
                    $tnt = intval($row['tnt']);
                    $count_text .= "(" . round($tnt * 1.0 / $total * 100, 2) . "%)";
                }
                $count_text .= "</span>";
            }
        }

        $count_sql = " select count(*) from ( " . $sql . " and IfNull(a.house_ready,0) = 0 ) t ";
        $house_not_ready = $this->datatable_model->get_count($count_sql);
        if ($count_text != "") {
            $count_text .= ", ";
        }
        $count_text .= '<span class="lbl-house-not-ready">';
        $count_text .= "House Not Ready: " . $house_not_ready;
        $count_text .= "(" . round($house_not_ready * 1.0 / $total * 100, 2) . "%)";

        $count_text .= "</h4>";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img width="322px" alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }
        if ($type == "5") {
            $title = "Stucco  " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        if ($community != "") {
            $c = $this->utility_model->get('ins_community', array('community_id' => $community));
            if ($c) {
                if ($sub_title != "") {
                    $sub_title .= ", ";
                }

                $sub_title .= $c['community_name'];
            }
        }

        $cls = "text-right";

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        if ($count_text != "") {
            $html_body .= $count_text;
        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Type</th>' .
            '<th>Region</th>' .
            '<th>Community</th>' .
            '<th>Job Number</th>' .
            '<th>Address</th>' .
            '<th>Field Manager</th>' .
            ($include_description ? '<th>Description</th>' : '') .
            '<th>Date</th>' .
            '<th>Result</th>' .
            '<th>House Ready</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql = $table;
        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        if (strlen($order_by_part) > 0) {
            $sql .= $order_by_part;
        } else {
            $sql .= " order by a.start_date ";
        }


        if ($include_description) {
            array_push($reports, array(
                'inspection_type' => "Inspection Type",
                'region' => 'Region',
                'community' => 'Community',
                'job_number' => 'Job Number',
                'address' => 'Address',
                'field_manager' => 'Field Manager',
                'description' => 'Description',
                'date' => 'Date',
                'result' => 'Result',
                'house_ready' => 'House Ready',
            ));
        } else {
            array_push($reports, array(
                'inspection_type' => "Inspection Type",
                'region' => 'Region',
                'community' => 'Community',
                'job_number' => 'Job Number',
                'address' => 'Address',
                'field_manager' => 'Field Manager',
                'date' => 'Date',
                'result' => 'Result',
                'house_ready' => 'House Ready',
            ));
        }

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . " " . $row['last_name'];
                }

                // replace community name.  2016/11/3
                $community_name = ""; // $row['community'];
                if (isset($row['community_name']) && $row['community_name'] != "") {
                    $community_name = $row['community_name'];
                }

                $html_body .= '<td class="text-center">' . $row['inspection_type'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['region_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $community_name . '</td>';
                $html_body .= '<td class="text-center">' . $row['job_number'] . '</td>';
                $html_body .= '<td>' . $row['address'] . '</td>';
                $html_body .= '<td class="text-center">' . $field_manager . '</td>';

                if ($include_description) {
                    $html_body .= '<td>' . $row['overall_comments'] . '</td>';
                }

                $html_body .= '<td class="text-center">' . $row['start_date'] . '</td>';

                $cls = "";
                if ($row['result_code'] == '1') {
                    $cls = "label-success";
                }
                if ($row['result_code'] == '2') {
                    $cls = "label-warning";
                }
                if ($row['result_code'] == '3') {
                    $cls = "label-danger";
                }

                $html_body .= '<td class="text-center"><span class="label ' . $cls . '">' . $row['result_name'] . '</span></td>';
                $html_body .= '<td class="text-center"><span class="">' . ($row['house_ready'] == 1 ? "House Ready" : "House Not Ready") . '</span></td>';

                $html_body .= '</tr>';

                if ($include_description) {
                    array_push($reports, array(
                        'inspection_type' => $row['inspection_type'],
                        'region' => $row['region_name'],
                        'community' => $community_name,
                        'job_number' => $row['job_number'],
                        'address' => $row['address'],
                        'field_manager' => $field_manager,
                        'description' => $row['overall_comments'],
                        'date' => $row['start_date'],
                        'result' => $row['result_name'],
                        'house_ready' => $row['house_ready'] == 1 ? "House Ready" : "House Not Ready"
                    ));
                } else {
                    array_push($reports, array(
                        'inspection_type' => $row['inspection_type'],
                        'region' => $row['region_name'],
                        'community' => $community_name,
                        'job_number' => $row['job_number'],
                        'address' => $row['address'],
                        'field_manager' => $field_manager,
                        'date' => $row['start_date'],
                        'result' => $row['result_name'],
                        'house_ready' => $row['house_ready'] == 1 ? "House Ready" : "House Not Ready"
                    ));
                }
            }
        }


        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_statistics_re_inspection($region, $community, $start_date, $end_date, $status, $type, $is_array = false, $include_description = true, $table_order = array())
    {
        $reports = array();

        $table = " ins_region r, ins_code c1, ins_code c2,  "
            . " ( SELECT p1.inspection_id, p2.* "
            . "   FROM "
            . "    ( SELECT MAX(t.id) AS inspection_id, t.job_number, bbb.address, t.type FROM ins_inspection t LEFT JOIN ins_building_unit bbb ON REPLACE(t.job_number,'-','')=REPLACE(bbb.job_number, '-', '') AND bbb.address=t.address and t.is_building_unit=1 GROUP BY t.job_number, bbb.address, t.type ) p1, "
            . "    ( SELECT t.type, t.job_number, bbb.address, MAX(t.start_date) AS inspection_date, COUNT(*) AS inspection_count  FROM ins_inspection t  LEFT JOIN ins_building_unit bbb ON REPLACE(t.job_number,'-','')=REPLACE(bbb.job_number, '-', '') AND bbb.address=t.address and t.is_building_unit=1 GROUP BY t.job_number, bbb.address, t.type ) p2 "
            . "   WHERE p1.type=p2.type AND p1.job_number=p2.job_number AND ((p1.address IS NULL AND p2.address IS NULL) OR p1.address=p2.address) "
            . " ) g "
            . " LEFT JOIN ins_inspection a ON g.inspection_id=a.id "
            . " LEFT JOIN ins_inspection_requested q ON a.requested_id=q.id "
            . " LEFT JOIN ins_admin u ON a.field_manager=u.id AND u.kind=2 "
            . " LEFT JOIN ins_community tt ON tt.community_id=a.community "
            . " WHERE a.region=r.id AND c1.kind='ins' AND c1.code=a.type AND c2.kind='rst' "
            . " AND c2.code=a.result_code  AND g.inspection_count>1 "
            . " ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            $param = $region;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.region in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.region in $ids_str ";
                }
            }
        }

        if ($community != "") {
            $param = $community;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.community in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.community in $ids_str ";
                }
            }
        }

        if ($status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.result_code='$status' ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }


        $sql = " select  a.*, "
            . " (g.inspection_count-1) as inspection_count, q.epo_number as requested_epo_number, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " u.first_name, u.last_name, '' as additional "
            . " from " . $table . " ";

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $order_by_part = "";
        if (count($table_order) == 3) {
            $cols = $table_order['cols'];
            $index = $table_order['index'];
            $sdir = $table_order['dir'];
            if ($index < 0 && $index > count($cols) - 1) {
                if (count($cols) < 8) {
                    $index = 7;
                } else {
                    $index = count($cols) - 1;
                }
            }
            $dir = "asc";
            $colName = $cols[$index];
            if ($sdir && strlen($sdir) > 0) {
                if ($sdir != "asc") {
                    $dir = "desc";
                }
            }

            $order_by_part = " order by " . $colName . " " . $dir . " ";
        }

        $count_sql = " select count(*) from ( " . $sql . " ) ttt ";
        $total = $this->datatable_model->get_count($count_sql);

        $count_text = "<h4 class='total-inspection'>Total: " . $total . "";

        $count_sql = " SELECT c.name AS result_name, t.result_code, t.tnt "
            . " FROM ins_code c, ( select a.result_code, count(*) as tnt from ( $sql ) a group by a.result_code ) t "
            . " WHERE c.kind='rst' AND c.code=t.result_code ORDER BY c.code ";

        $tnt = $this->utility_model->get_list__by_sql($count_sql);
        if ($tnt && is_array($tnt)) {
            foreach ($tnt as $row) {
                if ($count_text != "") {
                    $count_text .= ", ";
                }

                $count_text .= '<span class="total-' . $row['result_code'] . '">';
                $count_text .= $row['result_name'] . ": " . $row['tnt'];
                if ($total != 0) {
                    $tnt = intval($row['tnt']);
                    $count_text .= "(" . round($tnt * 1.0 / $total * 100, 2) . "%)";
                }
                $count_text .= "</span>";
            }
        }

        $count_text .= "</h4>";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }
        if ($type == "5") {
            $title = "Stucco " . $title;
        }
        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        if ($community != "") {
            $c = $this->utility_model->get('ins_community', array('community_id' => $community));
            if ($c) {
                if ($sub_title != "") {
                    $sub_title .= ", ";
                }

                $sub_title .= $c['community_name'];
            }
        }

        $cls = "text-right";

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        if ($count_text != "") {
            $html_body .= $count_text;
        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Type</th>' .
            '<th>Region</th>' .
            '<th>Community</th>' .
            '<th>Job Number</th>' .
            '<th>Address</th>' .
            '<th>Field Manager</th>' .
            ($include_description ? '<th>Description</th>' : '') .
            '<th>Date</th>' .
            '<th>EPO Number</th>' .
            '<th>Re-Inspections</th>' .
            '<th>Result</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql = " select  a.*, "
            . " (g.inspection_count-1) as inspection_count, q.epo_number, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " u.first_name, u.last_name, '' as additional "
            . " from " . $table . " ";
        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        if (strlen($order_by_part) > 0) {
            $sql .= $order_by_part;
        } else {
            $sql .= " order by g.inspection_count desc ";
        }


        if ($include_description) {
            array_push($reports, array(
                'inspection_type' => "Inspection Type",
                'region' => 'Region',
                'community' => 'Community',
                'job_number' => 'Job Number',
                'address' => 'Address',
                'field_manager' => 'Field Manager',
                'description' => 'Description',
                'date' => 'Date',
                'epo_number' => 'EPO Number',
                're_inspections' => 'Re-Inspections',
                'result' => 'Result',
            ));
        } else {
            array_push($reports, array(
                'inspection_type' => "Inspection Type",
                'region' => 'Region',
                'community' => 'Community',
                'job_number' => 'Job Number',
                'address' => 'Address',
                'field_manager' => 'Field Manager',
                'date' => 'Date',
                'epo_number' => 'EPO Number',
                're_inspections' => 'Re-Inspections',
                'result' => 'Result',
            ));
        }

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                // replace community name.  2016/11/3
                $community_name = ""; // $row['community'];
                if (isset($row['community_name']) && $row['community_name'] != "") {
                    $community_name = $row['community_name'];
                }

                $html_body .= '<td class="text-center">' . $row['inspection_type'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['region_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $community_name . '</td>';
                $html_body .= '<td class="text-center">' . $row['job_number'] . '</td>';
                $html_body .= '<td>' . $row['address'] . '</td>';
                $html_body .= '<td class="text-center">' . $field_manager . '</td>';

                if ($include_description) {
                    $html_body .= '<td>' . $row['overall_comments'] . '</td>';
                }

                $html_body .= '<td class="text-center">' . $row['start_date'] . '</td>';

                $epo_number = "";
                if (isset($row['epo_number']) && $row['epo_number'] != "") {
                    $epo_number = $row['epo_number'];
                } else {
                    $epo_number = isset($row['requested_epo_number']) && $row['requested_epo_number'] != 0 ? $row['requested_epo_number'] : "";
                }

                $html_body .= '<td class="text-center">' . $epo_number . '</td>';
                $html_body .= '<td class="text-center">' . $row['inspection_count'] . '</td>';

                $cls = "";
                if ($row['result_code'] == '1') {
                    $cls = "label-success";
                }
                if ($row['result_code'] == '2') {
                    $cls = "label-warning";
                }
                if ($row['result_code'] == '3') {
                    $cls = "label-danger";
                }

                $html_body .= '<td class="text-center"><span class="label ' . $cls . '">' . $row['result_name'] . '</span></td>';

                $html_body .= '</tr>';

                if ($include_description) {
                    array_push($reports, array(
                        'inspection_type' => $row['inspection_type'],
                        'region' => $row['region_name'],
                        'community' => $community_name,
                        'job_number' => $row['job_number'],
                        'address' => $row['address'],
                        'field_manager' => $field_manager,
                        'description' => $row['overall_comments'],
                        'date' => $row['start_date'],
                        'epo_number' => $epo_number,
                        're_inspections' => $row['inspection_count'],
                        'result' => $row['result_name']
                    ));
                } else {
                    array_push($reports, array(
                        'inspection_type' => $row['inspection_type'],
                        'region' => $row['region_name'],
                        'community' => $community_name,
                        'job_number' => $row['job_number'],
                        'address' => $row['address'],
                        'field_manager' => $field_manager,
                        'date' => $row['start_date'],
                        'epo_number' => $epo_number,
                        're_inspections' => $row['inspection_count'],
                        'result' => $row['result_name']
                    ));
                }
            }
        }


        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_statistics_checklist($region, $community, $start_date, $end_date, $status, $type, $is_array = false)
    {
        $reports = array();

        $table = " select  a.*, "
            . " c1.name as inspection_type, c2.name as status_name, c3.name as item_name, ch.no as item_no, ch.status as status_code, "
            . " r.region as region_name, loc.name as location_name, "
            . " u.first_name, u.last_name, '' as additional "
            . " from ins_region r, ins_code c1, ins_code c2, ins_code c3, ins_location loc, ins_checklist ch, ins_inspection a "
            . " left join ins_admin u on a.field_manager=u.id and u.kind=2 "
            . " where a.region=r.id and c1.kind='ins' and c1.code=a.type and c2.kind='sts' and c2.code=ch.status "
            . " and loc.inspection_id=a.id and ch.inspection_id=a.id and ch.location_id=loc.id and c3.value=a.type and (c3.kind='drg' or c3.kind='lth') and c3.code=ch.no ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            $param = $region;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.region in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.region in $ids_str ";
                }
            }
        }

        if ($community != "") {
            $param = $community;
            if (gettype($param) == 'string') {
                if ($common_sql != "") {
                    $common_sql .= " and ";
                }
                $common_sql .= " a.community in ($param) ";
            } else if (gettype($param) == 'array') {
                $ids_str = "";
                foreach ($param as $id) {
                    $ids_str = $ids_str . $id . ",";
                }
                if (strlen($ids_str) > 0) {
                    $ids_str = substr($ids_str, 0, strlen($ids_str) - 1);
                    $ids_str = "(" . $ids_str . ")";
                }
                if (strlen($ids_str) > 0) {
                    if ($common_sql != "") {
                        $common_sql .= " and ";
                    }
                    $common_sql .= " a.community in $ids_str ";
                }
            }
        }

        if ($status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " ch.status='$status' ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }

        $sql = $table;
        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $count_sql = " select count(*) from ( " . $sql . " ) t ";
        $total = $this->datatable_model->get_count($count_sql);

        $sql .= " and (ch.status=1 or ch.status=2 or ch.status=3) ";

        $count_text = "Total: " . $total . "";

        //        if ($status=="") {
        $count_sql = " SELECT c.name AS status_name, t.status_code, t.tnt "
            . " FROM ins_code c, ( select a.status_code, count(*) as tnt from ( $sql ) a group by a.status_code ) t "
            . " WHERE c.kind='sts' AND c.code=t.status_code ORDER BY c.code ";

        $tnt = $this->utility_model->get_list__by_sql($count_sql);
        if ($tnt && is_array($tnt)) {
            foreach ($tnt as $row) {
                if ($count_text != "") {
                    $count_text .= ", ";
                }

                if ($is_array) {

                } else {
                    $count_text .= '<span class="total-' . $row['status_code'] . '">';
                }

                $count_text .= $row['status_name'] . ": " . $row['tnt'];
                if ($total != 0) {
                    $tnt = intval($row['tnt']);
                    $count_text .= "(" . round($tnt * 1.0 / $total * 100, 2) . "%)";
                }

                if ($is_array) {

                } else {
                    $count_text .= '</span>';
                }
            }
        }

        array_push($reports, array(
            'title' => $count_text,
            'count' => '',
        ));
        array_push($reports, array(
            'title' => '',
            'count' => '',
        ));

        //        }

        $sql .= " order by a.start_date ";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        if ($community != "") {
            $c = $this->utility_model->get('ins_community', array('community_id' => $community));
            if ($c) {
                if ($sub_title != "") {
                    $sub_title .= ", ";
                }

                $sub_title .= $c['community_name'];
            }
        }

        $cls = "text-right";

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        if ($count_text != "") {
            $html_body .= "<h4 class='total-checklist'>" . $count_text . "</h4>";
        }

        $top_sql = " select  a.*, "
            . " c1.name as inspection_type, c2.name as status_name, c3.name as item_name, ch.no as item_no, ch.status as status_code, "
            . " r.region as region_name, loc.name as location_name, "
            . " u.first_name, u.last_name, '' as additional "
            . " from ins_region r, ins_code c1, ins_code c2, ins_code c3, ins_location loc, ins_checklist ch, ins_inspection a left join ins_user u on a.field_manager=u.id "
            . " where a.region=r.id and c1.kind='ins' and c1.code=a.type and c2.kind='sts' and c2.code=ch.status "
            . " and loc.inspection_id=a.id and ch.inspection_id=a.id and ch.location_id=loc.id and c3.value=a.type and c3.code=ch.no and (ch.status=1 or ch.status=2 or ch.status=3) ";

        if ($common_sql != "") {
            $top_sql .= " and " . $common_sql;
        }

        $top_content = $this->get_top_item($top_sql, 'drg', 1, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => 'Most Passed in Drainage Plane Inspection',
                    'count' => '',
                ));
                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Passed in Drainage Plane Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        $top_content = $this->get_top_item($top_sql, 'drg', 2, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => '',
                    'count' => '',
                ));
                array_push($reports, array(
                    'title' => 'Most Failed in Drainage Plane Inspection',
                    'count' => '',
                ));
                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Failed in Drainage Plane Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        $top_content = $this->get_top_item($top_sql, 'drg', 3, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => '',
                    'count' => '',
                ));
                array_push($reports, array(
                    'title' => 'Most Not Ready in Drainage Plane Inspection',
                    'count' => '',
                ));
                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Not Ready in Drainage Plane Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        $top_content = $this->get_top_item($top_sql, 'lth', 1, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => '',
                    'count' => '',
                ));
                array_push($reports, array(
                    'title' => 'Most Passed in Lath Inspection',
                    'count' => '',
                ));
                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Passed in Lath Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        $top_content = $this->get_top_item($top_sql, 'lth', 2, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => '',
                    'count' => '',
                ));
                array_push($reports, array(
                    'title' => 'Most Failed in Lath Inspection',
                    'count' => '',
                ));

                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Failed in Lath Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        $top_content = $this->get_top_item($top_sql, 'lth', 3, $is_array);
        if ($is_array) {
            if (count($top_content) > 0) {
                array_push($reports, array(
                    'title' => '',
                    'count' => '',
                ));
                array_push($reports, array(
                    'title' => 'Most Not Ready in Lath Inspection',
                    'count' => '',
                ));
                foreach ($top_content as $row) {
                    array_push($reports, $row);
                }
            }
        } else {
            if ($top_content != "") {
                $html_body .= '<div class="row">';

                $html_body .= '<table class="data-table table-bordered">';
                $html_body .= '' .
                    '<thead>' .
                    '<tr>' .
                    '<th colspan="2">Most Not Ready in Lath Inspection</th>' .
                    '</tr>' .
                    '</thead>' .
                    '';
                $html_body .= '<tbody>';
                $html_body .= $top_content;
                $html_body .= '</tbody>';
                $html_body .= '</table>';

                $html_body .= '</div>';
            }
        }

        //        $html_body .= '<div class="row" style="margin-top: 25px;">';
        //
        //        $html_body .= '<table class="data-table table-bordered">';
        //        $html_body .= '' .
        //                '<thead>' .
        //                    '<tr>' .
        //                        '<th>Type</th>' .
        //                        '<th>Region</th>' .
        //                        '<th>Community</th>' .
        //                        '<th>Date</th>' .
        //                        '<th>Location</th>' .
        //                        '<th style="width:50%;">CheckItem</th>' .
        //                        '<th>Result</th>' .
        //                    '</tr>' .
        //                '</thead>' .
        //                '';
        //
        //        $html_body .= '<tbody>';
        //
        //
        //        $data = $this->datatable_model->get_content($sql);
        //        if ($data && is_array($data)) {
        //            foreach ($data as $row) {
        //                $html_body .= '<tr>';
        //
        //                $field_manager = "";
        //                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name']!="" && $row['last_name']!="") {
        //                    $field_manager = $row['first_name'] . $row['last_name'];
        //                }
        //
        //                $html_body .= '<td class="text-center">' . $row['inspection_type']  . '</td>';
        //                $html_body .= '<td class="text-center">' . $row['region_name']  . '</td>';
        //                $html_body .= '<td class="text-center">' . $row['community']  . '</td>';
        //                $html_body .= '<td class="text-center">' . $row['start_date']  . '</td>';
        //                $html_body .= '<td class="text-center">' . $row['location_name']  . '</td>';
        //                $html_body .= '<td class="">' . $row['item_name']  . '</td>';
        //
        //                $cls = "";
        //                if ($row['status_code'] == '1')
        //                    $cls = "label-success";
        //                if ($row['status_code'] == '3')
        //                    $cls = "label-warning";
        //                if ($row['status_code'] == '2')
        //                    $cls = "label-danger";
        //
        //                $html_body .= '<td class="text-center"><span class="label '. $cls  . '">' . $row['status_name']  . '</span></td>';
        //
        //
        //                $html_body .= '</tr>';
        //            }
        //        }
        //
        //
        //        $html_body .= '</tbody>';
        //        $html_body .= '</table>';
        //
        //        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_top_item($sql, $inspection_type, $status, $is_array = false)
    {
        $reports = array();
        $result = "";
        $sql .= " and (c3.kind='$inspection_type') and ch.status='$status' ";

        $count_sql = " SELECT c.name AS item_name, t.item_no, t.tnt "
            . " FROM ins_code c, ( select a.status_code, a.item_no, count(*) as tnt from ( $sql ) a group by a.status_code, a.item_no ) t "
            . " WHERE c.kind='$inspection_type' and t.status_code='$status' and c.code=t.item_no "
            . " ORDER BY t.tnt desc "
            . " LIMIT 10 ";

        $top = $this->utility_model->get_list__by_sql($count_sql);
        if ($top && is_array($top)) {
            foreach ($top as $row) {
                $result .= '<tr>';
                $result .= '<td>' . $row['item_no'] . ". " . $row['item_name'] . '</td>';
                $result .= '<td class="text-center">' . $row['tnt'] . '</td>';
                $result .= '</tr>';

                array_push($reports, array(
                    'title' => $row['item_no'] . ". " . $row['item_name'],
                    'count' => $row['tnt'],
                ));
            }
        }

        if ($is_array) {
            return $reports;
        } else {
            return $result;
        }
    }

    private function get_scheduling_data($inspector_id, $region, $community, $start_date, $end_date, $ordering, $status)
    {
        $result = array();

        $cols = array("a.requested_at", "a.community_name", "a.job_number", "a.address", "c.city", "m.first_name", "a.category", "a.time_stamp");

        $common_sql = "";

        if ($inspector_id !== false && $inspector_id != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.inspector_id='$inspector_id' ";
        }

        if ($status !== false && $status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.status ='$status' ";
        }

        if ($start_date !== false && $start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.requested_at>='$start_date' ";
        }

        if ($end_date !== false && $end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.requested_at<='$end_date' ";
        }

        if ($region !== false && $region != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " c.region='$region' ";
        }

        if ($community !== false && $community != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " substr(a.job_number,1,4)='$community' ";
        }

        $order_sql = "";
        if ($ordering !== false && $ordering != "") {
            $order_item = explode(",,", $ordering);
            if (is_array($order_item)) {
                foreach ($order_item as $row) {
                    $order_cell = explode(",", $row);
                    if (is_array($order_cell) && count($order_cell) == 2) {
                        $col = intval($order_cell[0]);
                        $dir = $order_cell[1];

                        if ($col < 0 || $col > 7) {
                            $col = 0;
                        }

                        if ($order_sql != "") {
                            $order_sql .= ", ";
                        }

                        $order_sql .= $cols[$col] . " " . $dir . " ";
                    }
                }

                if ($order_sql != "") {
                    $order_sql = " order by " . $order_sql;
                }
            }
        }

        $table = " ins_inspection_requested a "
            . " left join ins_community c on c.community_id=substr(a.job_number,1,4)"
            . " left join ins_region r on c.region=r.id "
            . " left join ins_admin m on a.manager_id=m.id "
            . " ";

        $sql = " select  a.id, a.category, a.reinspection, a.epo_number, a.job_number, a.requested_at, a.assigned_at, a.completed_at, a.manager_id, a.inspector_id, "
            . " a.time_stamp, a.ip_address, a.community_name, a.lot, a.address, a.status, a.area, a.volume, a.qn, a.city as city_duct, "
            . " concat(m.first_name, ' ', m.last_name) as field_manager_name, "
            . " c1.name as category_name, c.community_id, c.region, r.region as region_name, c.city, "
            . " u.first_name, u.last_name "
            . " from ins_user u, ins_code c1, " . $table . " where u.id=a.inspector_id and c1.kind='ins' and c1.code=a.category and a.status=1 ";

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $sql .= $order_sql;
        $data = $this->datatable_model->get_content($sql);

        if ($data && is_array($data)) {
            $header = array();
            if ($inspector_id == "") {
                $header['inspector'] = "Inspector";
            }
            $header['requested_at'] = "Inspection Date";
            $header['community'] = "Community";
            $header['job_number'] = "Job Number";
            $header['address'] = "Address";
            $header['city'] = "City";
            $header['field_manager'] = "Field Manager";
            $header['inspection_type'] = "Inspection Type";
            $header['time_stamp'] = "Requested Time";

            array_push($result, $header);

            $last_inspector_id = "";
            foreach ($data as $row) {
                $item = array();

                if ($inspector_id == "") {
                    if ($last_inspector_id == "" || $last_inspector_id != $row['inspector_id']) {
                        $last_inspector_id = $row['inspector_id'];

                        $item['inspector'] = $row['first_name'] . " " . $row['last_name'];
                        $item['requested_at'] = "";
                        $item['community'] = "";
                        $item['job_number'] = "";
                        $item['address'] = "";
                        $item['city'] = "";
                        $item['field_manager'] = "";
                        $item['inspection_type'] = "";
                        $item['time_stamp'] = "";

                        array_push($result, $item);
                    }
                }

                if ($inspector_id == "") {
                    $item['inspector'] = "";
                }

                $item['requested_at'] = $row['requested_at'];
                $item['community'] = $row['community_name'];
                $item['job_number'] = $row['job_number'];
                $item['address'] = $row['address'];
                $item['city'] = $row['city'];
                if ($row['category'] == 3) {
                    $item['city'] = $row['city_duct'];
                }

                $item['field_manager'] = $row['field_manager_name'];
                $item['inspection_type'] = $row['category_name'];
                $item['time_stamp'] = mdate('%Y-%m-%d %H:%i:%s', strtotime($row['time_stamp']));

                array_push($result, $item);
            }
        }

        return $result;
    }

    private function get_report_data__for_statistics_fieldmanager($region, $start_date, $end_date, $type, $is_array = false)
    {
        $reports = array();

        $table = " ins_admin a where a.kind=2 ";
        //        $table = " ( select field_manager from ins_building group by field_manager ) b "
        //                . " left join ins_admin a on a.kind=2 and concat(a.first_name, ' ', a.last_name)=b.field_manager"
        //                . " left join ins_region r on r.id=a.region where b.field_manager is not null and b.field_manager<>'' ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.region='$region' ";
            $table .= " and a.id in ( select manager_id from ins_admin_region where region='$region' ) ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }

        $sql = " select  a.* "
            . " from " . $table . " "
            . " order by a.first_name ";

        $data = $this->datatable_model->get_content($sql);
        $table_data = array();

        foreach ($data as $row) {
            if ($row['status'] != 1 || $row['testflag'] != 0) {
                continue;
            }
            $region_name = "";
            $sql = " select r.region from ins_admin_region a, ins_region r where a.manager_id='" . $row['id'] . "' and a.region=r.id ";
            $regions = $this->utility_model->get_list__by_sql($sql);
            if ($regions) {
                foreach ($regions as $rrr) {
                    if ($region_name != "") {
                        $region_name .= ", ";
                    }
                    $region_name .= $rrr['region'];
                }
            }
            $row['region_name'] = $region_name;

            $inspections = 0;
            if (isset($row['id']) && $row['id'] != '') {
                $sql = " select count(*) from ins_inspection a where a.field_manager='" . $row['id'] . "' ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }
                $inspections = $this->datatable_model->get_count($sql);
                $row['inspections'] = $inspections;
            } else {

            }

            if ($inspections == 0) {
                $row['not_ready'] = 0;
                $row['pass'] = 0;
                $row['pass_with_exception'] = 0;
                $row['fail'] = 0;
                $row['reinspection'] = 0;
            } else {
                $sql = " select count(*) from ins_inspection a where a.field_manager='" . $row['id'] . "' and IfNull(a.house_ready,0) = '0' ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $not_ready = $this->datatable_model->get_count($sql);
                $row['not_ready'] = round($not_ready * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.field_manager='" . $row['id'] . "' and a.result_code=1 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $pass = $this->datatable_model->get_count($sql);
                $row['pass'] = round($pass * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.field_manager='" . $row['id'] . "' and a.result_code=2 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $pass_with_exception = $this->datatable_model->get_count($sql);
                $row['pass_with_exception'] = round($pass_with_exception * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.field_manager='" . $row['id'] . "' and a.result_code=3 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $fail = $this->datatable_model->get_count($sql);
                $row['fail'] = round($fail * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a left join ins_inspection_requested r on a.requested_id=r.id where a.field_manager='" . $row['id'] . "' and r.reinspection=1 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $reinspection = $this->datatable_model->get_count($sql);
                $row['reinspection'] = round($reinspection * 1.0 / $inspections * 100.0, 2);
            }

            array_push($table_data, $row);
        }


        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        $cls = "text-right";
        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        $html_body .= '<div class="row" style="margin-top: 10px;">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Field Manager</th>' .
            '<th>Region</th>' .
            '<th>Total Inspections</th>' .
            '<th>Not Ready(%)</th>' .
            '<th>Pass(%)</th>' .
            '<th>Pass with Exception(%)</th>' .
            '<th>Fail(%)</th>' .
            '<th>Reinspections(%)</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        array_push($reports, array(
            'field_manager' => 'Field Manager',
            'region' => 'Region',
            'inspections' => 'Total Inspections',
            'not_ready' => 'Not Ready(%)',
            'pass' => 'Pass(%)',
            'pass_with_exception' => 'Pass With Exception(%)',
            'fail' => 'Fail(%)',
            'reinspections' => 'Reinspections(%)',
        ));

        if ($table_data && is_array($table_data)) {
            foreach ($table_data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                //                $field_manager = $row['field_manager'];
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                $html_body .= '<td class="text-center">' . $field_manager . '</td>';
                $html_body .= '<td class="text-center">' . $row['region_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['inspections'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['not_ready'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['pass'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['pass_with_exception'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['fail'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['reinspection'] . '</td>';

                $html_body .= '</tr>';

                array_push($reports, array(
                    'field_manager' => $field_manager,
                    'region' => $row['region_name'],
                    'inspections' => $row['inspections'],
                    'not_ready' => $row['not_ready'],
                    'pass' => $row['pass'],
                    'pass_with_exception' => $row['pass_with_exception'],
                    'fail' => $row['fail'],
                    'reinspections' => $row['reinspection'],
                ));
            }
        }

        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';

        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_statistics_inspector($region, $start_date, $end_date, $type, $is_array = false)
    {
        $reports = array();
        $table = " ins_user a ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.region='$region' ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }

        $sql = " select  a.* "
            . " from " . $table . " "
            . " order by a.first_name ";

        $data = $this->datatable_model->get_content($sql);
        $table_data = array();

        foreach ($data as $row) {
            $sql = " select count(*) from ins_inspection a where a.user_id='" . $row['id'] . "' ";
            if ($common_sql != "") {
                $sql .= " and " . $common_sql;
            }
            $inspections = $this->datatable_model->get_count($sql);
            $row['inspections'] = $inspections;
            $row['fee'] = number_format($row['fee'] * $inspections, 2);

            if ($inspections == 0) {
                $row['not_ready'] = 0;
                $row['pass'] = 0;
                $row['pass_with_exception'] = 0;
                $row['fail'] = 0;
                $row['reinspection'] = 0;
            } else {
                $sql = " select count(*) from ins_inspection a where a.user_id='" . $row['id'] . "' and IfNull(a.house_ready,0)='0' ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $not_ready = $this->datatable_model->get_count($sql);
                $row['not_ready'] = round($not_ready * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.user_id='" . $row['id'] . "' and a.result_code=1 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $pass = $this->datatable_model->get_count($sql);
                $row['pass'] = round($pass * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.user_id='" . $row['id'] . "' and a.result_code=2 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $pass_with_exception = $this->datatable_model->get_count($sql);
                $row['pass_with_exception'] = round($pass_with_exception * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a where a.user_id='" . $row['id'] . "' and a.result_code=3 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $fail = $this->datatable_model->get_count($sql);
                $row['fail'] = round($fail * 1.0 / $inspections * 100.0, 2);


                $sql = " select count(*) from ins_inspection a left join ins_inspection_requested r on a.requested_id=r.id where a.user_id='" . $row['id'] . "' and r.reinspection=1 ";
                if ($common_sql != "") {
                    $sql .= " and " . $common_sql;
                }

                $reinspection = $this->datatable_model->get_count($sql);
                $row['reinspection'] = round($reinspection * 1.0 / $inspections * 100.0, 2);
            }

            array_push($table_data, $row);
        }


        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        $cls = "text-right";
        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        $html_body .= '<div class="row" style="margin-top: 10px;">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Inspector</th>' .
            '<th>Total Inspections</th>' .
            '<th>Not Ready(%)</th>' .
            '<th>Pass(%)</th>' .
            '<th>Pass with Exception(%)</th>' .
            '<th>Fail(%)</th>' .
            '<th>Reinspections(%)</th>' .
            '<th>Total Fee($)</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        array_push($reports, array(
            'inspector' => 'Inspector',
            'inspections' => 'Total Inspections',
            'not_ready' => 'Not Ready(%)',
            'pass' => 'Pass(%)',
            'pass_with_exception' => 'Pass With Exception(%)',
            'fail' => 'Fail(%)',
            'reinspections' => 'Reinspections(%)',
            'fee' => 'Total Fee($)',
        ));

        if ($table_data && is_array($table_data)) {
            foreach ($table_data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                $html_body .= '<td class="text-center">' . $field_manager . '</td>';
                $html_body .= '<td class="text-center">' . $row['inspections'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['not_ready'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['pass'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['pass_with_exception'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['fail'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['reinspection'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['fee'] . '</td>';

                $html_body .= '</tr>';

                array_push($reports, array(
                    'inspector' => $field_manager,
                    'inspections' => $row['inspections'],
                    'not_ready' => $row['not_ready'],
                    'pass' => $row['pass'],
                    'pass_with_exception' => $row['pass_with_exception'],
                    'fail' => $row['fail'],
                    'reinspections' => $row['reinspection'],
                    'fee' => $row['fee'],
                ));
            }
        }


        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    public function pdftest()
    {

        $pdftest_path = $_SERVER['DOCUMENT_ROOT'] . '/resource/upload/report/';
        $filepath = $pdftest_path . 'pdf.html';

        $param1 = $this->input->get_post('mode');
        if ($param1) {
            switch ($param1) {
                case '1':
                    $filepath = $pdftest_path . 'pdf1.html';
                    $msg = array();
                    $msg['tmp_name'] = $_FILES;
                    // var_dump($msg);

                    $html = file_get_contents($_FILES['file']['tmp_name']);
                    // echo $html;


                    file_put_contents($filepath, $html);

                    echo $filepath;
                    break;
                case '2':
                    $filepath = $pdftest_path . 'pdf2.html';
                    $msg = array();
                    $msg['tmp_name'] = $_FILES;
                    // var_dump($msg);

                    $html = file_get_contents($_FILES['file']['tmp_name']);
                    // echo $html;


                    file_put_contents($filepath, $html);

                    echo $filepath;
                    break;

                default:
                    $filepath = $pdftest_path . 'pdf1.html';
                    $msg = array();
                    $msg['tmp_name'] = $_FILES;
                    // var_dump($msg);

                    $html = file_get_contents($_FILES['file']['tmp_name']);

                    header("Content-Type: application/pdf");
                    header("Cache-Control: max-age=0");
                    header("Accept-Ranges: none");
                    header("Content-Disposition: attachment; filename=\"google_com.pdf\"");

                    $this->m_pdf->pdf->WriteHTML($html);
                    $this->m_pdf->pdf->Output("report.pdf", "D");

                    // echo $html;

                    break;
            }
            return;
        }

        $param1 = $this->input->get('mode');

        header("Content-Type: application/pdf");
        header("Cache-Control: max-age=0");
        header("Accept-Ranges: none");
        header("Content-Disposition: attachment; filename=\"google_com.pdf\"");

        $html = file_get_contents($filepath);

        $uu_id = $this->uuid->v4();
        $filename = "resource/upload/report/report_" . $uu_id . ".pdf";

        //echo $filename;
        $this->m_pdf->pdf->WriteHTML($html);
        $this->m_pdf->pdf->Output($filename, "F");

        $pdf = file_get_contents($filename);
        echo $pdf;
    }

    public function testPost()
    {
        $id = $this->input->get_post('id');
        $param1 = $this->input->get_post('mode');
        switch ($param1) {
            case '1':
                $content = $this->input->get_post('content');
                $pp = base64_encode($content);
                $msg = array('d' => $pp);
                print_r(json_encode($msg));
                break;
            default:
                break;
        }
    }

    public function testFunc()
    {

        $id = $this->input->get('id');
        $param1 = $this->input->get('mode');
        switch ($param1) {
            case '10':
                $tmp = $this->input->get('content');
//                $content = urldecode($tmp);
                $content = str_replace("%2B", "+", $tmp);
                $html = base64_decode($content);
                ini_set('memory_limit', '512M');

                $this->m_pdf->initialize("B4-C", "P");
                $this->m_pdf->setSize();
                $this->m_pdf->pdf->WriteHTML($html);
                $this->m_pdf->pdf->Output("report.pdf", "D");
                break;

            case '9':
                $pdftest_path = $_SERVER['DOCUMENT_ROOT'] . '/resource/upload/report/';
                $filepath = $pdftest_path . 'pdf1.html';
                // echo $filepath;
                $html = file_get_contents($filepath);
                return $html;
                break;
            case '8':
                $msg = array();
                $msg['tmp_name'] = $_FILES;
                var_dump($msg);
                break;
            case '7':
                $html = $this->get_report_html__for_duct_leakage_2018($id);
                $this->m_pdf->setSize();
                $this->m_pdf->pdf->WriteHTML($html);
                $this->m_pdf->pdf->Output("c:\\report.pdf", "D");
                break;
            case '6':
                $html = $this->get_report_html__for_duct_leakage_2018($id);
                echo $html;
                break;
            case '5':
                echo $this->get_report_html($id);
                break;
            case '1':
                echo $this->get_report_html__for_duct_leakage($id);
                break;
            case '2':
                echo $this->get_report_html__for_envelop_leakage_2018($id);
                break;
            case '3':
                // $user_id = '2';
                // $inspection_id = '14981';
                $user_id = $this->input->get('user_id');
                $inspection_id = $this->input->get('inspection_id');
                $report = $this->send_report($user_id, $inspection_id);
                echo $report;
                break;
            case '4':
                $user_id = '2';
                $inspection_id = '14977';
                $origin['user_id'] = $user_id;
                $origin['inspection_id'] = $inspection_id;
                $user_id = $this->utility_model->encode($user_id);
                $inspection_id = $this->utility_model->encode($inspection_id);

                $data = array();
                $encoded['user_id'] = $user_id;
                $encoded['inspection_id'] = $inspection_id;
                $data['origin'] = $origin;
                $data['encoded'] = $encoded;
                var_dump($data);
                //                $user_id= 2;
//                $inspection_id = 14977;
                break;
            default:
                # code...
                break;
        }
    }

    private function get_report_html__for_duct_leakage($inspection_id)
    {
        $sql = " select a.*, u.email, c2.name as result_name "
            . " , c3.name as result_duct_leakage_name, c4.name as result_envelop_leakage_name "
            . " from ins_code c2, ins_code c3, ins_code c4, ins_inspection a "
            . " left join ins_user u on a.user_id=u.id "
            . " where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code and c3.kind='rst_duct' and c3.code=a.result_duct_leakage and c4.kind='rst_envelop' and c4.code=a.result_envelop_leakage ";

        $inspection = $this->utility_model->get__by_sql($sql);

        $html = "";
        $html_head = "";
        $html_body = "";

        $html .= '<html>';
        $html_head .= '<head>';

        $settings = array();
        if ($inspection['type'] == 3) {
            $settings['s_width_25'] = 32;
            $settings['s_width_50'] = 36;
        } else {

            $settings['s_width_25'] = 32;
            $settings['s_width_50'] = 36;

//            $settings['s_width_25'] = 25;
//            $settings['s_width_50'] = 50;
        }
        $html_head .= '<style type="text/css">'
            . ' body { font-family: Arial, sans-serif; padding: 0; margin: 0; } '
            . '.title {    font-size: 23.92px; padding: 0 140px; line-height: 23px;  margin-bottom: 4px; }'
            . '.sub-title { font-size: 21.12px; margin-bottom: 0px;  }'
            . 'h2.sub-title { font-size: 19.24px; margin-top: 28px; font-weight: 600; margin-bottom: 3px; line-height: 16px; }'
            . '.text-center { text-align: center; }'
            . '.text-underline { padding-bottom: 3px; border-bottom: 1px solid #333; }'
            . '.font-light { font-weight: 100; }'
            . '.font-bold { font-weight: bold; }'
            . '.width-full { width: 100%; }'
            . '.performance-method {  font-size: 10.92px; padding: 5px 12px 1px;  border: 3px solid #000; }'
            . '.performance-method td { padding: 1px 2px; vertical-align: top; }'
            . '.row {  display: block; width: 100%;  }      '
            . '.test-result { font-size: 16.42px; border: 1px solid #000; border-collapse: collapse; }'
            . '.test-result td { border: 1px solid #000; vertical-align: top; padding: 16px 8px 8px; }'
            . '.test-result td span.text-underline { padding: 0 4px; }'
            . '.test-result td.result-line { text-align: center; width: 9%; }'
            . '.test-result td.result-system { text-align: left; width: 31%; padding-left: 16px; }'
            . '.test-result td.result-leakage { text-align: center; width: 60%; }'
            . '.width-25-percent { width: ' . $settings['s_width_25'] . '%; }'
            . '.width-40-percent { width: 42%; }'
            . '.width-50-percent { width: ' . $settings['s_width_50'] . '%; }'
            . '.width-60-percent { width: 58%; }'
            . '.inline-container>div { display: inline-block; }'
            . '.img-responsive { max-width: 100%; }'
            . '.footer-description { font-size: 12.92px; font-weight: 100; margin-top: 8px; margin-bottom: 32px; }'
            . 'td.footer-padding { padding: 4px 24px 32px; vertical-align: top; }'
            . 'td.footer-small-padding { padding: 8px 12px 8px 8px; vertical-align: top; }'
            . '.footer-value { font-size: 13.72px; font-weight: bold; padding: 10px 0; }'
            . '.footer .width-60-percent { border: 1px solid #000; }'
            . '.mybox tr  { line-height: 8px; }'
            . '.mybox td  { padding-left: 2px; font-size: 15px;}'
            . '</style>';
        $html_head .= '</head>';

        $html_body .= '<body>';

        if ($inspection) {
            $builder = "WCI";
            if ($inspection['type'] == 3) {
                $builder = "WCI";
                $settings['html_1'] = '';
                $settings['html_2'] = '';
                $settings['html_3'] = '<tr>'
                    . '<td style="vertical-align:bottom;"><span class="footer-value">RESNET ID: 9377172</span></td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td style="vertical-align:bottom;"><span class="footer-value">Florida Rater ID: 791</span></td>'
                    . '</tr>';
                $settings['qn_out'] = '(Qn,Out)';
                $settings['msg1'] = 'Total House Duct System Leakage';
                $settings['msg4'] = 'Duct tightness shall be verified by testing to Section 803 of the RESNET Standards by an energy rater certified in accordance with Section 553.99, Florida Statutes.';
                $settings['msg5'] = 'I certify the tested duct leakage to outside, Qn, is not greater than the proposed duct leakage Qn specified on Form R405-2014.';
                $settings['msg6'] = 'FORM R405-2014 Duct Leakage Test Report Performance Method';
            } else {
                $builder = "Pulte";
                $settings['html_1'] = '<div class="row">
    <table class="width-full" style="border-collapse: collapse;">
        <tbody><tr><td style="width:23%;">&nbsp;</td>
                <td  style="width:20%;   padding-top: 20px;">
                    <table class="width-full mybox" style="   border: 1px solid #000;"><tbody>
                    
                            <tr><td style="width: 55%;">
                            <table>
                                <tr><td>Required Duct Leakage from</td></tr>
                                <tr><td>FORMR405-2017</td></tr>
                            </table>
                            </td>
                            <td style="width: 45%; padding-top:10px">
                                <span class="text-underline">&nbsp; ' . $this->show_decimal($inspection['qn_out'], 3) . ' &nbsp;</span> 
                                    <span style="position: relative;    top: -15px;" class="text-value">(Q<span class="font-bold">n</span>,out)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width:23%;">&nbsp;</td>
            </tr>
        </tbody>
    </table></div>';

                $settings['html_2'] = '<table class="width-full" style="border-collapse: collapse;"><tbody><tr><td style="  "><table class="width-full" style="    border: 0px solid #000;    "><tbody>
                    <tr><td style="width: 100%;text-align: center;">*Tested Qn (Out) must be less than or equal to the required Qn (Out)</td></tr></tbody></table></td></tr></tbody></table>';
                $settings['html_3'] = '';
                $settings['qn_out'] = '(Qn,out)*';
                $settings['msg1'] = 'Tested Total House Duct System Leakage';
                $settings['msg4'] = 'Duct tightness shall be verified by
testing to ANSI/RESNET/ICC 380 by
either individuals as defined in Section
553.993(5) or (7), Florida Statutes, or
individuals licensed as set forth in
Section 489.105(3)(f),
(g), or (i), Florida Statutes.';
                $settings['msg5'] = 'I certify the tested duct leakage to outside, Qn,
is less than or equal to the proposed duct
leakage Qn specified on FORM R405-2017.';
                $settings['msg6'] = 'FORM R405-2017 Duct Leakage Test Report Performance Method';
            }


            $html_body .= '<h2 class="font-light" style="font-size: 13px;">' . $settings['msg6'] . '</h2>';
            $html_body .= '<h1 class="title text-center">FLORIDA ENERGY EFFICIENCY CODE FOR BUILDING CONSTRUCTION</h1>';
            $html_body .= '<h1 class="sub-title text-center font-light" style="margin-top: 3px; padding:0 140px;">Form R405 Duct Leakage Test Report Performance Method</h1>';


            $html_body .= '<div class="row" style="padding: 0 2px;">';
            $html_body .= '<table class="performance-method width-full" style="">';
            $html_body .= '<tr><td style="width: 55%;">Project Name: <span class="text-value">' . $inspection['community'] . '</span></td><td style="width: 45%;">Builder Name: <span class="text-value">' . $builder . '</td></tr>';
            $html_body .= '<tr><td>Street: <span class="text-value">' . $inspection['address'] . '</span></td><td>Permit Office: </td></tr>';
            $html_body .= '<tr><td>City, State, Zip: <span class="text-value">' . $inspection['city'] . '</span></td><td>Permit Number: <span class="text-value">' . $inspection['permit_number'] . '</span></td></tr>';
            $html_body .= '<tr><td>Design Location: <span class="text-value">' . $inspection['design_location'] . '</span></td><td>Jurisidiction: </td></tr>';
            $html_body .= '<tr><td>&nbsp;</td><td>Duct Test Time: Post Construction</td></tr>';
            $html_body .= '</table>';
            $html_body .= '</div>';

            $cfm25_system_1 = $this->cfm25($inspection_id, 1);
            $cfm25_system_2 = $this->cfm25($inspection_id, 2);
            $cfm25_system_3 = $this->cfm25($inspection_id, 3);
            $cfm25_system_4 = $this->cfm25($inspection_id, 4);
            $cfm25_system = $cfm25_system_1 + $cfm25_system_2 + $cfm25_system_3 + $cfm25_system_4;

            $html_body .= $settings['html_1'];
            $html_body .= '<h2 class="sub-title text-center">Duct Leakage Test Results</h2>';
            $html_body .= '<div class="row">';
            $html_body .= '<table class="width-full" style="border-collapse: collapse;"><tr><td class="width-25-percent">&nbsp;</td>';
            $html_body .= '<td class="width-50-percent">'
                . '<table class="test-result width-full">'
                . '<tr><td colspan="3" style="padding-top: 20px; font-size: 18px;">CFM25 Duct Leakage Test Values</td></tr>'
                . '<tr><td class="result-line">Line</td><td class="result-system">System</td><td class="result-leakage">Outside Duct Leakage</td></tr>'
                . '<tr><td class="result-line">1</td><td class="result-system">System 1</td><td class="result-leakage"><span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($cfm25_system_1, 1) . ' &nbsp;&nbsp;&nbsp;</span> cfm25(Out)</td></tr>'
                . '<tr><td class="result-line">2</td><td class="result-system">System 2</td><td class="result-leakage"><span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($cfm25_system_2, 1) . ' &nbsp;&nbsp;&nbsp;</span> cfm25(Out)</td></tr>'
                . '<tr><td class="result-line">3</td><td class="result-system">System 3</td><td class="result-leakage"><span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($cfm25_system_3, 1) . ' &nbsp;&nbsp;&nbsp;</span> cfm25(Out)</td></tr>'
                . '<tr><td class="result-line">4</td><td class="result-system">System 4</td><td class="result-leakage"><span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($cfm25_system_4, 1) . ' &nbsp;&nbsp;&nbsp;</span> cfm25(Out)</td></tr>'
                . '<tr>'
                . '<td class="result-line">5</td><td class="result-system font-bold">' . $settings['msg1'] . '</td>'
                . '<td class="result-leakage-total" style="padding-bottom: 96px; line-height: 28px;">'
                . 'Sum lines 1-4 <span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($cfm25_system, 1) . ' &nbsp;&nbsp;&nbsp;</span> <br>'
                . 'Divide by &nbsp;&nbsp;&nbsp; <span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($inspection['area'], 0) . ' &nbsp;&nbsp;&nbsp;</span> <br>'
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (Total Conditioned Floor Area) <br>'
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; = &nbsp; <span class="text-underline">&nbsp; ' . $this->show_decimal($inspection['qn_out'], 3) . ' &nbsp;</span> <span class="font-bold">' . $settings['qn_out'] . '</span>'
                . '</td>'
                . '</tr>'
                . '</table>'
                . '</td>';
            $html_body .= '<td class="width-25-percent">&nbsp;</td>';
            $html_body .= '</tr></table>';
            $html_body .= '</div>';

            $html_body .= $settings['html_2'];

            $html_body .= '<div class="row" style="height: 16px;"></div>';

            $html_body .= '<div class="row"><table class="width-full footer"><tr>';

            $html_body .= '<td class="width-40-percent footer-padding">';
            $html_body .= '<br><br>';
            $html_body .= '<h2 class="footer-description">' . $settings['msg5'] . '</h2>';

            $html_body .= '<table class="">'
                . '<tr>'
                . '<td style="vertical-align:bottom;"><span class="footer-value">SIGNATURE: </span></td>'
                . '<td style="padding-left: 8px; vertical-align:bottom; border-bottom: 1px solid #000;"><img class="img-responsive" src="' . $this->image_url_change(base_url()) . 'resource/upload/signature.png" alt="" style="height: 42px;"></td>'
                . '</tr>'
                . $settings['html_3']
                . '</table>';
            $html_body .= '<br>';
            $html_body .= '<h3 class="footer-value">PRINTED NAME: <span class="text-underline">&nbsp;&nbsp;&nbsp;' . 'Tom Karras ' . '&nbsp;&nbsp;&nbsp;</span></h3>';
            $html_body .= '<br>';
            $html_body .= '<h3 class="footer-value">DATE: <span class="text-underline">&nbsp;&nbsp;&nbsp;' . $inspection['end_date'] . '&nbsp;&nbsp;&nbsp;</span></h3>';
            $html_body .= '</td>';

            $html_body .= '<td class="width-60-percent footer-small-padding">';
            $html_body .= '<table class="width-full">'
                . '<tr>'
                . '<td style="vertical-align: middle;"><h2 class="footer-description" style="padding-right: 16px; padding-top: 8px;">' . $settings['msg4'] . '</h2></td>'
                . '<td style="padding-left:12px;"><img src="' . $this->image_url_change(base_url()) . 'resource/upload/wci.png" alt="" style="width: 164px; margin-top: 4px;"></td>'
                . '</tr>'
                . '</table>';
            $html_body .= '<h3 class="footer-value">BUILDING OFFICIAL: _______________</h3>';
            $html_body .= '<h3 class="footer-value">DATE: ____________________________</h3>';
            $html_body .= '</td>';

            $html_body .= '</tr></table></div>';

            $html_body .= '<div class="row" style="height: 16px;"></div>';
        }

        $html_body .= '</body>';

        $html .= $html_head . $html_body;
        $html .= '</html>';

        return $html;
    }

    // duct_2018
    private function get_report_html__for_duct_leakage_2018($inspection_id)
    {

        $sql = " select a.*, u.email, c2.name as result_name "
            . " , c3.name as result_duct_leakage_name, c4.name as result_envelop_leakage_name "
            . " from ins_code c2, ins_code c3, ins_code c4, ins_inspection a "
            . " left join ins_user u on a.user_id=u.id "
            . " where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code and c3.kind='rst_duct' and c3.code=a.result_duct_leakage and c4.kind='rst_envelop' and c4.code=a.result_envelop_leakage ";

        $inspection = $this->utility_model->get__by_sql($sql);
        if ($inspection) {


            $permit = $inspection['permit_number'];

            $ins_inspection_requested = $this->utility_model->get('ins_inspection_requested', array('id' => $inspection['requested_id']));

            $Jurisdiction = '';
            $ins_jurisdiction = $this->utility_model->get('ins_jurisdiction', array('id' => $ins_inspection_requested['jur_id']));
//            var_dump($ins_inspection_requested);
//            var_dump($inspection);
            if ($ins_jurisdiction) {
                $Jurisdiction = $ins_jurisdiction['name'];
            }

            $leakage_type = $ins_inspection_requested['leakage_type'];
            $leakage_type_name = 'Propsed Qn Entered';
            $qn = $inspection['qn'];
            if ($leakage_type == 1) {
                // Propsed Qn Entered
                $leakage_type_name = 'Default Leakage';
                $qn = '&nbsp;&nbsp;';
            } else if ($leakage_type == 2) {
                // Propsed Qn Entered
                $leakage_type_name = 'Proposed Leak Free';
                $qn = '&nbsp;&nbsp;';
            }

            $ins_user = $this->utility_model->get('ins_user', array('id' => $inspection['user_id']));
            $license = $ins_user['license'];
            $inspector_name = $ins_user['first_name'] . ' ' . $ins_user['last_name'];
            $builder = "Pulte";
            $type = $inspection['type'];
            if ($type >= 4) {
                $builder = "Pulte";
            }

//            $signature_url = $this->image_url_change(base_url()) . 'resource/upload/signature.png';
            $signature_url = $inspection['image_signature'];
//            $signature_url = $this->image_url_change(base_url()).'resource/upload/signature.png';
            $signature_url = str_replace("https://", "http://", $signature_url);

            $ach50 = $inspection['ach50'];


            $base_ach = $ins_inspection_requested['base_ach'];
            if ($ach50 < 3.0) {
                // ok
                // put check mark
            }
            $community = $inspection['community'];
            $lot = $inspection['lot'];
            $address = $inspection['address'];

            $city = $inspection['city'];


            $ins_community = $this->utility_model->get('ins_community', array('community_name' => $inspection['community']));

            $state = $ins_community['state'];
            $zip = $ins_community['zip'];

            $cfm25_system_1 = $this->cfm25($inspection_id, 1);
            $cfm25_system_2 = $this->cfm25($inspection_id, 2);
            $cfm25_system_3 = $this->cfm25($inspection_id, 3);
            $cfm25_system_4 = $this->cfm25($inspection_id, 4);
            $cfm25_system = $cfm25_system_1 + $cfm25_system_2 + $cfm25_system_3 + $cfm25_system_4;

            $c = floatval($inspection['flow']) / pow(floatval($inspection['house_pressure']), 0.65);
            $cfm50 = $c * 12.7154;
            $ela = $cfm50 * 0.055;
            $eqla = $cfm50 * 0.1032;
            $ach = floatval($inspection['ach50']) / 25.36;
            $sla = $ela * 0.00694 / floatval($inspection['area']);

            $result_duct_leakage = $this->utility_model->get('ins_code', array('kind' => 'rst_duct', 'code' => $inspection['result_duct_leakage']));

            $passbox1 = '';
            $passbox2 = '';
            if (strtolower($result_duct_leakage['name']) == 'pass') {
                $passbox1 = $this->image_url_change(base_url()) . 'resource/assets/images/checkboxcheck1.png';
                $passbox2 = $this->image_url_change(base_url()) . 'resource/assets/images/checkbox1.png';
            } else {
                $passbox2 = $this->image_url_change(base_url()) . 'resource/assets/images/checkboxcheck1.png';
                $passbox1 = $this->image_url_change(base_url()) . 'resource/assets/images/checkbox1.png';
            }

            $print_time = date('m/d/Y H:i:s', time());
//            $print_time = '';

            $company_name = 'E3 Building Sciences';
            $phone = '239-949-2409';
            $authority_name = 'FSEC - RESNET';

            $html = '<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    </head>
    <style>
        @page {
            margin-top: 40px;
            margin-left: 60px;
            margin-bottom: 20px;
            margin-right: 60px;
            sheet-size: 215.9mm 279.4mm;
        }

    </style>
        

    <body style="font-family: Helvetica,Arial,sans-serif;font-size: 14px;line-height: 1.42857143;color: #333;width: 1170px;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;background-color: #fff;box-sizing: border-box;">


        <!-- <div style="top:0px;width:100%;height:100%;border-style:outset;overflow:hidden"> -->
        <div style="top:0px;width:100%;height:100%;">



            <div style="text-align: center;width: 100%;">
                <h2 style="margin-bottom: 0px;margin-top: 45px;font-weight: 700;font-size: 30px;font-family:Arial,serif;font-size:16.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>Duct Leakage Test Report<b></h2>
            </div>


            <div style="width: 100%;text-align: center;">
                <p style="font-size: 21px;margin-top: 0px;margin-bottom: 30px;font-family:Arial,serif;font-size:14.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                    Residential Prescriptive, Performance or ERI Method Compliance<br>2017 Florida Building Code, Energy Conservation, 6th Edition
                </p>
            </div>


        

            <table style="width: 100%;margin-bottom: 0px;border: 1px solid black;max-width: 100%;background-color: transparent;border-spacing: 0;border-collapse: collapse;float: right;margin-left: 4%;border-bottom: none;">
                <tr>
                    <td style="width: 50%;padding: 8px;line-height: 1.42857143;vertical-align: top;box-sizing: border-box;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: non">Jurisdiction: ' . $Jurisdiction . '</td>
                    <td style="width: 50%;padding: 8px;line-height: 1.42857143;vertical-align: top;border-left: 1px solid black;box-sizing: border-box;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Permit #:' . $permit . '</td>
                <tr>
            </table>


           

            <table style="margin-bottom: 0px;border: 1px solid black;width: 100%;max-width: 100%;background-color: transparent;border-spacing: 0;border-collapse: collapse;">
                <tr>
                    <td colspan="3" style="background-color: #bdbdbd;padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">
                        <b>Job Information</b>
                    </td>
                </tr>


                <tr>
                    <td style="width: 25%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-right: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Builder:</span>' . $builder . '</td>
                    <td style="width: 50%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;border-right: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Community:</span> ' . $community . '</td>
                    <td style="width: 25%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Lot:</span> ' . $lot . '</td>
                </tr>


                <tr>
                    <td colspan="3" style="padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Address:</span> ' . $address . '</td>
                </tr>


                <tr>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-right: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>' . $city . ':</span> Naples</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;border-right: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>' . $state . ':</span> FL</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Zip:</span> ' . $zip . '</td>
                </tr>


                <tr style="vertical-align: middle;">
                    <td colspan="3" style="background-color: #bdbdbd;padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-right: 0px solid;">
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="width: 33%;float: left;position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;box-sizing: border-box;line-height: 1.42857143;">
                            <span style="font-size: 16px;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>Duct Leakage Test Results</b></span>
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="width: 4%;float: left;position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;box-sizing: border-box;">
                            <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radio.png">
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="width: 25%;float: left;position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;line-height: 1.42857143;">
                            <span style="font-size: 16px;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>Prescriptive Method</b></span>
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="width: 2%;float: left;position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;line-height: 1.42857143;">
                            <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radioselect.png">
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="width: 25%;float: left;position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;line-height: 1.42857143;">
                            <span style="font-size: 16px;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>Performance/ERI Method</b></span>
                        </span>
                    </td>
                </tr>

            </table>










            <div style="margin-bottom: 4px;background-color: #fff;border: 1px solid transparent;border-top-color: transparent;border-right-color: transparent;border-bottom-color: transparent;border-left-color: transparent;border-radius: 4px;-webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);box-shadow: 0 1px 1px rgba(0,0,0,.05);border-radius: 0px;border-color: black;border-top: none;">
                

                <div style="padding: 10px; min-height: 737px;">
                    
                    <div style="padding:0px;width: 34%;float: left;position: relative;min-height: 1px;"> 

                        <div style="padding: 9px;width: 100%;float: left;position: relative;min-height: 1px;border: 1px solid black;border-bottom: none;">
                            <div style="width: 45%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;margin-left: 5px">
                                System 1
                            </div>
                            <div style="width: 51%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                <div style="width: 65%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;">
                                    ' . $this->show_decimal($cfm25_system_1, 1) . '
                                    <div style="border-bottom: 1px solid black;"></div>
                                </div>
                                <div style="width: 10%;float: right;text-align: right;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                   cfm25
                                </div>
                            </div>
                        </div>
                        <div style="padding: 9px;width: 100%;float: left;position: relative;min-height: 1px;border: 1px solid black;border-bottom: none;">
                            <div style="width: 45%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;margin-left: 5px">
                                System 2
                            </div>
                            <div style="width: 51%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                <div style="width: 65%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;">
                                    ' . $this->show_decimal($cfm25_system_2, 1) . '
                                    <div style="border-bottom: 1px solid black;"></div>
                                </div>
                                <div style="width: 10%;float: right;text-align: right;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                   cfm25
                                </div>
                            </div>
                        </div>
                        <div style="padding: 9px;width: 100%;float: left;position: relative;min-height: 1px;border: 1px solid black;border-bottom: none;">
                            <div style="width: 45%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;margin-left: 5px">
                                System 3
                            </div>
                            <div style="width: 51%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                <div style="width: 65%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;">
                                    ' . $this->show_decimal($cfm25_system_3, 1) . '
                                    <div style="border-bottom: 1px solid black;"></div>
                                </div>
                                <div style="width: 10%;float: right;text-align: right;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                   cfm25
                                </div>
                            </div>
                        </div>
                        <div style="padding: 9px;width: 100%;float: left;position: relative;min-height: 1px;border: 1px solid black;border-bottom: none;">
                            <div style="width: 45%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;margin-left: 5px">
                                System 4
                            </div>
                            <div style="width: 51%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                <div style="width: 65%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;">
                                    ' . $this->show_decimal($cfm25_system_4, 1) . '
                                    <div style="border-bottom: 1px solid black;"></div>
                                </div>
                                <div style="width: 10%;float: right;text-align: right;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                   cfm25
                                </div>
                            </div>
                        </div>
                        <div style="padding: 9px;width: 100%;float: left;position: relative;min-height: 1px;border: 1px solid black; margin-bottom: 10px;">
                            <div style="width: 45%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;margin-left: 5px">
                                Total of all
                            </div>
                            <div style="width: 51%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                <div style="width: 65%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;">
                                    ' . $this->show_decimal($cfm25_system, 1) . '
                                    <div style="border-bottom: 1px solid black;"></div>
                                </div>
                                <div style="width: 10%;float: right;text-align: right;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                   cfm25
                                </div>
                            </div>
                        </div>







                        <div style="padding: 0px;width: 100%;float: left;position: relative;min-height: 1px;margin-top: 10px;">
                            <div style="width: 22%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                &nbsp;' . $this->show_decimal($cfm25_system, 1) . '
                                <div style="border-bottom: 1px solid black;"></div>
                                <small style="font-size: 10px;font-family:Arial,serif;font-size:8.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Total of all systems</small>
                            </div>
                            <div style="width: 8%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                ÷
                            </div>
                            <div style="width: 29%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">     
                                &nbsp;' . $this->show_decimal($inspection['area'], 0) . '                        
                                <div style="border-bottom: 1px solid black;"></div>
                                <small style="font-size: 10px;font-family:Arial,serif;font-size:8.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Total Conditioned Square Footage</small>
                            </div>
                            <div style="width: 8%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                =
                            </div>
                            <div style="width: 22%;float: left;text-align: centerfont-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;">
                                &nbsp;' . $this->show_decimal($inspection['qn_out'], 3) . '
                                <div style="border-bottom: 1px solid black;"></div>
                            </div>
                            <div style="width: 8%;float: left;text-align: center;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> 
                                Qn
                            </div>
                        </div>









                        <div style="margin-top: 10px;width: 100%;float: left;position: relative;min-height: 1px;">
                            <div style="width: 49%;float: left;position: relative;min-height: 1px;">
                                <img src="' . $passbox1 . '" style="width: 47px;margin-right: 5px;float: left;">
                                <h2 style="float: left;font-family:Arial,serif;font-size:14.1px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>PASS</b></h2>
                            </div>
                            <div style=";width: 49%;float: left;position: relative;min-height: 1px;;">
                                <img src="' . $passbox2 . '" style="width: 47px;margin-right: 5px;float: left;">
                                <h2 style="float: left;font-family:Arial,serif;font-size:14.1px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>FAIL</b></h2>
                            </div>
                        </div>



                    </div>


                    <div style="padding-right: 0px;width: 64%;float: left;position: relative;min-height: 1px;padding-left: 10px;"> 
                        

                        <div style="background: #bdbdbd;border: 1px solid black;padding: 8px;box-sizing: border-box;min-height: 150px;">
                            <div style="padding: 0px;width: 4%;float: left;position: relative;min-height: 1px;">
                                <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radio.png" style="margin-top: -1px;margin-left: -5px;">
                            </div>
                            <div style="padding: 0px;width: 96%;float: left;position: relative;min-height: 1px;">
                                <b style="font-size: 16px;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">Prescriptive Method</b> &nbsp;&nbsp;&nbsp;
                                <span style="font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"> cfm25 (Total) </span>
                                <br>
                                <span style="font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">To qualify as "substantially leak free" Qn Total must be less than or
                                equal to 0.04 if air handler unit is installed. If air handler unit is not
                                installed, Qn Total must be less than or equal to 0.03. This testing
                                method meets the requirements in accordance with Section R403.3.3.
                               
                                <span style="font-size: 10.0px">
                                    <i>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Is the air handler unit installed during testing? </i><img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/checkbox.png" style="width: 12px;margin-top: 2px;"> YES (


                                <span><sup style="font-size: 7px;">≤.04</sup> <sub style="font-size: 7px;margin-left: -25px;">Qn</sub></span>
                                ) <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/checkbox.png" style="width: 12px;margin-top: 2px;"> NO ( 
                                <span style="font-family:Arial,serif;font-size:7px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;">
                                    <span><sup style="font-size: 7px;">≤.03</sup>&nbsp;<sub style="font-size: 7px;">Qn</sub></span></span>
                                )
                                </span>
                                </span>
                            </div>
                        </div>


                        <div style="background: #bdbdbd;border: 1px solid black; margin-top: 15px;padding: 8px;box-sizing: border-box;min-height: 225px;">
                            <div style="padding: 0px;width: 4%;float: left;position: relative;min-height: 1px;padding-top: 0px">
                                <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radioselect.png" style="margin-top: -1px;margin-left: -5px;">
                            </div>
                            <div style="padding: 0px;width: 96%;float: left;position: relative;min-height: 1px;">
                                <b style="font-size: 16px;font-family:Arial,serif;font-size:13.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">Performance/ERI Method</b> &nbsp;&nbsp;&nbsp; 
                                <span style="font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">cfm25 (Out or Total) </span>
                                <br>
                                <span style="font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                To qualify using this method, Qn must not be greater than the
                                <br>proposed duct leakage Qn specified on Form R405-2017 or R406-2017.
                                </span>
                                <br>
                                <br>
                            </div>

                            <div style="width: 47%;float: left;position: relative;min-height: 1px;text-align: center;padding-right: 8px;padding-left: 8px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:italic;text-decoration: none">
                                Leakage Type selected on Form
                                R405-2017 (EnergyCalc) or R406-2017
                                <br>
                                <div style="border: 1px solid black; background: white; margin-top: 10px;text-align: left;padding: 8px;font-family:Arial,serif;font-size:9.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;width: 90%">' . $leakage_type_name . '</div>
                            </div>


                            <div style="width: 45%;float: left;position: relative;min-height: 1px;text-align: center;padding-left: 8px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:italic;text-decoration: none">
                                Qn specified on Form R405-2017
                                (EnergyCalc) or R406-2017
                                <br>
                                <div style="border: 1px solid black; background: white; margin-top: 10px;text-align: left;padding: 8px;font-family:Arial,serif;font-size:9.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">' . $qn . '</div>
                            </div>
                        </div>

                    </div> 


                    <p style="font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Duct tightness shall be verified by testing in accordance with ANSI/RESNET/ICC380 by either individuals as defined in Section 553.993(5) or (7), Florida Statutes, or individuals licensed as set forth in Section 489.105(3)(f), (g) or (i), Florida Statutes.</p>



                    <div style="background: #9e9e9e;border: 1px solid black;padding: 10px;width: 100%;float: left;position: relative;min-height: 1px;">
                        <span style="font-size: 15px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Testing Company </span>
                    </div>



                    <div style="padding: 12px 0px;width: 100%;float: left;position: relative;min-height: 1px;">
                        <div style="width: 66.66666667%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Company Name:&nbsp;&nbsp;&nbsp;&nbsp;' . $company_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 70%;margin-left: 80px;"></div>
                        </div>
                        <div style="width: 33.33333333%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Phone:&nbsp;&nbsp;&nbsp;&nbsp;' . $phone . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 70%;margin-left: 35px;"></div>
                        </div>

                        <div style="width: 100%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            <p>I hereby verify that the above duct leakage testing results are in accordance with the Florida Building Code requirements with the selected compliance path as stated above, either the Prescriptive Method or Performance Method.</p>
                        </div>

                        <div style="padding: 0px;width: 66.66666667%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Signature of Tester:&nbsp;&nbsp;&nbsp;&nbsp;
                            <!-- <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 67%;margin-left: 93px;"> -->
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 67%;margin-left: 100px;text-align: center;">
                                <img src="' . $signature_url . '" style="height: 20px;margin-top: -20px;display: block;margin-left: auto;margin-right: auto;">
                            </div>
                        </div>
                        <div style="padding: 0px;width: 33.33333333%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Date of Test:&nbsp;&nbsp;&nbsp;&nbsp;' . $inspection['end_date'] . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 60%;margin-left: 61px;"></div>
                        </div>

                        <div style="padding: 0px;margin-top: 12px;width: 100%;float: left;position: relative;min-height: 1px;">
                        </div>

                        <div style="padding: 0px;width: 100%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Printed Name of Tester:&nbsp;&nbsp;&nbsp;&nbsp;' . $inspector_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 40%;margin-left: 111px;"></div>
                        </div>

                        <div style="padding: 0px;margin-top: 12px;width: 100%;float: left;position: relative;min-height: 1px;">
                        </div>

                        <div style="padding: 0px;width: 66.66666667%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            License/Certification #:&nbsp;&nbsp;&nbsp;&nbsp;' . $license . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 64%;margin-left: 108px;"></div>               
                        </div>
                        <div style="padding: 0px;width: 33.33333333%;float: left;position: relative;min-height: 1px; margin-bottom: 5px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                            Issuing Authority:&nbsp;&nbsp;&nbsp;&nbsp;' . $authority_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 50%;margin-left: 84px;"></div>
                        </div>

                    </div>
                </div>

            </div>
              





                <div style="margin-top: 10px;position: relative;min-height: 1px;">
                    <div style="width: 23.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: left;float: left;">
                        ' . $print_time . '
                    </div>
                    <div style="width: 59.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;float: left;">
                        EnergyGauge® USA 6.0.02 - FlaRes2017 FBC 6th Edition (2017) Compliant Software
                    </div>
                    <div style="width: 17.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: right;float: left;">
                        Page 1 of 1
                    </div>
                </div>

            
        
        </div>
    </body>
</html>
';
        }

        return $html;
    }

    // envelop_2018
    private function get_report_html__for_envelop_leakage_2018($inspection_id)
    {
        $sql = " select a.*, u.email, c2.name as result_name "
            . " , c3.name as result_duct_leakage_name, c4.name as result_envelop_leakage_name "
            . " from ins_code c2, ins_code c3, ins_code c4, ins_inspection a "
            . " left join ins_user u on a.user_id=u.id "
            . " where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code and c3.kind='rst_duct' and c3.code=a.result_duct_leakage and c4.kind='rst_envelop' and c4.code=a.result_envelop_leakage ";

        $inspection = $this->utility_model->get__by_sql($sql);

        $html = '';
        if ($inspection) {
            $permit = $inspection['permit_number'];

            $ins_inspection_requested = $this->utility_model->get('ins_inspection_requested', array('id' => $inspection['requested_id']));
            $Jurisdiction = '';
            $ins_jurisdiction = $this->utility_model->get('ins_jurisdiction', array('id' => $ins_inspection_requested['jur_id']));
//            var_dump($ins_inspection_requested);
//            var_dump($inspection);
            if ($ins_jurisdiction) {
                $Jurisdiction = $ins_jurisdiction['name'];
            }
            $leakage_type = $ins_inspection_requested['leakage_type'];
            $leakage_type_name = 'Propsed Qn Entered';
            $qn = $inspection['qn'];
            if ($leakage_type == 1) {
                // Propsed Qn Entered
                $leakage_type_name = 'Default Leakage';
                $qn = '&nbsp;&nbsp;';
            } else if ($leakage_type == 2) {
                // Propsed Qn Entered
                $leakage_type_name = 'Proposed Leak Free';
                $qn = '&nbsp;&nbsp;';
            }

            $ins_user = $this->utility_model->get('ins_user', array('id' => $inspection['user_id']));
            $license = $ins_user['license'];
            $inspector_name = $ins_user['first_name'] . ' ' . $ins_user['last_name'];
            $builder = "Pulte";
            $type = $inspection['type'];
            if ($type >= 5) {
                $builder = "Pulte";
            }

//            $signature_url = $this->image_url_change(base_url()) . 'resource/upload/signature.png';
            $signature_url = $inspection['image_signature'];
//            $signature_url = $this->image_url_change(base_url()) . 'resource/upload/signature.png';
//            $signature_url = 'http://inspdev.e3bldg.com/resource/upload/pulte_duct/signature/20180810041246_56f67d3c-009f-48df-9006-ec450e71fada.jpg';
            $signature_url = str_replace("https://", "http://", $signature_url);

            $ach50 = $inspection['ach50'];
            $volume = $inspection['volume'];

            $base_ach = $ins_inspection_requested['base_ach'];
            $check_url1 = $this->image_url_change(base_url()) . 'resource/assets/images/checkbox.png';
            if ($ach50 < 3.0) {
                // ok
                // put check mark
                $check_url1 = $this->image_url_change(base_url()) . 'resource/assets/images/checkbox_on.png';
            }
            $community = $inspection['community'];
            $lot = $inspection['lot'];
            $address = $inspection['address'];

            $city = $inspection['city'];


            $ins_community = $this->utility_model->get('ins_community', array('community_name' => $inspection['community']));

            $state = $ins_community['state'];
            $zip = $ins_community['zip'];

            $cfm25_system_1 = $this->cfm25($inspection_id, 1);
            $cfm25_system_2 = $this->cfm25($inspection_id, 2);
            $cfm25_system_3 = $this->cfm25($inspection_id, 3);
            $cfm25_system_4 = $this->cfm25($inspection_id, 4);

            $c = floatval($inspection['flow']) / pow(floatval($inspection['house_pressure']), 0.65);
            $cfm50 = $c * 12.7154;
            $ela = $cfm50 * 0.055;
            $eqla = $cfm50 * 0.1032;
            $ach = floatval($inspection['ach50']) / 25.36;
            $sla = $ela * 0.00694 / floatval($inspection['area']);

            $result_envelop_leakage = $this->utility_model->get('ins_code', array('kind' => 'rst_envelop', 'code' => $inspection['result_envelop_leakage']));

            $passbox1 = '';
            $passbox2 = '';
            if (strtolower($result_envelop_leakage['name']) == 'pass') {

            } else {

            }

            $print_time = date('m/d/Y H:i:s', time());

            $envelop_result_name = "";
            $envelop_result_box = "";
            if ($inspection['result_envelop_leakage'] == 1 || $inspection['result_envelop_leakage'] == 2 || $inspection['result_envelop_leakage'] == 5) {
                $envelop_result_name = "PASS";

                $passbox1 = $this->image_url_change(base_url()) . 'resource/assets/images/checkboxcheck1.png';
                $envelop_result_box = '<img src="' . $passbox1 . '" style="width: 33px;margin-right: 5px;float: left;">';
            }
            if ($inspection['result_envelop_leakage'] == 3) {
                $envelop_result_name = "FAIL";

                $passbox1 = '';
                $envelop_result_box = "";
            }

            $company_name = 'E3 Building Sciences';
            $phone = '239-949-2409';
            $authority_name = 'FSEC - RESNET';

            $html = '<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    </head>
    <style>
        @page {
            margin-top: 40px;
            margin-left: 60px;
            margin-bottom: 20px;
            margin-right: 60px;
            sheet-size: 215.9mm 279.4mm;
        }

    </style>
        

    <body style="font-family: Helvetica,Arial,sans-serif;font-size: 14px;line-height: 1.42857143;color: #333;width: 1170px;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;background-color: #fff;box-sizing: border-box;">


        <!-- <div style="top:0px;width:100%;height:100%;border-style:outset;overflow:hidden"> -->
        <div style="top:0px;width:100%;height:100%;">



            <div style="text-align: center;width: 100%;">
                <h2 style="margin-bottom: 0px;margin-top: -10px;font-weight: 700;font-size: 30px;font-family:Arial,serif;font-size:16.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><b>Envelope Leakage Test Report (Blower Door Test)<b></h2>
            </div>


            <div style="width: 100%;text-align: center;">
                <p style="font-size: 21px;margin-top: 0px;margin-bottom: 0px;font-family:Arial,serif;font-size:14.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                    Residential Prescriptive, Performance or ERI Method Compliance<br> 2017 Florida Building Code, Energy Conservation, 6th Edition
                </p>
            </div>


        

            <table style="width: 100%;margin-bottom: 0px;border: 1px solid black;max-width: 100%;background-color: transparent;border-spacing: 0;border-collapse: collapse;float: right;margin-left: 4%;border-bottom: none;">
                <tr>
                    <td style="width: 50%;padding: 8px;line-height: 1.42857143;vertical-align: top;box-sizing: border-box;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: non">Jurisdiction:' . $Jurisdiction . '</td>
                    <td style="width: 50%;padding: 8px;line-height: 1.42857143;vertical-align: top;border-left: 1px solid black;box-sizing: border-box;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Permit #:' . $permit . '</td>
                <tr>
            </table>


           

            <table style="margin-bottom: 0px;border: 1px solid black;width: 100%;max-width: 100%;background-color: transparent;border-spacing: 0;border-collapse: collapse;">
                <tr>
                    <td colspan="3" style="background-color: #bdbdbd;padding: 5px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">
                        <b>Job Information</b>
                    </td>
                </tr>


                <tr>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-right: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Builder:</span> ' . $builder . '</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;border-right: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Community:</span> ' . $community . '</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Lot:</span> ' . $lot . '</td>
                </tr>


                <tr>
                    <td colspan="3" style="padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Address:</span> ' . $address . '</td>
                </tr>


                <tr>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-right: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>City:</span> ' . $city . '</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;border-right: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>State:</span> ' . $state . '</td>
                    <td style="width: 33.3%; padding: 8px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;border-left: 0px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><span>Zip:</span> ' . $zip . '</td>
                </tr>



                <tr>
                    <td colspan="3" style="background-color: #bdbdbd;padding: 5px;line-height: 1.42857143;vertical-align: top;border: 1px solid black;">
                        <span style="font-size: 16px;font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none"><b>Air Leakage Test Results</b></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="font-size: 14px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">Passing results must meet either the Performance, Prescriptive, or ERI Method</span>
                    </td>
                </tr>

            </table>




            <div style="margin-bottom: 0px;background-color: #fff;border: 1px solid transparent;border-top-color: transparent;border-right-color: transparent;border-bottom-color: transparent;border-left-color: transparent;border-radius: 4px;-webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);box-shadow: 0 1px 1px rgba(0,0,0,.05);border-radius: 0px;border-color: black;border-top: none;">
                

                <div style="padding: 8px; min-height: 737px;">


                    <div style="background: #bdbdbd;border: 1px solid black;padding: 5px;box-sizing: border-box;min-height: 150px;padding-bottom: 2px;">
                        <div style="padding: 0px;width: 3%;float: left;position: relative;min-height: 1px;">
                            <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radio.png" style="margin-top: -4px;margin-left: -5px;">
                        </div>
                        <div style="padding: 0px;width: 97%;float: left;position: relative;min-height: 1px;">
                            <b style="font-size: 16px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">PRESCRIPTIVE METHOD - </b>
                            <span style="font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">The building or dwelling unit shall be tested and verified as having an air leakage rate of not exceeding 7 air changes per hour at a pressure of 0.2 inch w.g. (50 Pascals) in Climate Zones 1 and 2.</span>
                        </div>
                    </div>


                    <div style="background: #bdbdbd;border: 1px solid black;padding: 5px;box-sizing: border-box;min-height: 150px;margin-top: 10px;padding-bottom: 2px;">
                        <div style="padding: 0px;width: 3%;float: left;position: relative;min-height: 1px;">
                            <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radioselect.png" style="margin-top: -4px;margin-left: -5px;">
                        </div>
                        <div style="padding: 0px;width: 97%;float: left;position: relative;min-height: 1px;">
                            <b style="font-size: 16px;font-size: 16px;font-family:Arial,serif;font-size:11.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">PERFORMANCE or ERI METHOD - </b>
                            <span style="font-size: 14px;float: left;font-family:Arial,serif;font-size:9.7px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                The building or dwelling unit shall be tested and verified as having an air leakage rate of not exceeding the selected ACH(50) value, as shown on Form R405-2017 (Performance) or R406-2017 (ERI), section labeled as infiltration, sub-section ACH50.
                            </span>
                            <div style="font-size: 14px;float: left;">
                                <div style="float: left;font-size: 16px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none">
                                    <i>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ACH(50) specified on Form R405-2017-Energy Calc (Performance) or R406-2017 (ERI):</i>
                                </div> 
                                <div style="border: 1px solid black; background: white;text-align: left;padding: 2px;width: 60px;margin-top: -17x;margin-right: 90px;float: right;font-size:10.0px;">' . $base_ach . '</div>
                            </div>
                        </div>
                    </div>


                    <div style="border: 1px solid black;padding: 5px;box-sizing: border-box;min-height: 150px;margin-top: 10px;">
                        <div style="width: 50%;float: left;position: relative;min-height: 1px;padding-right: 25px;padding-left: 15px;">
                            <div style="width: 21%;float: left;text-align: center;font-size:10.0px;"> 
                                &nbsp;' . $this->show_decimal($cfm50, 0) . '
                                <div style="border-bottom: 1px solid black;"></div>
                                <span style="font-size: 16px;font-size:10.0px;"> CFM(50) </span>
                            </div>

                            <div style="width: 6%;float: left;text-align: center;font-size:10.0px;"> 
                                &times;
                            </div>

                            <div style="width: 6%;float: left;text-align: center;font-size:10.0px;">     
                                &nbsp;60                       
                            </div>

                            <div style="width: 6%;float: left;text-align: center;font-size:10.0px;"> 
                                ÷
                            </div>

                            <div style="width: 24%;float: left;text-align: center;font-size:10.0px;">     
                                &nbsp;' . $volume . '                       
                                <div style="border-bottom: 1px solid black;"></div>
                                <span style="font-size: 16px;font-size:10.0px;">Building Volume</span>
                            </div>

                            <div style="width: 6%;float: left;text-align: center;font-size:10.0px;"> 
                                =
                            </div>

                            <div style="width: 21%;float: left;text-align: center;font-size:10.0px;">
                                &nbsp;' . $ach50 . '
                                <div style="border-bottom: 1px solid black;"></div>
                                <span style="font-size: 16px;font-size:10.0px;">ACH(50)</span>
                            </div>

                            <div style="width: 6%;float: left;text-align: center;font-size:10.0px;"> 
                                Qn
                            </div>

                            

                            <div style="width: 100%;float: left;position: relative;min-height: 1px;margin-left: 60px;">
                                ' . $envelop_result_box . '
                                <!-- <img src="' . $passbox1 . '" style="width: 33px;margin-right: 5px;float: left;"> -->
                                <h1 style="float: left;font-family:Arial,serif;font-size:14.1px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none"><b>' . $envelop_result_name . '</b></h1>
                            </div>


                            <div style="width: 100%;float: left;position: relative;min-height: 1px;">
                                <img src="' . $check_url1 . '" style="width: 20px;margin-right: 5px;float: left;">
                                <span style="float: left;margin-top: 0px;font-size: 16px;font-size:11.0px;color:rgb(0,0,0)">When ACH(50) is less than 3, Mechanical Ventilation installation must be verified by building department.</span>
                            </div>

                        </div>


                        <div style="width: 40%;float: left;position: relative;min-height: 1px;padding-left: 15px;">
                            <div style="float: left;margin-top: 0px;font-size: 18px; border-bottom: 1px solid black;font-size:12.0px;color:rgb(0,0,0)">
                                Method for calculating building volume:
                            </div>
                            <div style="float: left;font-size: 18px;padding-top: 10px;font-size:12.0px;">
                                <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radio.png" style="margin-left: -5px;">&nbsp;&nbsp;Retrieved from architectural plans
                            </div>
                            <div style="float: left;font-size: 18px;padding-top: 10px;font-size:12.0px;color:rgb(0,0,0)">
                                <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radioselect.png" style="margin-left: -5px;">&nbsp;&nbsp;Code software calculated
                            </div>
                            <div style="float: left;font-size: 18px;padding-top: 10px;font-size:12.0px;color:rgb(0,0,0)">
                                <img src="' . $this->image_url_change(base_url()) . 'resource/assets/images/radio.png" style="margin-left: -5px;">&nbsp;&nbsp;Field measured and calculated
                            </div>
                        </div>
                    </div>


                    <div style="border: 1px solid black;padding: 5px;box-sizing: border-box;min-height: 150px;margin-top: 10px;">
                        <div style="padding: 0px;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:9.7px;color:rgb(0,0,0);font-style:normal;text-decoration: none">
                            <b style="font-size: 14px;font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none">R402.4.1.2 Testing. </b>
                            Testing shall be conducted in accordance with ANSI/RESNET/ICC 380 and reported at a pressure of 0.2 inch w.g. (50 Pascals). Testing shall be conducted by either individuals as defined in Section 553.993(5) or (7), Florida Statues.or individuals licensed as set forth in Section 489.105(3)(f), (g), or (i) or an approved third party. A written report of the results of the test shall be signed by the party conducting the test and provided to the<i>code official</i>. Testing shall be performed at any time after creation of all penetrations of the <i>building thermal envelope</i>.
                            <br>
                            <br>
                            <div style="float: left;">
                                During testing: 
                            </div>
                            1. Exterior windows and doors, fireplace and stove doors shall be closed, but not sealed, beyond the intended weatherstripping or other infiltration control measures.
                            <br>
                            2. Dampers including exhaust, intake, makeup air, back draft and flue dampers shall be closed, but not sealed beyond intended infiltration control measures.
                            <br>
                            3. Interior doors, if installed at the time of the test, shall be open.
                            <br>
                            4. Exterior doors for continuous ventilation systems and heat recovery ventilators shall be closed and sealed.
                            <br>
                            5. Heating and cooling systems, if installed at the time of the test, shall be turned off.
                            <br>
                            6. Supply and return registers, if installed at the time of the test, shall be fully open.
                        </div>
                    </div>



                    <div style="background: #9e9e9e;border: 1px solid black;padding: 5px;width: 100%;float: left;position: relative;min-height: 1px;margin-top: 10px;">
                        <span style="font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-style:normal;text-decoration: none">Testing Company </span>
                    </div>



                    <div style="padding: 5px 0px;width: 100%;float: left;position: relative;min-height: 1px;">
                        <div style="width: 66.66666667%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-style:normal;text-decoration: none">
                            Company Name:&nbsp;&nbsp;&nbsp;&nbsp;' . $company_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 70%;margin-left: 100px;"></div>
                        </div>
                        <div style="width: 33.33333333%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:12.0px;color:rgb(0,0,0);font-style:normal;text-decoration: none">
                            Phone:&nbsp;&nbsp;&nbsp;&nbsp;' . $phone . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 70%;margin-left: 47px;"></div>
                        </div>

                        <div style="margin-top: 5px;width: 100%;float: left;position: relative;min-height: 1px;font-family:Arial,serif;font-size:10.0px;color:rgb(0,0,0);font-style:normal;text-decoration: none">
                            <span>I hereby verify that the above Air Leakage results are in accordance with the 2017 6th Edition Florida Building Code Energy Conservation requirements according to the compliance method selected above.</span>
                        </div>

                        <div style="width: 66.66666667%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                            Signature of Tester:&nbsp;&nbsp;&nbsp;&nbsp;
                            <!-- <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 67%;margin-left: 93px;"> -->
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 67%;margin-left: 100px;text-align: center;">
                                <img src="' . $signature_url . '" style="height: 20px;margin-top: -20px;display: block;margin-left: auto;margin-right: auto;">
                            </div>
                        </div>
                        <div style="width: 33.33333333%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                            Date of Test:&nbsp;&nbsp;&nbsp;&nbsp;' . $inspection['end_date'] . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 60%;margin-left: 68px;"></div>
                        </div>

                        <div style="margin-top: 8px;width: 100%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                        </div>

                        <div style="width: 100%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                            Printed Name of Tester:&nbsp;&nbsp;&nbsp;&nbsp;' . $inspector_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 40%;margin-left: 125px;"></div>
                        </div>

                         <div style="margin-top: 8px;width: 100%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                        </div>

                        <div style="width: 66.66666667%;float: left;position: relative;min-height: 1px;font-size:11.0px;color:rgb(0,0,0);">
                            License/Certification #:&nbsp;&nbsp;&nbsp;&nbsp;' . $license . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 64%;margin-left: 123px;"></div>
                        </div>
                        <div style="width: 33.33333333%;float: left;position: relative;min-height: 1px; font-size:11.0px;color:rgb(0,0,0);margin-bottom: 8px;">
                            Issuing Authority:&nbsp;&nbsp;&nbsp;&nbsp;' . $authority_name . '
                            <div style="border-bottom: 1px solid black; background: white; text-align: left;width: 50%;margin-left: 97px;"></div>
                        </div>

                    </div>
                </div>
            </div>


            <div style="margin-top: 10px;position: relative;min-height: 1px;">
                <div style="width: 23.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: left;float: left;">
                    6/21/2018 12:12:54 PM
                </div>
                <div style="width: 59.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: center;float: left;">
                    EnergyGauge® USA 6.0.02 - FlaRes2017 FBC 6th Edition (2017) Compliant Software
                </div>
                <div style="width: 17.3%;font-family:arial,serif;font-size:10.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none;text-align: right;float: left;">
                    Page 1 of 1
                </div>
            </div>

        
        </div>
    </body>
</html>';
        }
        return $html;
    }

    private function get_report_html__for_envelop_leakage($inspection_id)
    {
        $sql = " select a.*, u.email, c2.name as result_name "
            . " , c3.name as result_duct_leakage_name, c4.name as result_envelop_leakage_name "
            . " from ins_code c2, ins_code c3, ins_code c4, ins_inspection a "
            . " left join ins_user u on a.user_id=u.id "
            . " where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code and c3.kind='rst_duct' and c3.code=a.result_duct_leakage and c4.kind='rst_envelop' and c4.code=a.result_envelop_leakage ";

        $inspection = $this->utility_model->get__by_sql($sql);

        $html = "";
        $html_head = "";
        $html_body = "";

        if ($inspection['type'] == 3) {
            $settings['s_width_30'] = 40;
            $settings['s_width_70'] = 60;
        } else {
            $settings['s_width_30'] = 50;
            $settings['s_width_70'] = 50;
        }

        $html .= '<html>';
        $html_head .= '<head>';
        $html_head .= '<style type="text/css">'
            . ' body { font-family: Arial, sans-serif; padding: 0; margin: 0; } '
            . '.title {    font-size: 23.92px; padding: 16px 140px 0; line-height: 23px; margin-bottom: 4px; }'
            . '.sub-title { font-size: 21.12px; margin-bottom: 4px; line-height: 22px; }'
            . 'h2.sub-title { font-size: 19.24px; margin-top: 28px; font-weight: 600; margin-bottom: 3px; line-height: 16px; }'
            . '.text-center { text-align: center; }'
            . '.text-underline { padding-bottom: 3px; border-bottom: 1px solid #333; }'
            . '.font-light { font-weight: 100; }'
            . '.font-bold { font-weight: bold; }'
            . '.width-full { width: 100%; }'
            . '.performance-method {  font-size: 10.92px; padding: 5px 12px 3px;  border: 3px solid #000; }'
            . '.performance-method td { padding: 1px 2px; vertical-align: top; }'
            . '.row {  display: block; width: 100%; margin-left: -10px; margin-right: -10px;  }      '
            . '.test-result { font-size: 13.92px; border: 1px solid #000; border-collapse: collapse; }'
            . '.test-result td { border: 1px solid #000; vertical-align: top; padding: 16px 8px 8px; }'
            . '.test-result td span.text-underline { padding: 0 4px; }'
            . '.test-result td.result-line { text-align: center; width: 9%; }'
            . '.test-result td.result-system { text-align: left; width: 31%; padding-left: 16px; }'
            . '.test-result td.result-leakage { text-align: center; width: 60%; }'
            . '.width-25-percent { width: 32%; }'
            . '.width-40-percent { width: 42%; }'
            . '.width-50-percent { width: 36%; }'
            . '.width-60-percent { width: 58%; }'
            . '.width-30-percent { width: ' . $settings['s_width_30'] . '%; }'
            . '.width-70-percent { width: ' . $settings['s_width_70'] . '%; }'
            . '.inline-container>div { display: inline-block; }'
            . '.img-responsive { max-width: 100%; }'
            . '.footer-description { font-size: 12.92px; font-weight: 100; margin-top: 8px; margin-bottom: 32px; }'
            . 'td.footer-padding { padding: 4px 24px 32px; vertical-align: top; }'
            . 'td.footer-small-padding { padding: 8px 12px 8px 8px; vertical-align: top; }'
            . '.footer-value { font-size: 13.72px; font-weight: bold; padding: 10px 0; }'
            . '.footer .width-60-percent { border: 1px solid #000; }'
            . '.border-bottom { border-bottom: 1px solid #000; } '
            . '.part-title { font-size: 18.56px; margin-bottom: 4px; font-weight: 100; line-height: 22px; }'
            . '.test-result td.house-pressure { text-align: center; width: 200px; }'
            . '.test-result td.flow { text-align: center; width: 150px; }'
            . '.leakage-characteristics td { padding: 4px 4px; } '
            . 'li { padding: 3px 0; } '
            . '</style>';
        $html_head .= '</head>';

        $html_body .= '<body>';

        if ($inspection) {
            $builder = "WCI";
            $settings = array();
            if ($inspection['type'] == 3) {
                $builder = "WCI";
                $html_1 = '';
                $html_1 .= '<table class="leakage-characteristics" style="padding-left: 20px;">';
                $html_1 .= '<tr><td>&nbsp;</td><td style="width:100px;">&nbsp;</td></tr>';
                $html_1 .= '<tr><td>CFM(50): &nbsp;</td><td class="border-bottom text-center"> ' . $this->show_decimal($cfm50, 0) . '  </td></tr>';
                $html_1 .= '<tr><td>ELA: &nbsp;</td><td class="border-bottom text-center"> ' . $this->show_decimal($ela, 4) . '  </td></tr>';
                $html_1 .= '<tr><td>EqLA: &nbsp;</td><td class="border-bottom text-center"> ' . $this->show_decimal($eqla, 4) . '  </td></tr>';
                $html_1 .= '<tr><td>ACH: &nbsp;</td><td class="border-bottom text-center"> ' . $this->show_decimal($ach, 4) . '  </td></tr>';
                $html_1 .= '<tr><td>ACH(50): &nbsp;</td><td class="border-bottom text-center">' . $this->show_decimal($inspection['ach50'], 2) . '</td></tr>';
                $html_1 .= '<tr><td>SLA: &nbsp;</td><td class="border-bottom text-center"> ' . $this->show_decimal($sla, 4) . '  </td></tr>';
                $html_1 .= '</table>';
                $settings['html_1'] = $html_1;
                $settings['html_2'] = '';
                $settings['html_3'] = '<tr>'
                    . '<td style="vertical-align:bottom;"><span class="footer-value">RESNET ID: 9377172</span></td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td style="vertical-align:bottom;"><span class="footer-value">Florida Rater ID: 791</span></td>'
                    . '</tr>';
                $settings['padding1'] = '';

                $settings['html_4'] = '<div class="row" style="height: 24px;"></div>';

                $settings['msg2'] = 'Where required by the code official, testing shall be conducted by an approved third party. A written report of the results of the test shall be signed by the third party conducting the test and provided to the code official.';
                $settings['msg3'] = 'The building or dwelling unit shall be tested and verified as having an air leakage rate of not exceeding 5 air changes per hour in Climate Zones 1 and 2, 3 air changes per hour in Climate Zones 3 through 8. Testing shall be conducted with a blower door at a pressure of 0.2 inches w.g. (50 Pascals). Where required by the code official, testing shall be conducted by an approved third party. A written report of the results of the test shall be signed by the party conducting the test and provided to the code official. Testing shall be performed at any time after creation of all penetrations of the building thermal envelope.';
                $settings['msg7'] = '';
                $settings['msg8'] = 'Prescriptive and Performance Method';
            } else {
                $builder = "Pulte";


                $html_1 = '<table class="width-full" style="border: 0px solid #000;padding-bottom: 10px;padding-top: 0px;">
    <tbody><tr>
            <td style="width: 60%;">
                Required                ACH(50)                from</td>
            <td style="width: 40%;"></td>
        </tr>
        <tr>
            <td>FORM R405-2017 :</td>
            <td><span class="text-underline" style=" ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $this->show_decimal($inspection['ach50'], 2) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="row" style="height: 16px;"> </div>
            </td>
        </tr>
        <tr >
            <td style="    padding-top: 30px;    padding-bottom:5px;">Tested ACH(50) * :</td>
            <td><span class="text-underline" style=" ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $this->show_decimal($inspection['ach50'], 2) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
        </tr>
        <tr>
            <td colspan="2"><div class="row" style="height: 16px;">      </div>
            </td>
        </tr>

        <tr>
            <td colspan="2">*Tested leakage must be less than or equal to the required ACH(50) shown on Form R405-2017 forthis building. If the tested ACH(50) is less than 3 the building must have a mechanical ventilation system</td>
        </tr>
    </tbody>
</table>';
                $settings['html_1'] = $html_1;

                $settings['html_2'] = '<table class="width-full" style="border-collapse: collapse;"><tbody><tr><td class="width-25-percent">&nbsp;</td><td class="width-50-percent" style="    padding-top: 20px;"><table class="width-full" style="    border: 0px solid #000;    padding-bottom: 10px;    padding-top: 10px;"><tbody>
                    <tr><td style="width: 100%;text-align: center;">*Tested Qn (Out) must be less than or equal to the required Qn (Out)</td></tr></tbody></table></td><td class="width-25-percent">&nbsp;</td></tr></tbody></table>';
                $settings['html_3'] = '';
                $settings['padding1'] = 'padding-left:30px; ';
                $settings['html_4'] = '';

                $settings['msg2'] = 'Testing shall be conducted by either
individuals as defined in Section
553.993(5) or (7), Florida Statutes or
individuals licensed as set forth in
Section 489.105(3)(f), (g), or (i) or an
approved third party. A written report of
the results of the test shall be signed
by the third party conducting the test
and provided to the code official.';
                $settings['msg3'] = 'The building or dwelling unit shall be tested and verified as having an air leakage rate of not exceeding 7 air changes per hour
in Climate Zones 1 and 2 ... Testing shall be conducted with a blower door at a pressure of 0.2 inches w.g. (50 Pascals). Testing shall be conducted by
either individuals as defined in Section 553.993(5) or (7), Florida Statutes or individuals licensed as set forth in Section 489.105(3)(f), (g), or (i) or an
approved third party. A written report of the results of the test shall be signed by the party conducting the test and provided to the code official. Testing
shall be performed at any time after creation of all penetrations of the building thermal envelope. ';
                $settings['msg7'] = 'FORM R405-2017 Envelope Leakage Test Report Performance';
                $settings['msg8'] = 'Performance Method';
            }

            $html_body .= '<h2 class="font-light" style="font-size: 13px;">' . $settings['msg7'] . '</h2>';
            $html_body .= '<h1 class="title text-center">FLORIDA ENERGY EFFICIENCY CODE FOR BUILDING CONSTRUCTION</h1>';
            $html_body .= '<h1 class="sub-title text-center font-light" style="margin-top: 3px; padding:0 100px;">Envelope Leakage Test Report<br>' . $settings['msg8'] . '</h1>';

            $html_body .= '<div class="row" style="padding: 0 2px;">';
            $html_body .= '<table class="performance-method width-full" style="">';
            $html_body .= '<tr><td style="width: 55%;">Project Name: <span class="text-value">' . $inspection['community'] . '</span></td><td style="width: 45%;">Builder Name: <span class="text-value">' . $builder . '</td></tr>';
            $html_body .= '<tr><td>Street: <span class="text-value">' . $inspection['address'] . '</span></td><td>Permit Office: </td></tr>';
            $html_body .= '<tr><td>City, State, Zip: <span class="text-value">' . $inspection['city'] . '</span></td><td>Permit Number: <span class="text-value">' . $inspection['permit_number'] . '</span></td></tr>';
            $html_body .= '<tr><td>Design Location: <span class="text-value">' . $inspection['design_location'] . '</span></td><td>Jurisidiction: </td></tr>';
            $html_body .= '<tr><td>Cond. Floor Area: <span class="text-value">' . $this->show_decimal($inspection['area'], 0) . ' sq.ft</span></td><td>Cond. Volume: <span class="text-value">' . $this->show_decimal($inspection['volume'], 0) . ' cu.ft</span></td></tr>';
            $html_body .= '</table>';
            $html_body .= '</div>';

            $cfm25_system_1 = $this->cfm25($inspection_id, 1);
            $cfm25_system_2 = $this->cfm25($inspection_id, 2);
            $cfm25_system_3 = $this->cfm25($inspection_id, 3);
            $cfm25_system_4 = $this->cfm25($inspection_id, 4);

            $html_body .= '<div class="row" style="height: 12px;"></div>';
            $html_body .= '<div class="row" style="padding: 0 16px;">';
            $html_body .= '<table class="width-full"><tr>';

            $c = floatval($inspection['flow']) / pow(floatval($inspection['house_pressure']), 0.65);
            $cfm50 = $c * 12.7154;
            $ela = $cfm50 * 0.055;
            $eqla = $cfm50 * 0.1032;
            $ach = floatval($inspection['ach50']) / 25.36;
            $sla = $ela * 0.00694 / floatval($inspection['area']);

            $html_body .= '<td class="width-70-percent" style="vertical-align:top; line-height: 14px;">';
            $html_body .= '<div class="row" style="padding: 0 16px;">';
            $html_body .= '<h2 class="part-title" style="margin-bottom: 20px;">Envelope Leakage Test Results</h2>';
            $html_body .= '<br>';
            $html_body .= '<h3 style="font-size: 13.92px; font-weight: 100; margin: 4px 0px 4px 0px;">Regression Data: </h3>';
            $html_body .= '<h3 style="font-size: 13.92px; font-weight: 100; margin: 4px 0px 4px 0px; padding-left: 8px;">'
                . '&nbsp; C: <span class="text-underline">&nbsp;&nbsp;&nbsp; ' . $this->show_decimal($c, 5) . ' &nbsp;&nbsp;&nbsp;</span> '
                . '&nbsp; n: <span class="text-underline">&nbsp;&nbsp;&nbsp; 0.65 &nbsp;&nbsp;&nbsp;</span> '
                . '&nbsp; R: <span class="text-underline">&nbsp;&nbsp;&nbsp; N/A &nbsp;&nbsp;&nbsp;</span>'
                . '</h3>';
            $html_body .= '<br>';
            $html_body .= '<h3 style="font-size: 13.92px; font-weight: 100; margin: 4px 0 8px;">Single or Multi Point Test Data</h3>';
            $html_body .= '<table class="test-result" style="width: 200px;">';
            $html_body .= '<tr><td class="result-line">&nbsp;</td><td class="house-pressure">HOUSE PRESSURE</td><td class="flow">FLOW</td></tr>';
            $html_body .= '<tr><td class="result-line">&nbsp;</td><td class="house-pressure">' . $this->show_decimal($inspection['house_pressure'], 1) . '</td><td class="flow">' . $this->show_decimal($inspection['flow'], 1) . '</td></tr>';
            $html_body .= '</table>';
            $html_body .= '</div>';
            $html_body .= '</td>';

            $html_body .= '<td class="width-30-percent" style="vertical-align:top; ' . $settings['padding1'] . '">';
            $html_body .= '<h2 class="part-title" style="' . $settings['padding1'] . '">Leakage Characteristics</h2>';
            // html1
            $html_body .= $settings['html_1'];
            $html_body .= '</td>';

            $html_body .= '</tr></table>';
            $html_body .= '</div>';


            $html_body .= $settings['html_4'];
            $html_body .= '<div class="row" style="padding: 0 12px;">';
            $html_body .= '<h3 style="font-size: 12.24px; font-weight: 100; margin: 4px 0 2px; line-height: 14px;">';
            $html_body .= '<span class="font-bold">R402.4.1.2.Testing.</span>&nbsp;';
            $html_body .= $settings['msg3'];
            $html_body .= '</h3>';
            $html_body .= '<div style="padding: 0 24px;">';
            $html_body .= '<h3 style="font-size: 12.0px; font-weight: 100; margin: 2px 0;">During testing:</h3>';
            $html_body .= '<ul style="font-size: 12.0px; line-height: 13px; margin: 4px 0; list-style-type:decimal; ">';
            $html_body .= '<li>Exterior windows and doors, fireplace and stove doors shall be closed, but not sealed, beyond the intended weatherstripping or other infiltration control measures;</li>';
            $html_body .= '<li>Dampers including exhaust, intake, makeup air, backdraft and flue dampers shall be closed, but not sealed beyond intended infiltration control measures;</li>';
            $html_body .= '<li>Interior doors, if installed at the time of the test, shall be open;</li>';
            $html_body .= '<li>Exterior doors for continuous ventilation systems and heat recovery ventilators shall be closed and sealed;</li>';
            $html_body .= '<li>Heating and cooling systems, if installed at the time of the test, shall be turned off; and</li>';
            $html_body .= '<li>Supply and return registers, if installed at the time of the test, shall be fully open.</li>';
            $html_body .= '</ul>';
            $html_body .= '</div>';
            $html_body .= '</div>';


            $html_body .= '<div class="row" style="height: 16px;"></div>';
            $html_body .= '<div class="row"><table class="width-full footer"><tr>';

            $html_body .= '<td class="width-40-percent footer-padding">';
            $html_body .= '<br><br>';
            $html_body .= '<h2 class="footer-description">I hereby certify that the above envelope leakage performance results demonstrate compliance with Florida Energy Code requirements in accordance with Section R402.4.1.2.</h2>';

            $html_body .= '<table class="">'
                . '<tr>'
                . '<td style="vertical-align:bottom;"><span class="footer-value">SIGNATURE: </span></td>'
                . '<td style="padding-left: 8px; vertical-align:bottom; border-bottom: 1px solid #000;"><img class="img-responsive" src="' . $this->image_url_change(base_url()) . 'resource/upload/signature.png" alt="" style="height: 42px;"></td>'
                . '</tr>'
                . $settings['html_3']
                . '</table>';
            $html_body .= '<br>';
            $html_body .= '<h3 class="footer-value">PRINTED NAME: <span class="text-underline">&nbsp;&nbsp;&nbsp;' . 'Tom Karras' . '&nbsp;&nbsp;&nbsp;</span></h3>';
            $html_body .= '<br>';
            $html_body .= '<h3 class="footer-value">DATE: <span class="text-underline">&nbsp;&nbsp;&nbsp;' . $inspection['end_date'] . '&nbsp;&nbsp;&nbsp;</span></h3>';
            $html_body .= '</td>';

            $html_body .= '<td class="width-60-percent footer-small-padding">';
            $html_body .= '<table class="width-full">'
                . '<tr>'
                . '<td style="vertical-align: middle;"><h2 class="footer-description" style="padding-right: 16px; padding-top: 8px;">' . $settings['msg2'] . '</h2></td>'
                . '<td style="padding-left:12px;"><img src="' . $this->image_url_change(base_url()) . 'resource/upload/wci.png" alt="" style="width: 164px; margin-top: 4px;"></td>'
                . '</tr>'
                . '</table>';
            $html_body .= '<br>';
            $html_body .= '<h3 class="footer-value">BUILDING OFFICIAL: _______________</h3>';
            $html_body .= '<h3 class="footer-value">DATE: ____________________________</h3>';
            $html_body .= '</td>';

            $html_body .= '</tr></table></div>';

            $html_body .= '<div class="row" style="height: 16px;"></div>';
        }

        $html_body .= '</body>';

        $html .= $html_head . $html_body;
        $html .= '</html>';

        return $html;
    }

    private function cfm25($inspection_id, $no)
    {
        $unit = $this->utility_model->get('ins_unit', array('inspection_id' => $inspection_id, 'no' => $no));
        if ($unit) {
            $result = 0;
            if ($unit['supply'] != "") {
                $result += floatval($unit['supply']);
            }
            if ($unit['return'] != "") {
                $result += floatval($unit['return']);
            }
            return $result / 2;
        } else {
            return 0;
        }
    }

    private function show_decimal($value, $decimal, $is_integer = false)
    {
        return number_format(floatval($value), intval($decimal), ".", "‚");
    }

    private function image_url_change($url)
    {
        $url = str_replace("https://", "http://", $url);
        return $url;
    }

    private function get_report_data__for_payable_payroll($inspector, $period, $start_date, $end_date, $is_array = false)
    {
        $reports = array();

        $table = " select * from ins_inspector_payroll a ";

        $filter_sql = "";
        if ($inspector != "") {
            if ($filter_sql != "") {
                $filter_sql .= " and ";
            }

            $filter_sql .= " a.inspector_id='$inspector' ";
        }

        if ($period != "") {
            if ($filter_sql != "") {
                $filter_sql .= " and ";
            }

            $filter_sql .= " a.start_date='$period' ";
        }

        if ($start_date != "" || $end_date != "") {
            if ($filter_sql != "") {
                $filter_sql .= " and ";
            }

            $date_sql = " ( a.transaction_date is null or a.transaction_date='' or ";
            if ($start_date != "" && $end_date != "") {
                $date_sql .= " ( a.transaction_date>='" . $start_date . "' and a.transaction_date<='" . $end_date . "' ) ";
            } elseif ($start_date != "") {
                $date_sql .= " a.transaction_date>='" . $start_date . "' ";
            } else {
                $date_sql .= " a.transaction_date<='" . $end_date . "' ";
            }
            $date_sql .= " ) ";

            $filter_sql .= $date_sql;
        }

        $sql = $table;
        if ($filter_sql != "") {
            $sql .= " where " . $filter_sql;
        }

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Processed Inspector's Payments Report";

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        $cls = "text-right";
        if ($inspector != "") {
            $u = $this->utility_model->get("ins_user", array('id' => $inspector));
            if ($u) {
                $sub_title = "Inspector: " . $u['first_name'] . " " . $u['last_name'];
            }
        }

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Inspector Name</th>' .
            '<th>Email</th>' .
            '<th>Phone Number</th>' .
            '<th>Address</th>' .
            '<th>Pay Period</th>' .
            '<th>Check Amount</th>' .
            '<th>Check Number</th>' .
            '<th>Transaction Date</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql .= " order by a.updated_at asc ";

        if (true) {
            array_push($reports, array(
                'name' => "Inspection Name",
                'email' => 'Email',
                'phone' => 'Phone Number',
                'address' => 'Address',
                'period' => 'Pay Period',
                'amount' => 'Check Amount',
                'number' => 'Check Number',
                'transaction_date' => 'Transaction Date',
            ));
        }

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $html_body .= '<td class="">' . $row['inspector_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['inspector_email'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['inspector_phone'] . '</td>';
                $html_body .= '<td class="">' . $row['inspector_address'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['start_date'] . " ~ " . $row['end_date'] . '</td>';
                $html_body .= '<td class="text-center">$' . number_format($row['check_amount'], 2) . '</td>';
                $html_body .= '<td class="text-center">' . $row['check_number'] . '</td>';
                $html_body .= '<td class="text-center">$' . $row['transaction_date'] . '</td>';

                //                $cls = "";
                //                $field_value = "";
                //                if ($row['status'] == 1) {
                //                    $cls = "label-success";
                //                    $field_value = "PAID";
                //                } else {
                //                    $cls = "label-warning";
                //                    $field_value = "PENDING";
                //                }
                //
                //                $html_body .= '<td class="text-center"><span class="label '. $cls  . '">' . $field_value  . '</span></td>';
                $html_body .= '</tr>';

                array_push($reports, array(
                    'name' => $row['inspector_name'],
                    'email' => $row['inspector_email'],
                    'phone' => $row['inspector_phone'],
                    'address' => $row['inspector_address'],
                    'period' => $row['start_date'] . " ~ " . $row['end_date'],
                    'amount' => number_format($row['check_amount'], 2),
                    'number' => $row['check_number'],
                    'transaction_date' => $row['transaction_date'],
//                    'status'=>$row['status']==1 ? "PAID" : "PENDING",
                ));
            }
        }

        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_payable_re_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, $is_array = false)
    {
        $reports = array();

        $table = " ins_region r, ins_code c1, ins_code c2, "
            . " ins_inspection a "
            . " left join "
            . " ( SELECT t.type, t.job_number, bbb.address, COUNT(*) AS inspection_count "
            . " FROM ins_inspection t "
            . " LEFT JOIN ins_building_unit bbb ON REPLACE(t.job_number,'-','')=REPLACE(bbb.job_number, '-', '') AND bbb.address=t.address and t.is_building_unit=1 "
            . " GROUP BY t.job_number, bbb.address, t.type ) p "
            . " ON p.type=a.type AND a.job_number=p.job_number AND (a.address=p.address OR p.address IS NULL) "
            . " LEFT JOIN ins_inspection_requested q ON a.requested_id=q.id "
            . " LEFT JOIN ins_admin u ON a.field_manager=u.id AND u.kind=2 "
            . " LEFT JOIN ins_community tt ON tt.community_id=a.community "
            . " WHERE a.region=r.id AND c1.kind='ins' AND c1.code=a.type AND c2.kind='rst' "
            . "       AND c2.code=a.result_code AND p.inspection_count>1 "
            . " ";

        $common_sql = "";

        if ($start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.region='$region' ";
        }

        if ($community != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.community='$community' ";
        }

        if ($status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.result_code='$status' ";
        }

        if ($type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }

        if ($epo_status !== false && $epo_status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            if ($epo_status == "0_1") {
                $common_sql .= " ( a.epo_status=0 or a.epo_status=1 ) ";
            } else {
                $common_sql .= " a.epo_status='$epo_status' ";
            }
        }

        $sql = " select  a.*, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " (p.inspection_count-1) as inspection_count, q.epo_number as requested_epo_number, '' as pay_invoice_number, "
            . " a.epo_number as inspection_epo_number, a.epo_status as inspection_epo_status, a.invoice_number as inspection_invoice_number, "
            . " u.first_name, u.last_name, '' as additional "
            . " from " . $table . " ";

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $count_sql = " select count(*) from ( " . $sql . " ) ttt ";
        $total = $this->datatable_model->get_count($count_sql);

        $count_text = "<h4 class='total-inspection'>Total: " . $total . "";

        $count_sql = " SELECT c.name AS result_name, t.result_code, t.tnt "
            . " FROM ins_code c, ( select a.result_code, count(*) as tnt from ( $sql ) a group by a.result_code ) t "
            . " WHERE c.kind='rst' AND c.code=t.result_code ORDER BY c.code ";

        $tnt = $this->utility_model->get_list__by_sql($count_sql);
        if ($tnt && is_array($tnt)) {
            foreach ($tnt as $row) {
                if ($count_text != "") {
                    $count_text .= ", ";
                }

                $count_text .= '<span class="total-' . $row['result_code'] . '">';
                $count_text .= $row['result_name'] . ": " . $row['tnt'];
                if ($total != 0) {
                    $tnt = intval($row['tnt']);
                    $count_text .= "(" . round($tnt * 1.0 / $total * 100, 2) . "%)";
                }
                $count_text .= "</span>";
            }
        }

        $count_text .= "</h4>";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Re-Inspections EPO Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        if ($community != "") {
            $c = $this->utility_model->get('ins_community', array('community_id' => $community));
            if ($c) {
                if ($sub_title != "") {
                    $sub_title .= ", ";
                }

                $sub_title .= $c['community_name'];
            }
        }

        $cls = "text-right";

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Type</th>' .
            '<th>Region</th>' .
            '<th>Community</th>' .
            '<th>Job Number</th>' .
            '<th>Address</th>' .
            '<th>Field Manager</th>' .
            '<th>Date</th>' .
            '<th>Result</th>' .
            '<th>Status</th>' .
            '<th>EPO Number</th>' .
            '<th>EPO Status</th>' .
            '<th>Invoice Number</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql = " select  a.*, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " p.inspection_count, q.epo_number as requested_epo_number, pay.invoice_number as pay_invoice_number, "
            . " a.epo_number as inspection_epo_number, a.epo_status as inspection_epo_status, a.invoice_number as inspection_invoice_number, "
            . " u.first_name, u.last_name, '' as additional "
            . " from " . $table . " ";
        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $sql .= " order by a.start_date desc ";

        array_push($reports, array(
            'inspection_type' => "Inspection Type",
            'region' => 'Region',
            'community' => 'Community',
            'job_number' => 'Job Number',
            'address' => 'Address',
            'field_manager' => 'Field Manager',
            'date' => 'Date',
            'result' => 'Result',
            'status' => 'Status',
            'epo_number' => 'EPO Number',
            'epo_status' => 'EPO Status',
            'invoice_number' => 'Invoice Number',
        ));

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                // replace community name.  2016/11/3
                $community_name = ""; // $row['community'];
                if (isset($row['community_name']) && $row['community_name'] != "") {
                    $community_name = $row['community_name'];
                }

                $html_body .= '<td class="text-center">' . $row['inspection_type'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['region_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $community_name . '</td>';
                $html_body .= '<td class="text-center">' . $row['job_number'] . '</td>';
                $html_body .= '<td>' . $row['address'] . '</td>';
                $html_body .= '<td class="text-center">' . $field_manager . '</td>';

                $html_body .= '<td class="text-center">' . $row['start_date'] . '</td>';

                $cls = "";
                if ($row['result_code'] == '1') {
                    $cls = "label-success";
                }
                if ($row['result_code'] == '2') {
                    $cls = "label-warning";
                }
                if ($row['result_code'] == '3') {
                    $cls = "label-danger";
                }

                $html_body .= '<td class="text-center"><span class="label ' . $cls . '">' . $row['result_name'] . '</span></td>';
                $html_body .= '<td class="text-center"><span class="label ' . ($row['house_ready'] == "1" ? "label-success" : "label-warning") . '">' . ($row['house_ready'] == "1" ? "House Ready" : "House Not Ready") . '</span></td>';

                $epo_number = " ";
                $epo_status = $row['inspection_epo_status'];
                $invoice_number = " ";

                if (isset($row['inspection_epo_number']) && $row['inspection_epo_number'] != "") {
                    $epo_number = $row['inspection_epo_number'];
                } elseif (isset($row['requested_epo_number']) && $row['requested_epo_number'] != 0) {
                    $epo_number = $row['requested_epo_number'];
                }

                if (isset($row['pay_invoice_number']) && $row['pay_invoice_number'] != "") {
                    $invoice_number = $row['pay_invoice_number'];
                } else {
                    $invoice_number = $row['inspection_invoice_number'];
                }


                $html_body .= '<td class="text-center">' . $epo_number . '</td>';
                $html_body .= '<td class="text-center">' . $this->get_epo_status_title($epo_status) . '</td>';
                $html_body .= '<td class="text-center">' . $invoice_number . '</td>';

                $html_body .= '</tr>';

                array_push($reports, array(
                    'inspection_type' => $row['inspection_type'],
                    'region' => $row['region_name'],
                    'community' => $community_name,
                    'job_number' => $row['job_number'],
                    'address' => $row['address'],
                    'field_manager' => $field_manager,
                    'date' => $row['start_date'],
                    'result' => $row['result_name'],
                    'status' => $row['house_ready'] == "1" ? "House Ready" : "House Not Ready",
                    'epo_number' => $epo_number,
                    'epo_status' => $this->get_epo_status_title($epo_status),
                    'invoice_number' => $invoice_number,
                ));
            }
        }

        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {

            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_payable_pending_inspection($region, $community, $start_date, $end_date, $status, $type, $epo_status, $payment_status, $re_inspection, $is_array = false)
    {
        $reports = array();

        $ins_re_inspection = " ( "
            . " SELECT t.type, t.job_number, bbb.address, COUNT(*) AS inspection_count "
            . " FROM ins_inspection t "
            . " LEFT JOIN ins_building_unit bbb ON REPLACE(t.job_number,'-','')=REPLACE(bbb.job_number, '-', '') AND bbb.address=t.address and t.is_building_unit=1 "
            . " GROUP BY t.job_number, bbb.address, t.type "
            . " ) ";

        $table = " ins_region r, ins_code c1, ins_code c2,  "
            . " ins_inspection a "
            . " left join " . $ins_re_inspection . " p ON p.type=a.type AND a.job_number=p.job_number AND (a.address=p.address OR p.address IS NULL) "
            . " LEFT JOIN ins_inspection_requested q ON a.requested_id=q.id "
            . " LEFT JOIN ins_admin u ON a.field_manager=u.id AND u.kind=2 "
            . " LEFT JOIN ins_community tt ON tt.community_id=a.community "
            . " left join ins_inspection_paid pay on pay.inspection_id=a.id "
            . " WHERE a.region=r.id AND c1.kind='ins' AND c1.code=a.type AND c2.kind='rst' "
            . " AND c2.code=a.result_code "
            . " ";

        $common_sql = "";

        if ($start_date !== false && $start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.start_date>='$start_date' ";
        }

        if ($end_date !== false && $end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.end_date<='$end_date' ";
        }

        if ($region !== false && $region != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.region='$region' ";
        }

        if ($community !== false && $community != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.community='$community' ";
        }

        if ($status !== false && $status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.result_code='$status' ";
        }

        if ($type !== false && $type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.type='$type' ";
        }

        if ($epo_status !== false && $epo_status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            if ($epo_status == "0_1") {
                $common_sql .= " ( a.epo_status=0 or a.epo_status=1 ) ";
            } else {
                $common_sql .= " a.epo_status='$epo_status' ";
            }
        }

        if ($re_inspection !== false && $re_inspection != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            if ($re_inspection == "1") {
                $common_sql .= " ( p.inspection_count>1 and a.first_submitted=0 ) ";
            }
            if ($re_inspection == "0") {
                $common_sql .= " ( p.inspection_count<=1 and a.first_submitted=1 ) ";
            }
        }

        if ($payment_status !== false && $payment_status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            if ($payment_status == "1") {
                $common_sql .= " pay.invoice_id is not null ";
            } else {
                $common_sql .= " pay.invoice_id is null ";
            }
        }

        $sql = " select  a.*, "
            . " c1.name as inspection_type, c2.name as result_name, "
            . " r.region as region_name, tt.community_name, "
            . " (p.inspection_count-1) as inspection_count, q.epo_number as requested_epo_number, "
            . " pay.invoice_id, pay.invoice_amount, pay.check_number, pay.invoice_number as payment_invoice_number, "
            . " u.first_name, u.last_name, '' as additional "
            . " from " . $table . " ";

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        $count_sql = " select count(*) from ( " . $sql . " ) ttt ";
        $total = $this->datatable_model->get_count($count_sql);

        $count_text = "<h4 class='total-inspection'>Total Inspections: " . $total . "";

        $count_sql = " select count(*) from ( " . $sql . " and pay.invoice_id is not null " . " ) ttt ";
        $total_paid = $this->datatable_model->get_count($count_sql);
        $count_text .= '<span class="total-1">, Total Inspection Paid: ' . $total_paid . '</span>';

        $count_sql = " select count(*) from ( " . $sql . " and pay.invoice_id is null " . " ) ttt ";
        $total_pending = $this->datatable_model->get_count($count_sql);
        $count_text .= '<span class="total-2">, Total Inspection Pending: ' . $total_pending . '</span>';

        $count_sql = " select sum(ttt.invoice_amount) as invoice_amount from ( " . $sql . " and pay.invoice_id is not null " . " ) ttt ";
        $amount_received = $this->utility_model->get__by_sql($count_sql);
        $count_text .= '<span class="total-1">, Total $ Received : ' . (isset($amount_received) && isset($amount_received['invoice_amount']) ? number_format($amount_received['invoice_amount'], 2) : "0.00") . '</span>';

        $count_text .= '<span class="total-2">, Total $ Pending : ';

        $count_sql = " select vvvvv.type, "
            . " sum(case when vvvvv.first_submitted=1 and vvvvv.invoice_id is null then 1 else 0 end) as inspection_count, "
            . " sum(case when vvvvv.first_submitted=0 and vvvvv.invoice_id is null and vvvvv.epo_number is not null and vvvvv.epo_number<>'' then 1 else 0 end) as re_inspection_count "
            . " from ( " . $sql . " and (a.type=1 or a.type=2) " . " ) vvvvv "
            . " group by vvvvv.type ";

        $pending_amount = 0.0;
        $query_sql = " select (fee.inspection_fee*qwert.inspection_count) as inspection_fee, "
            . " (fee.re_inspection_fee*qwert.re_inspection_count) as re_inspection_fee "
            . " from ( " . $count_sql . " ) qwert, ins_builder_fee fee"
            . " where fee.builder_id=1 and fee.inspection_type=qwert.type ";

        $amounts = $this->utility_model->get_list__by_sql($query_sql);
        if ($amounts) {
            foreach ($amounts as $row) {
                if (isset($row['inspection_fee'])) {
                    $pending_amount += $row['inspection_fee'];
                }

                if (isset($row['re_inspection_fee'])) {
                    $pending_amount += $row['re_inspection_fee'];
                }
            }
        }

        $count_sql = " select vvvvv.type, "
            . " sum(case when vvvvv.first_submitted=1 and vvvvv.invoice_id is null then 1 else 0 end) as inspection_count, "
            . " sum(case when vvvvv.first_submitted=0 and vvvvv.invoice_id is null and vvvvv.epo_number is not null and vvvvv.epo_number<>'' then 1 else 0 end) as re_inspection_count "
            . " from ( " . $sql . " and (a.type=3) " . " ) vvvvv "
            . " group by vvvvv.type ";

        $query_sql = " select (fee.inspection_fee*qwert.inspection_count) as inspection_fee, "
            . " (fee.re_inspection_fee*qwert.re_inspection_count) as re_inspection_fee "
            . " from ( " . $count_sql . " ) qwert, ins_builder_fee fee"
            . " where fee.builder_id=2 and fee.inspection_type=qwert.type ";

        $amounts = $this->utility_model->get_list__by_sql($query_sql);
        if ($amounts) {
            foreach ($amounts as $row) {
                if (isset($row['inspection_fee'])) {
                    $pending_amount += $row['inspection_fee'];
                }

                if (isset($row['re_inspection_fee'])) {
                    $pending_amount += $row['re_inspection_fee'];
                }
            }
        }

        $count_text .= number_format($pending_amount, 2);
        $count_text .= '</span>';

        $count_text .= "</h4>";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Inspections Pending Payment Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $sub_title = "";
        if ($region != "") {
            $r = $this->utility_model->get('ins_region', array('id' => $region));
            if ($r) {
                $sub_title .= $r['region'];
            }
        }

        if ($community != "") {
            $c = $this->utility_model->get('ins_community', array('community_id' => $community));
            if ($c) {
                if ($sub_title != "") {
                    $sub_title .= ", ";
                }

                $sub_title .= $c['community_name'];
            }
        }

        $cls = "text-right";

        if ($sub_title != "") {
            $html_body .= "<h5 class='" . $cls . "'>" . $sub_title . "</h5>";
        }

        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Type</th>' .
            '<th>Region</th>' .
            '<th>Community</th>' .
            '<th>Job Number</th>' .
            '<th>Address</th>' .
            '<th>Field Manager</th>' .
            '<th>Date</th>' .
            '<th>Result</th>' .
            '<th>EPO Number</th>' .
            '<th>EPO Status</th>' .
            '<th>Payment Status</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql .= " order by a.start_date desc ";

        array_push($reports, array(
            'inspection_type' => "Inspection Type",
            'region' => 'Region',
            'community' => 'Community',
            'job_number' => 'Job Number',
            'address' => 'Address',
            'field_manager' => 'Field Manager',
            'date' => 'Date',
            'result' => 'Result',
            'epo_number' => 'EPO Number',
            'epo_status' => 'EPO Status',
            'payment_status' => 'Paymenet Status',
        ));

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                // replace community name.  2016/11/3
                $community_name = ""; // $row['community'];
                if (isset($row['community_name']) && $row['community_name'] != "") {
                    $community_name = $row['community_name'];
                }

                $html_body .= '<td class="text-center">' . $row['inspection_type'] . '</td>';
                $html_body .= '<td class="text-center">' . $row['region_name'] . '</td>';
                $html_body .= '<td class="text-center">' . $community_name . '</td>';
                $html_body .= '<td class="text-center">' . $row['job_number'] . '</td>';
                $html_body .= '<td>' . $row['address'] . '</td>';
                $html_body .= '<td class="text-center">' . $field_manager . '</td>';

                $html_body .= '<td class="text-center">' . $row['start_date'] . '</td>';

                $cls = "";
                if ($row['result_code'] == '1') {
                    $cls = "label-success";
                }
                if ($row['result_code'] == '2') {
                    $cls = "label-warning";
                }
                if ($row['result_code'] == '3') {
                    $cls = "label-danger";
                }

                $html_body .= '<td class="text-center"><span class="label ' . $cls . '">' . $row['result_name'] . '</span></td>';

                $epo_number = " ";
                $epo_status = $row['epo_status'];
                $payment_status = false;

                //                if (isset($row['inspection_epo_number']) && $row['inspection_epo_number']!="") {
                //                    $epo_number = $row['inspection_epo_number'];
                //                }

                if (isset($row['invoice_id']) && $row['invoice_id'] != "") {
                    $payment_status = true;
                }


                $html_body .= '<td class="text-center">' . $epo_number . '</td>';
                $html_body .= '<td class="text-center">' . $this->get_epo_status_title($epo_status) . '</td>';
                $html_body .= '<td class="text-center"><span class="label ' . ($payment_status === true ? "label-success" : "label-warning") . '">' . ($payment_status === true ? "PAID" : "PENDING") . '</span></td>';

                $html_body .= '</tr>';

                array_push($reports, array(
                    'inspection_type' => $row['inspection_type'],
                    'region' => $row['region_name'],
                    'community' => $community_name,
                    'job_number' => $row['job_number'],
                    'address' => $row['address'],
                    'field_manager' => $field_manager,
                    'date' => $row['start_date'],
                    'result' => $row['result_name'],
                    'epo_number' => $epo_number,
                    'epo_status' => $this->get_epo_status_title($epo_status),
                    'payment_status' => $payment_status === true ? "PAID" : "PENDING",
                ));
            }
        }

        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_report_data__for_requested_inspection($start_date, $end_date, $status, $type, $is_array = false)
    {
        $reports = array();

        $table = " ins_code c1, ins_inspection_requested a "
            . " left join ins_community c on c.community_id=substr(a.job_number,1,4)"
            . " left join ins_region r on c.region=r.id "
            . " left join ins_admin m on a.manager_id=m.id "
            . " left join ins_user u on a.inspector_id=u.id "
            . " where c1.kind='ins' and c1.code=a.category ";  // and ( a.status=0 or a.status=1 )

        if ($this->session->userdata('permission') == 2) {
            $table .= " and a.manager_id='" . $this->session->userdata('user_id') . "' ";
        } elseif ($this->session->userdata('permission') == 0) {
            $table .= " and a.inspector_id='" . $this->session->userdata('user_id') . "' ";
        }

        $common_sql = "";

        if ($start_date !== false && $start_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.requested_at>='$start_date' ";
        }

        if ($end_date !== false && $end_date != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.requested_at<='$end_date' ";
        }

        if ($type !== false && $type != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.category='$type' ";
        }

        if ($status !== false && $status != "") {
            if ($common_sql != "") {
                $common_sql .= " and ";
            }

            $common_sql .= " a.status='$status' ";
        }

        $sql = " select  a.id, a.category, a.reinspection, a.epo_number, a.job_number, a.requested_at, a.assigned_at, a.completed_at, a.manager_id, a.inspector_id, "
            . " a.time_stamp, a.ip_address, a.community_name, a.lot, a.address, a.status, a.area, a.volume, a.qn, a.city as city_duct, "
            . " '' as additional, "
            . " m.first_name, m.last_name,"
            . " concat(u.first_name, ' ', u.last_name) as inspector_name, "
            . " c1.name as category_name, c.community_id, c.region, r.region as region_name, c.city "
            . " from " . $table . " ";

        if ($common_sql != "") {
            $sql .= " and " . $common_sql;
        }

        //        $count_sql = " select count(*) from ( " . $sql . " ) ttt ";
        //        $total = $this->datatable_model->get_count($count_sql);
        //
        //        $count_text = "<h4 class='total-inspection'>Total: " . $total . "";
        //
        //        $count_sql = " SELECT c.name AS result_name, t.result_code, t.tnt "
        //                . " FROM ins_code c, ( select a.result_code, count(*) as tnt from ( $sql ) a group by a.result_code ) t "
        //                . " WHERE c.kind='rst' AND c.code=t.result_code ORDER BY c.code ";
        //
        //        $tnt = $this->utility_model->get_list__by_sql($count_sql);
        //        if ($tnt && is_array($tnt)) {
        //            foreach ($tnt as $row) {
        //                if ($count_text!="") {
        //                    $count_text .= ", ";
        //                }
        //
        //                $count_text .= '<span class="total-' . $row['result_code'] . '">';
        //                $count_text .= $row['result_name'] . ": " . $row['tnt'];
        //                if ($total!=0) {
        //                    $tnt = intval($row['tnt']);
        //                    $count_text .= "(" . round($tnt*1.0/$total * 100, 2) . "%)";
        //                }
        //                $count_text .= "</span>";
        //            }
        //        }
        //
        //        $count_text .= "</h4>";

        $table_styles = " .data-table {width: 100%; border: 1px solid #000; } "
            . " .data-table thead th { padding: 7px 5px; } "
            . " .table-bordered { border-collapse: collapse; }"
            . " .table-bordered thead th, .table-bordered tbody td { border: 1px solid #000; }  "
            . " .table-bordered tbody td { font-size: 85%; padding: 4px 4px; }  ";

        $html_styles = "<style type='text/css'> " . $table_styles . " "
            . " .text-right{text-align:right;} "
            . " .text-center{text-align:center;} "
            . " .row{float:left;width:100%;margin-bottom:20px;} "
            . " span.label{} .label-danger{color:#d9534f;} .label-success{color:#5cb85c;} .label-warning{color:#f0ad4e;} "
            . " .col-50-percent{float:left;width:50%;} "
            . ".total-inspection span , .total-checklist span { font-size: 84%; } .total-checklist span.total-1 { color: #02B302; } .total-checklist span.total-2 { color: #e33737; } .total-checklist span.total-3 { color: #11B4CE; }  .total-inspection span.total-1 { color: #02B302; } .total-inspection span.total-2 { color: #e89701; } .total-inspection span.total-3 { color: #e33737; }"
            . "</style>";

        $html_header = "<html><head><meta charset='utf-8'/><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/><title>Report</title>" . $html_styles . "</head><body>";
        $html_body = "";

        $html_body .= "<div class='row text-center'>" . '<img alt="" src="' . $this->image_url_change(LOGO_PATH) . '" style="margin: auto; max-width: 400px;">' . "</div>";

        $cls = "text-center";
        $title = "Requested Inspection Report";
        if ($type == '1') {
            $title = "Drainage Plane " . $title;
        }
        if ($type == "2") {
            $title = "Lath " . $title;
        }
        if ($type == "3") {
            $title = "WCI Duct Leakage " . $title;
        }

        $html_body .= "<h1 class='" . $cls . "'>" . $title . "</h1>";

        $cls = "text-right";
        if ($start_date != "" && $end_date != "") {
            $html_body .= "<h6 class='" . $cls . "'>" . $start_date . " ~ " . $end_date . "</h6>";
        }

        //        if ($count_text!="") {
        //            $html_body .=  $count_text ;
        //        }

        $html_body .= '<div class="row">';

        $html_body .= '<table class="data-table table-bordered">';
        $html_body .= '' .
            '<thead>' .
            '<tr>' .
            '<th>Inspection Date</th>' .
            '<th>Community</th>' .
            '<th>Job Number</th>' .
            '<th>Address</th>' .
            '<th>City</th>' .
            '<th>Field Manager</th>' .
            '<th>Inspection Type</th>' .
            '<th>Requested Time</th>' .
            '<th>Inspector</th>' .
            '<th>Status</th>' .
            '</tr>' .
            '</thead>' .
            '';

        $html_body .= '<tbody>';

        $sql .= " order by a.requested_at desc ";

        array_push($reports, array(
            'inspection_date' => 'Inspection Date',
            'community' => 'Community',
            'job_number' => 'Job Number',
            'address' => 'Address',
            'city' => 'City',
            'field_manager' => 'Field Manager',
            'inspection_type' => "Inspection Type",
            'requested_time' => 'Requested Time',
            'inspector' => 'Inspector',
            'status' => 'Status',
        ));

        $data = $this->datatable_model->get_content($sql);
        if ($data && is_array($data)) {
            foreach ($data as $row) {
                $html_body .= '<tr>';

                $html_body .= '<td>' . $row['requested_at'] . '</td>';
                $html_body .= '<td>' . $row['community_name'] . '</td>';
                $html_body .= '<td>' . $row['job_number'] . '</td>';
                $html_body .= '<td>' . $row['address'] . '</td>';

                $city = "";
                if ($row['category'] == 3) {
                    if (isset($row['city_duct']) && $row['city_duct'] != "") {
                        $city = $row['city_duct'];
                    }
                } else {
                    if (isset($row['city']) && $row['city'] != "") {
                        $city = $row['city'];
                    }
                }

                $html_body .= '<td>' . $city . '</td>';

                $field_manager = "";
                if (isset($row['first_name']) && isset($row['last_name']) && $row['first_name'] != "" && $row['last_name'] != "") {
                    $field_manager = $row['first_name'] . $row['last_name'];
                }

                $html_body .= '<td class="text-center">' . $field_manager . '</td>';
                $html_body .= '<td>' . $row['category_name'] . '</td>';

                $requested_time = date('Y-m-d H:i:s', strtotime($row['time_stamp']));
                $html_body .= '<td>' . $requested_time . '</td>';

                $html_body .= '<td>' . $row['inspector_name'] . '</td>';


                //                // replace community name.  2016/11/3
                //                $community_name = ""; // $row['community'];
                //                if (isset($row['community_name']) && $row['community_name']!="") {
                //                    $community_name = $row['community_name'];
                //                }
                //                $html_body .= '<td class="text-center">' . $community_name  . '</td>';

                $cls = "";
                $status_name = "";
                if ($row['status'] == 2) {
                    $cls = "label-success";
                    $status_name = "Completed";
                } elseif ($row['status'] == 1) {
                    $cls = "label-warning";
                    $status_name = "Assigned";
                } else {
                    $cls = "label-default";
                    $status_name = "Unassigned";
                }

                $html_body .= '<td class="text-center"><span class="label ' . $cls . '">' . $status_name . '</span></td>';

                $html_body .= '</tr>';

                array_push($reports, array(
                    'inspection_date' => $row['requested_at'],
                    'community' => $row['community_name'],
                    'job_number' => $row['job_number'],
                    'address' => $row['address'],
                    'city' => $city,
                    'field_manager' => $field_manager,
                    'inspection_type' => $row['category_name'],
                    'requested_time' => $requested_time,
                    'inspector' => $row['inspector_name'],
                    'status' => $status_name
                ));
            }
        }


        $html_body .= '</tbody>';
        $html_body .= '</table>';

        $html_body .= '</div>';


        $html_footer = "</body></html>";

        $html = $html_header . $html_body . $html_footer;

        if ($is_array) {
            return $reports;
        } else {
            return $html;
        }
    }

    private function get_epo_status_title($status)
    {
        if ($status == 0) {
            return "To Request";
        }
        if ($status == 1) {
            return "Requested";
        }
        if ($status == 2) {
            return "Received";
        }
        if ($status == 3) {
            return "Not Needed";
        }

        return "";
    }

    public function test($id)
    {
        echo $this->get_report_html__for_envelop_leakage($id);

        echo "<br>";
        echo "End";
        echo "<br>";
    }

    function pulte_stucco_autocomplete()
    {

        $response = array(
            'status' => $this->status[0],

        );

        $community = $this->input->post('community');
        if (isset($community)) {
            $result = $this->utility_model->community_autocomplete($community);

            $json = array();
            $community_name = "";
            if ($result) {
                foreach ($result as $value) {

                    $response[] = array(
                        'community_id' => $value['community_id'],
                        'community_name' => $value['community_name'],
                    );
                    // $response[] = $value['community_name'];
                }
            } else {
                $response['status'] = $this->status[1];
            }
        } else {

            $response['status'] = $this->status[1];
        }


        print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

// echo json_encode($json);
    }

    function pulte_stucco_fetch()
    {

        $response = array(
            'status' => $this->status[1],

        );

        $query = $this->input->post('community_id');

        if (isset($query)) {
            $result = $this->utility_model->fetch_data($query);

            if ($result) {
                foreach ($result as $value) {

                    $response = array(
                        'community id' => $value['community_id'],
                        'community name' => $value['community_name'],
                        'address' => $value['address'],
                        'city' => $value['city'],
                        'state' => $value['state'],
                        'zip' => $value['zip']
                    );
                    $response['status'] = $this->status[0];
                }
            } else {
                $response['status'] = $this->status[1];
            }
        } else {

            $response['status'] = $this->status[1];
        }


        print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

// echo json_encode($json);
    }

    public function send_sms_from_android()
    {
        $job_id = $_REQUEST['job_id'];

        $list_numbers = array();
        $sqls = array();

        $sql = "select * from ins_inspection_requested where id = $job_id";
        $inspection = $this->utility_model->get__by_sql($sql);
        $sqls[] = $sql;
        if ($inspection) {
            $manager_id = $inspection['manager_id'];
            $sql = "select * from ins_admin where id = $manager_id";
            $manager1 = $this->utility_model->get__by_sql($sql);
            $sqls[] = $sql;

            if ($manager1) {
                $list_numbers[] = $manager1['cell_phone'];
            }

            $job_number = $inspection['job_number'];
            $sql = "select * from ins_building where "
                . " replace(job_number,'-','') like '%" . str_replace('-', '', $job_number) . "%'";
//            $sql = "select * from ins_building where job_number = '$job_number'";

            $sqls[] = $sql;
            $row = $this->utility_model->get__by_sql($sql);
            if ($row) {
                $field_manager = $row['field_manager'];

                $sql = "SELECT * FROM `ins_admin` WHERE concat(first_name,' ',last_name) = '$field_manager'";
                $sqls[] = $sql;
                $row_admin = $this->utility_model->get__by_sql($sql);
                if ($row_admin) {
                    $list_numbers[] = $row_admin['cell_phone'];
                }
            }
        }

//        $this->m_twilio->setDbInfo(DB_HOST, DB_DATABASE, DB_USER, DB_PASSWORD);
//        $this->m_twilio->initialize();
//        $printmode = $this->input->get("printmode");
//        if (is_string($printmode) && $printmode == "1") {
//            $ret = array();
//            $ret['numbers'] = $list_numbers;
//            $ret['sql'] = $sqls;
//            print_r(json_encode($ret));
//        } else if (is_string($printmode) && $printmode == "2") {
//            $ret = array();
//            $ret['numbers'] = $list_numbers;
//            $ret['sql'] = $sqls;
//            $ret['step2'] = 'pass';
//            $ret_wci = $this->m_twilio->wci->start($list_numbers, $inspection);
//            $ret['ret_wci'] = $ret_wci;
//            print_r(json_encode($ret));
//        } else {
//
//            $ret = $this->m_twilio->wci->start($list_numbers, $inspection);
//            print_r(json_encode($ret));
//        }

        $list_numbers = array_unique($list_numbers);

        $ret = array();
        $ret['list_numbers_raw'] = $list_numbers;
        $real_num = array();
        foreach ($list_numbers as $number1) {
            if (strlen($number1) > 8) {
                $real_num[] = $number1;
            }
        }
        $ret['list_numbers'] = $real_num;
        $ret['inspection'] = $inspection;

        return $ret;
    }

    public function send_sms_from_android2()
    {
        $this->m_twilio->setDbInfo(DB_HOST, DB_DATABASE, DB_USER, DB_PASSWORD);
        $this->m_twilio->initialize();

        $sid = $_REQUEST['sid'];
        $token = $_REQUEST['token'];
        $number1 = $_REQUEST['to'];
        $number2 = $_REQUEST['from'];
        $text = $_REQUEST['text'];


        $input_param = array('sid' => $sid
        , 'token' => $token
        , 'to' => $number1
        , 'from' => $number2
        , 'text' => $text
        );
        $ret = $this->m_twilio->wci->testSms($input_param);

        print_r(json_encode($ret));
    }

    public function send_sms_from_android3()
    {
        $ret = $this->send_sms_from_android();

        $sid = 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $token = 'your_auth_token';
        $twilio_phone1 = '15017122661';
        $text = 'default send text';

        $sql = "select * from sys_config where code = 'twilio_sid'";
        $message = $this->utility_model->get__by_sql($sql);
        if ($message) {
            $sid = $message['value'];
            $ret['sid'] = $sid;
        }

        $sql = "select * from sys_config where code = 'twilio_token'";
        $message = $this->utility_model->get__by_sql($sql);
        if ($message) {
            $token = $message['value'];
            $ret['token'] = $token;
        }

        $sql = "select * from sys_config where code = 'twilio_phone1'";
        $message = $this->utility_model->get__by_sql($sql);
        if ($message) {
            $twilio_phone1 = $message['value'];
            $ret['from'] = $twilio_phone1;
        }

        $sql = "select * from sys_config where code = 'twilio_send_text'";
        $message = $this->utility_model->get__by_sql($sql);
        if ($message) {
            $text = $message['value'];
        }

        $inspection = $ret['inspection'];
        $sql = "select * from ins_code c1 where c1.kind='ins' and c1.code= " . $inspection['category'];
        $message = $this->utility_model->get__by_sql($sql);
        $category_name = "";
        if ($message) {
            $category_name = $message['name'];
        }

        // modify text
        $text = $text . "\nJob Number: " . $inspection['job_number'];
        $text = $text . "\nLot: " . $inspection['lot'];
        $text = $text . "\nAddress: " . $inspection['address'];
        $text = $text . "\nInspection Type: " . $category_name;


        $ret['text'] = $text;

        print_r(json_encode($ret));
    }

    public function sms_response()
    {
        header("content-type: text/xml");
        $sql = "select * from sys_config where code='twilio_reply_text'";
        $row = $this->utility_model->get__by_sql($sql);

        $value = "Thanks for messaging me!";
        if ($row) {
            $value = $row['value'];
        }


        $msg = "<Response><Message>$value</Message></Response>";
        print_r($msg);
    }


    function insert_ins()
    {
        // $data=array();


        $response = array(
            'status' => $this->status[1],
            'request' => array(
                'method' => 'insert_ins',
                'param' => '111',
                'kind' => '5',
                'data' => array()
            ),
            'response' => array()
        );

        $result_data = array();


        $reassigned = $this->input->post('reassigned');
        $requested_id = $this->input->post('requested_id');
        $jobnumber = $this->input->post('job_number');
        $job_numbercommunity = explode('-', $jobnumber);


        if (!empty($job_numbercommunity[1])) {
            $community = $job_numbercommunity[0];
        } else {
            $community = substr($jobnumber, 0, 4);
        }

        // $community = substr($jobnumber, 0, -6);
        //$community = substr($jobnumber, 0, 4);
        $communitydata = $this->utility_model->get('ins_community', array('community_id' => $community));
        $region = $communitydata['region'];
        $gps_location = $this->input->post('gps_location');
        $array = explode(',', $gps_location);
        if ($gps_location == "Location Not Captured" || $gps_location == "") {
            $latitude = 0;
            $longitude = 0;
            $accuracy = 0;
        } else {
            $latitude = $array[0];
            $longitude = $array[1];
            $accuracyget = $array[2];
            $accuracy = substr($accuracyget, 10, -1);
        }


        $data_request_table = array(
            'completed_at' => date('Y-m-d'),
            'status' => 2,
        );

        $t = mdate('%Y%m%d%H%i%s', time());
        $job_number = $this->input->post('job_number');
        $data = array(
            'user_id' => $this->input->post('user_id'),
            'requested_id' => $this->input->post('requested_id'),
            'type' => $this->input->post('type'),
            'job_number' => $job_number ? $job_number : 0,
            'community_name' => $this->input->post('community_name'),
            'address' => $this->input->post('address'),
            'community' => $community,
            'lot' => $this->input->post('lot'),
            'date_requested' => $this->input->post('date_requested'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'region' => $region ? $region : 0,
            'close_escrow_date' => $this->input->post('close_escrow_date'),
            'created_at' => $t,
            'access_instructions' => $this->input->post('access_instructions'),
            'gps_location' => $gps_location ? $gps_location : 0,
            'latitude' => $latitude ? $latitude : 0,
            'longitude' => $longitude ? $longitude : 0,
            'accuracy' => $accuracy ? $accuracy : 0,
            'no_of_stories' => $this->input->post('no_of_stories'),
            'home_type' => $this->input->post('home_type'),
            'int_inspection' => $this->input->post('int_inspection'),
            'overall_comments' => $this->input->post('overall_comments'),
            'emp_per_inspection' => $this->input->post('emp_per_inspection'),
//            'image_right_building' => $this->input->post('image_front_building'),
//            'image_left_building' => $this->input->post($image_left_building),
            // 'image_back_building' => $image_back_building,
            // 'image_front_building' => $image_front_building,
            // 'image_signature'=>$image_signature,
        );


        $image_right_building = "";
        if (isset($_FILES['image_right_building'])) {


            // $url=base_url();

            $uploaddir = 'resource/upload/stucco/right/';
            $i1 = 'image_right_building';
            $image_right_building_main = $uploaddir . $i1 . '_' . ($_FILES['image_right_building']['name']);


            if (move_uploaded_file($_FILES['image_right_building']['tmp_name'], $image_right_building_main)) {

                $data['image_right_building'] = $i1 . '_' . ($_FILES['image_right_building']['name']);
            }
        }

        if (isset($_FILES['image_right_building_o'])) {
            // $url=base_url();

            $uploaddir = 'resource/upload/stucco/right/';
            $i1 = 'image_right_building_o';
            $image_right_building_main_o = $uploaddir . $i1 . '_' . ($_FILES['image_right_building_o']['name']);
            if (move_uploaded_file($_FILES['image_right_building_o']['tmp_name'], $image_right_building_main_o)) {
                $data['image_right_building_o'] = $i1 . '_' . ($_FILES['image_right_building_o']['name']);
            }
        }


        $image_left_building = "";
        if (isset($_FILES['image_left_building'])) {
            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/left/';

            $i2 = 'image_left_building';

            $image_left_building_main = $uploaddir . $i2 . '_' . ($_FILES['image_left_building']['name']);

            if (move_uploaded_file($_FILES['image_left_building']['tmp_name'], $image_left_building_main)) {

                $data['image_left_building'] = $i2 . '_' . ($_FILES['image_left_building']['name']);

            }

        }

        if (isset($_FILES['image_left_building'])) {
            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/left/';

            $i2 = 'image_left_building_o';


            $image_left_building_main_o = $uploaddir . $i2 . '_' . ($_FILES['image_left_building_o']['name']);

            if (move_uploaded_file($_FILES['image_left_building_o']['tmp_name'], $image_left_building_main_o)) {

                $data['image_left_building_o'] = $i2 . '_' . ($_FILES['image_left_building_o']['name']);

            }

        }

        $image_back_building = "";
        if (isset($_FILES['image_back_building'])) {
            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/back/';

            $i3 = 'image_back_building';

            $image_back_building_main = $uploaddir . $i3 . '_' . ($_FILES['image_back_building']['name']);

            if (move_uploaded_file($_FILES['image_back_building']['tmp_name'], $image_back_building_main)) {

                $data['image_back_building'] = $i3 . '_' . ($_FILES['image_back_building']['name']);

            }

        }


        if (isset($_FILES['image_back_building_o'])) {
            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/back/';

            $i3 = 'image_back_building_o';

            $image_back_building_main_o = $uploaddir . $i3 . '_' . ($_FILES['image_back_building_o']['name']);

            if (move_uploaded_file($_FILES['image_back_building_o']['tmp_name'], $image_back_building_main_o)) {

                $data['image_back_building_o'] = $i3 . '_' . ($_FILES['image_back_building_o']['name']);

            }

        }

        $image_front_building = "";
        if (isset($_FILES['image_front_building'])) {

            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/front/';
            $i4 = 'image_front_building';

            $image_front_building_main = $uploaddir . $i4 . '_' . ($_FILES['image_front_building']['name']);
            if (move_uploaded_file($_FILES['image_front_building']['tmp_name'], $image_front_building_main)) {


                $data['image_front_building'] = $i4 . '_' . ($_FILES['image_front_building']['name']);
            }
        }


        if (isset($_FILES['image_front_building_o'])) {
            // $url=base_url();

            $uploaddir = 'resource/upload/stucco/front/';
            $i4 = 'image_front_building_o';
            $image_front_building_main_o = $uploaddir . $i4 . '_' . ($_FILES['image_front_building_o']['name']);
            if (move_uploaded_file($_FILES['image_front_building_o']['tmp_name'], $image_front_building_main_o)) {

                $data['image_front_building_o'] = $i4 . '_' . ($_FILES['image_front_building_o']['name']);
            }
        }


        $image_signature = "";
        if (isset($_FILES['image_signature'])) {
            // $url=base_url();
            $uploaddir = 'resource/upload/stucco/signature/';
            $i21 = 'image_signature';
            $image_signature_main = $uploaddir . $i21 . '_' . ($_FILES['image_signature']['name']);


            if (move_uploaded_file($_FILES['image_signature']['tmp_name'], $image_signature_main)) {
                $data['image_signature'] = $i21 . '_' . ($_FILES['image_signature']['name']);

            }
        }

        //transaction start
        $this->db->trans_begin();
        $inspection_id = null;

        if ($reassigned == 1) {

            $inspection = $this->utility_model->get('ins_inspection', array('requested_id' => $requested_id));
            $data['reassigned'] = 0;

            $up_inspection = $this->utility_model->update('ins_inspection', $data, $inspection_id);
            $del_si = $this->utility_model->delete('ins_stucco_image', array('inspection_id' => $inspection_id));
            $del_ii = $this->utility_model->delete('ins_inspection_images', array('requested_id' => $requested_id));
            $inspection_id = $inspection['id'];
        } else {

            if ($this->utility_model->get('ins_inspection', array('requested_id' => $requested_id))) {
                $this->utility_model->delete('ins_inspection', array('requested_id' => $requested_id));
            };
            $insert = $this->utility_model->insert('ins_inspection', $data);
            $inspection_id = $this->db->insert_id();


        }


        if (!empty($inspection_id)) {


            if (isset($_FILES['image_int_inspection'])) {

                $checked_int_inspection = $this->input->post('checked_int_inspection');
                $comments_int_inspection = $this->input->post('comments_int_inspection');

                $data = $this->get_images('image_int_inspection', $inspection_id, 'resource/upload/stucco/interior', $checked_int_inspection, $comments_int_inspection);

            }


            if (isset($_FILES['image_front_building1'])) {

                $checked_front_building1 = $this->input->post('checked_front_building1');
                $comments_front_building1 = $this->input->post('comments_front_building1');
                $data = $this->get_images('image_front_building1', $inspection_id, 'resource/upload/stucco/front1', $checked_front_building1, $comments_front_building1);

            }

            if (isset($_FILES['image_front_building2'])) {

                $checked_front_building2 = $this->input->post('checked_front_building2');
                $comments_front_building2 = $this->input->post('comments_front_building2');
                $data = $this->get_images('image_front_building2', $inspection_id, 'resource/upload/stucco/front2', $checked_front_building2, $comments_front_building2);

            }


            if (isset($_FILES['image_front_building3'])) {

                $checked_front_building3 = $this->input->post('checked_front_building3');
                $comments_front_building3 = $this->input->post('comments_front_building3');
                $data = $this->get_images('image_front_building3', $inspection_id, 'resource/upload/stucco/front3', $checked_front_building3, $comments_front_building3);

            }


            if (isset($_FILES['image_front_building4'])) {

                $checked_front_building4 = $this->input->post('checked_front_building4');
                $comments_front_building4 = $this->input->post('comments_front_building4');
                $data = $this->get_images('image_front_building4', $inspection_id, 'resource/upload/stucco/front4', $checked_front_building4, $comments_front_building4);

            }


            if (isset($_FILES['image_back_building1'])) {

                $checked_back_building1 = $this->input->post('checked_back_building1');
                $comments_back_building1 = $this->input->post('comments_back_building1');
                $data = $this->get_images('image_back_building1', $inspection_id, 'resource/upload/stucco/back1', $checked_back_building1, $comments_back_building1);

            }


            if (isset($_FILES['image_back_building2'])) {

                $checked_back_building2 = $this->input->post('checked_back_building2');
                $comments_back_building2 = $this->input->post('comments_back_building2');
                $data = $this->get_images('image_back_building2', $inspection_id, 'resource/upload/stucco/back2', $checked_back_building2, $comments_back_building2);

            }


            if (isset($_FILES['image_back_building3'])) {

                $checked_back_building3 = $this->input->post('checked_back_building3');
                $comments_back_building3 = $this->input->post('comments_back_building3');
                $data = $this->get_images('image_back_building3', $inspection_id, 'resource/upload/stucco/back3', $checked_back_building3, $comments_back_building3);

            }


            if (isset($_FILES['image_back_building4'])) {

                $checked_back_building4 = $this->input->post('checked_back_building4');
                $comments_back_building4 = $this->input->post('comments_back_building4');
                $data = $this->get_images('image_back_building4', $inspection_id, 'resource/upload/stucco/back4', $checked_back_building4, $comments_back_building4);

            }

            if (isset($_FILES['image_left_building1'])) {

                $checked_left_building1 = $this->input->post('checked_left_building1');
                $comments_left_building1 = $this->input->post('comments_left_building1');
                $data = $this->get_images('image_left_building1', $inspection_id, 'resource/upload/stucco/left1', $checked_left_building1, $comments_left_building1);
            }


            if (isset($_FILES['image_left_building2'])) {

                $checked_left_building2 = $this->input->post('checked_left_building2');
                $comments_left_building2 = $this->input->post('comments_left_building2');

                $data = $this->get_images('image_left_building2', $inspection_id, 'resource/upload/stucco/left2', $checked_left_building2, $comments_left_building2);

            }


            if (isset($_FILES['image_left_building3'])) {

                $checked_left_building3 = $this->input->post('checked_left_building3');
                $comments_left_building3 = $this->input->post('comments_left_building3');

                $data = $this->get_images('image_left_building3', $inspection_id, 'resource/upload/stucco/left3', $checked_left_building3, $comments_left_building3);

            }


            if (isset($_FILES['image_left_building4'])) {

                $checked_left_building4 = $this->input->post('checked_left_building4');
                $comments_left_building4 = $this->input->post('comments_left_building4');

                $data = $this->get_images('image_left_building4', $inspection_id, 'resource/upload/stucco/left4', $checked_left_building4, $comments_left_building4);

            }


            if (isset($_FILES['image_right_building1'])) {

                $checked_right_building1 = $this->input->post('checked_right_building1');
                $comments_right_building1 = $this->input->post('comments_right_building1');

                $data = $this->get_images('image_right_building1', $inspection_id, 'resource/upload/stucco/right1', $checked_right_building1, $comments_right_building1);

            }


            if (isset($_FILES['image_right_building2'])) {

                $checked_right_building2 = $this->input->post('checked_right_building2');
                $comments_right_building2 = $this->input->post('comments_right_building2');

                $data = $this->get_images('image_right_building2', $inspection_id, 'resource/upload/stucco/right2', $checked_right_building2, $comments_right_building2);

            }


            if (isset($_FILES['image_right_building3'])) {

                $checked_right_building3 = $this->input->post('checked_right_building3');
                $comments_right_building3 = $this->input->post('comments_right_building3');

                $data = $this->get_images('image_right_building3', $inspection_id, 'resource/upload/stucco/right3', $checked_right_building3, $comments_right_building3);

            }

            if (isset($_FILES['image_right_building4'])) {

                $checked_right_building4 = $this->input->post('checked_right_building4');
                $comments_right_building4 = $this->input->post('comments_right_building4');

                $data = $this->get_images('image_right_building4', $inspection_id, 'resource/upload/stucco/right4', $checked_right_building4, $comments_right_building4);

            }

            if (isset($_FILES['image_front_building3_1'])) {

                $checked_front_building3_1 = $this->input->post('checked_front_building3_1');
                $comments_front_building3_1 = $this->input->post('comments_front_building3_1');

                $data = $this->get_images('image_front_building3_1', $inspection_id, 'resource/upload/stucco/front3', $checked_front_building3_1, $comments_front_building3_1);

            }


            if (isset($_FILES['image_front_building4_1'])) {

                $checked_front_building4_1 = $this->input->post('checked_front_building4_1');
                $comments_front_building4_1 = $this->input->post('comments_front_building4_1');

                $data = $this->get_images('image_front_building4_1', $inspection_id, 'resource/upload/stucco/front4', $checked_front_building4_1, $comments_front_building4_1);

            }


            if (isset($_FILES['image_right_building3_1'])) {

                $checked_right_building3_1 = $this->input->post('checked_right_building3_1');
                $comments_right_building3_1 = $this->input->post('comments_right_building3_1');

                $data = $this->get_images('image_right_building3_1', $inspection_id, 'resource/upload/stucco/right3', $checked_right_building3_1, $comments_right_building3_1);

            }


            if (isset($_FILES['image_right_building4_1'])) {

                $checked_right_building4_1 = $this->input->post('checked_right_building4_1');
                $comments_right_building4_1 = $this->input->post('comments_right_building4_1');

                $data = $this->get_images('image_right_building4_1', $inspection_id, 'resource/upload/stucco/right4', $checked_right_building4_1, $comments_right_building4_1);

            }


            if (isset($_FILES['image_back_building3_1'])) {

                $checked_back_building3_1 = $this->input->post('checked_back_building3_1');
                $comments_back_building3_1 = $this->input->post('comments_back_building3_1');

                $data = $this->get_images('image_back_building3_1', $inspection_id, 'resource/upload/stucco/back3', $checked_back_building3_1, $comments_back_building3_1);

            }

            if (isset($_FILES['image_back_building4_1'])) {

                $checked_back_building4_1 = $this->input->post('checked_back_building4_1');
                $comments_back_building4_1 = $this->input->post('comments_back_building4_1');

                $data = $this->get_images('image_back_building4_1', $inspection_id, 'resource/upload/stucco/back4', $checked_back_building4_1, $comments_back_building4_1);

            }


            if (isset($_FILES['image_left_building3_1'])) {

                $checked_left_building3_1 = $this->input->post('checked_left_building3_1');
                $comments_left_building3_1 = $this->input->post('comments_left_building3_1');

                $data = $this->get_images('image_left_building3_1', $inspection_id, 'resource/upload/stucco/left3', $checked_left_building3_1, $comments_left_building3_1);

            }

            if (isset($_FILES['image_left_building4_1'])) {

                $checked_left_building4_1 = $this->input->post('checked_left_building4_1');
                $comments_left_building4_1 = $this->input->post('comments_left_building4_1');

                $data = $this->get_images('image_left_building4_1', $inspection_id, 'resource/upload/stucco/left4', $checked_left_building4_1, $comments_left_building4_1);
            }


            $text_front_building2 = $this->input->post('text_front_building2');
            $text_front_building4 = $this->input->post('text_front_building4');
            $text_right_building2 = $this->input->post('text_right_building2');
            $text_right_building4 = $this->input->post('text_right_building4');
            $text_back_building2 = $this->input->post('text_back_building2');
            $text_back_building4 = $this->input->post('text_back_building4');
            $text_left_building2 = $this->input->post('text_left_building2');
            $text_left_building4 = $this->input->post('text_left_building4');
            $check_front_building1 = $this->input->post('check_front_building1');
            $check_front_building2 = $this->input->post('check_front_building2');
            $check_front_building3 = $this->input->post('check_front_building3');
            $check_front_building3_1 = $this->input->post('check_front_building3_1');
            $check_front_building4 = $this->input->post('check_front_building4');
            $check_front_building4_1 = $this->input->post('check_front_building4_1');
            $check_back_building1 = $this->input->post('check_back_building1');
            $check_back_building2 = $this->input->post('check_back_building2');
            $check_back_building3 = $this->input->post('check_back_building3');
            $check_back_building3_1 = $this->input->post('check_back_building3_1');
            $check_back_building4 = $this->input->post('check_back_building4');
            $check_back_building4_1 = $this->input->post('check_back_building4_1');
            $check_left_building1 = $this->input->post('check_left_building1');
            $check_left_building2 = $this->input->post('check_left_building2');
            $check_left_building3 = $this->input->post('check_left_building3');
            $check_left_building3_1 = $this->input->post('check_left_building3_1');
            $check_left_building4 = $this->input->post('check_left_building4');
            $check_left_building4_1 = $this->input->post('check_left_building4_1');
            $check_right_building1 = $this->input->post('check_right_building1');
            $check_right_building2 = $this->input->post('check_right_building2');
            $check_right_building3 = $this->input->post('check_right_building3');
            $check_right_building3_1 = $this->input->post('check_right_building3_1');
            $check_right_building4 = $this->input->post('check_right_building4');
            $check_right_building4_1 = $this->input->post('check_right_building4_1');
            $stucco_exterior = $this->input->post('stucco_exterior');

            $data1 = array(
                'user_id' => $this->input->post('user_id'),
                'type' => $this->input->post('type'),
                'requested_id' => $this->input->post('requested_id'),
                'stucco_exterior' => $stucco_exterior ? $stucco_exterior : "",
                'text_front_building2' => $text_front_building2 ? $text_front_building2 : "",
                'text_front_building4' => $text_front_building4 ? $text_front_building4 : "",
                'text_right_building2' => $text_right_building2 ? $text_right_building2 : "",
                'text_right_building4' => $text_right_building4 ? $text_right_building4 : "",
                'text_back_building2' => $text_back_building2 ? $text_back_building2 : "",
                'text_back_building4' => $text_back_building4 ? $text_back_building4 : "",
                'text_left_building2' => $text_left_building2 ? $text_left_building2 : "",
                'text_left_building4' => $text_left_building4 ? $text_left_building4 : "",
                'check_front_building1' => $check_front_building1 ? $check_front_building1 : 0,
                'check_front_building2' => $check_front_building2 ? $check_front_building2 : 0,
                'check_front_building3' => $check_front_building3 ? $check_front_building3 : 0,
                'check_front_building3_1' => $check_front_building3_1 ? $check_front_building3_1 : 0,
                'check_front_building4' => $check_front_building4 ? $check_front_building4 : 0,
                'check_front_building4_1' => $check_front_building4_1 ? $check_front_building4_1 : 0,
                'check_back_building1' => $check_back_building1 ? $check_back_building1 : 0,
                'check_back_building2' => $check_back_building2 ? $check_back_building2 : 0,
                'check_back_building3' => $check_back_building3 ? $check_back_building3 : 0,
                'check_back_building3_1' => $check_back_building3_1 ? $check_back_building3_1 : 0,
                'check_back_building4' => $check_back_building4 ? $check_back_building4 : 0,
                'check_back_building4_1' => $check_back_building4_1 ? $check_back_building4_1 : 0,
                'check_left_building1' => $check_left_building1 ? $check_left_building1 : 0,
                'check_left_building2' => $check_left_building2 ? $check_left_building2 : 0,
                'check_left_building3' => $check_left_building3 ? $check_left_building3 : 0,
                'check_left_building3_1' => $check_left_building3_1 ? $check_left_building3_1 : 0,
                'check_left_building4' => $check_left_building4 ? $check_left_building4 : 0,
                'check_left_building4_1' => $check_left_building4_1 ? $check_left_building4_1 : 0,
                'check_right_building1' => $check_right_building1 ? $check_right_building1 : 0,
                'check_right_building2' => $check_right_building2 ? $check_right_building2 : 0,
                'check_right_building3' => $check_right_building3 ? $check_right_building3 : 0,
                'check_right_building3_1' => $check_right_building3_1 ? $check_right_building3_1 : 0,
                'check_right_building4' => $check_right_building4 ? $check_right_building4 : 0,
                'check_right_building4_1' => $check_right_building4_1 ? $check_right_building4_1 : 0,
            );

            $insert_inspection_images = false;
            $update_requested_inspection = false;

            $insert_inspection_images = $this->utility_model->insert('ins_inspection_images', $data1);

            $update_requested_inspection = $this->utility_model->update('ins_inspection_requested', $data_request_table, array('id' => $this->input->post('requested_id')));

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();

                $response['status'] = $this->status[1];

                print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));
            } else {
                if ($insert_inspection_images && $update_requested_inspection) {
                    $insert_id = $this->db->insert_id();

                    $result_data = $this->utility_model->get('ins_inspection', array('id' => $insert_id));

                    $result_data['id'] = !empty($result_data['id']) ? $result_data['id'] : "";

                    $url = base_url();
                    $fileurl = 'resource/upload/images/';
                    $result_data['img_url'] = $url . '' . $fileurl;

                    $mail_subject = "Pulte Stucco Inspection Form";
                    $mail_body = " Pulte Stucco Inspection Form Details \n"
                        . "\n"
                        . "\n"
                        . " Requested At: " . $result_data['date_requested'] . "\n"
                        . " First Name: " . $first_name . "\n"
                        . " job_number: " . $result_data['job_number'] . "\n"
                        . " Email Address: " . $email . "\n"
                        . " Phone Number: " . $cell_phone . "\n"
                        . " Address: " . $address . "\n"
                        . " community: " . $result_data['community_name'] . "\n"
                        . " City: " . $city . "\n"
                        . " State: " . $state . "\n"
                        . " Zip: " . $zip . "\n"
                        . " GPS Location: " . $result_data['gps_location'] . "\n"
                        . " Comments: " . $result_data['overall_comments'] . "\n"
                        . " Close Escrow Date: " . $result_data['close_escrow_date'] . "\n"
                        . " Start Date: " . $result_data['start_date'] . "\n"
                        . " End Date: " . $result_data['end_date'] . "\n"
                        . " Access Instructions: " . $result_data['ins_initials'] . "\n"
                        . "\n"
                        . " Please login admin panel and check this user. \n"
                        . " " . base_url() . " \n\n"
                        . " Regards."
                        . "\n";

                    $sender = $this->utility_model->get_list('ins_admin', array('kind' => 1, 'allow_email' => 1));
                    $this->send_mail($mail_subject, $mail_body, $sender, false);


                    //transaction end

                    $this->db->trans_commit();

                    $response['status'] = $this->status[0];
                    $response['response'] = $result_data;

                    print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

                } else {

                    $response['status'] = $this->status[1];

                    print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

                }

            }


        } else {
            $response['status'] = "fails";
            print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));
        }


    }

    function get_reassgined_ins_data()
    {

        $requested_id = $this->input->get_post('id');
        $inspection_id = $this->input->get_post('edit_inspection_id');

        $response = array();
        $response['data'] = array();


        $inspection = $this->utility_model->get('ins_inspection', array('requested_id' => $requested_id));
        $inspection_images = $this->utility_model->get('ins_inspection_images', array('requested_id' => $requested_id));
        $stucco_images = $this->utility_model->getImageList('ins_stucco_image', array('inspection_id' => $inspection_id));
        $stucco_images_count = $this->utility_model->get_count('ins_stucco_image', array('inspection_id' => $inspection_id));

        $f_image_url = base_url() . 'resource/upload/stucco/front/';
        $l_image_url = base_url() . 'resource/upload/stucco/left/';
        $b_image_url = base_url() . 'resource/upload/stucco/back/';
        $r_image_url = base_url() . 'resource/upload/stucco/right/';
        $s_image_url = base_url() . 'resource/upload/stucco/signature/';


        if ($inspection && $inspection_images && $stucco_images) {


            // data of ins_inspection table;
            $data = array(
                'user_id' => $inspection['user_id'],
                'requested_id' => $inspection['requested_id'],
                'type' => $inspection['type'],
                'job_number' => $inspection['job_number'],
                'community_name' => $inspection['community_name'],
                'address' => $inspection['address'],
                'community' => $inspection['community'],
                'lot' => $inspection['lot'],
                'date_requested' => $inspection['date_requested'],
                'start_date' => $inspection['start_date'],
                'end_date' => $inspection['end_date'],
                'region' => $inspection['region'],
                'close_escrow_date' => $inspection['close_escrow_date'],
                'created_at' => $inspection['created_at'],
                'access_instructions' => $inspection['access_instructions'],
                'gps_location' => $inspection['gps_location'],
                'latitude' => $inspection['latitude'],
                'longitude' => $inspection['longitude'],
                'accuracy' => $inspection['accuracy'],
                'no_of_stories' => $inspection['no_of_stories'],
                'home_type' => $inspection['home_type'],
                'int_inspection' => $inspection['int_inspection'],
                'overall_comments' => $inspection['overall_comments'],
                'emp_per_inspection' => $inspection['emp_per_inspection'],

                // important parameter
                'edit_inspection_id' => $inspection['id'],
                'reassigned' => $inspection['reassigned'],
                //end
                'image_front_building' => $inspection['image_front_building'] ? $f_image_url . $inspection['image_front_building'] : null,
                'image_front_building_o' => $inspection['image_front_building_o'] ? $f_image_url . $inspection['image_front_building_o'] : null,
                'image_right_building' => $inspection['image_right_building'] ? $r_image_url . $inspection['image_right_building'] : null,
                'image_right_building_o' => $inspection['image_right_building_o'] ? $r_image_url . $inspection['image_right_building_o'] : null,
                'image_left_building' => $inspection['image_left_building'] ? $l_image_url . $inspection['image_left_building'] : null,
                'image_left_building_o' => $inspection['image_left_building_o'] ? $l_image_url . $inspection['image_left_building_o'] : null,
                'image_back_building' => $inspection['image_back_building'] ? $b_image_url . $inspection['image_back_building'] : null,
                'image_back_building_o' => $inspection['image_back_building_o'] ? $b_image_url . $inspection['image_back_building_o'] : null,
                'image_signature' => $inspection['image_signature'] ? base_url() . $s_image_url . $inspection['image_signature'] : null,
            );

            //data of ins_stucco_image table;
            $index[] = 0;
            for ($i = 0; $i < $stucco_images_count; $i++) {
                if ($stucco_images[$i]['stucco_label']) {
                    $label = $stucco_images[$i]['stucco_label'];
                    if (!$index[$label]) {
                        $index[$label] = 0;
                    }
                    $data[$label][$index[$label]] = base_url() . $stucco_images[$i]['upload_link'] . $stucco_images[$i]['stucco_value_src'];
                    $index[$label]++;
                }
                if ($stucco_images[$i]['stucco_label_o']) {
                    $label = $stucco_images[$i]['stucco_label_o'];
                    if (!$index[$label]) {
                        $index[$label] = 0;
                    }
                    $data[$label][$index[$label]] = base_url() . $stucco_images[$i]['upload_link'] . $stucco_images[$i]['stucco_value_src_o'];
                    $index[$label]++;
                }

                $label = $stucco_images[$i]['stucco_label'] . '_check';
                if (!$index[$label]) {
                    $index[$label] = 0;
                }
                $data[$label][$index[$label]] = $stucco_images[$i]['stucco_check'];
                $index[$label]++;


                $label = $stucco_images[$i]['stucco_label'] . '_comments';
                if (!$index[$label]) {
                    $index[$label] = 0;
                }
                $data[$label][$index[$label]] = $stucco_images[$i]['stucco_comments'];
                $index[$label]++;

            }


            $data['check_front_building1'] = $inspection_images['check_front_building1'];
            $data['check_front_building2'] = $inspection_images['check_front_building2'];
            $data['check_front_building3'] = $inspection_images['check_front_building3'];
            $data['check_front_building4'] = $inspection_images['check_front_building4'];
            $data['check_back_building1'] = $inspection_images['check_back_building1'];
            $data['check_back_building2'] = $inspection_images['check_back_building2'];
            $data['check_back_building3'] = $inspection_images['check_back_building3'];
            $data['check_back_building4'] = $inspection_images['check_back_building4'];
            $data['check_right_building1'] = $inspection_images['check_right_building1'];
            $data['check_right_building2'] = $inspection_images['check_right_building2'];
            $data['check_right_building3'] = $inspection_images['check_right_building3'];
            $data['check_right_building4'] = $inspection_images['check_right_building4'];
            $data['check_left_building1'] = $inspection_images['check_left_building1'];
            $data['check_left_building2'] = $inspection_images['check_left_building2'];
            $data['check_left_building3'] = $inspection_images['check_left_building3'];
            $data['check_left_building4'] = $inspection_images['check_left_building4'];

            $data['check_front_building3_1'] = $inspection_images['check_front_building3_1'];
            $data['check_front_building4_1'] = $inspection_images['check_front_building4_1'];
            $data['check_back_building3_1'] = $inspection_images['check_back_building3_1'];
            $data['check_back_building4_1'] = $inspection_images['check_back_building4_1'];
            $data['check_right_building3_1'] = $inspection_images['check_right_building3_1'];
            $data['check_right_building4_1'] = $inspection_images['check_right_building4_1'];
            $data['check_left_building3_1'] = $inspection_images['check_left_building3_1'];
            $data['check_left_building4_1'] = $inspection_images['check_left_building4_1'];


            if ($inspection_images['stucco_exterior']) {
                $data['stucco_exterior'] = $inspection_images['stucco_exterior'];
            }

            $data['text_front_building2'] = $inspection_images['text_front_building2'];
            $data['text_front_building4'] = $inspection_images['text_front_building4'];
            $data['text_right_building2'] = $inspection_images['text_right_building2'];
            $data['text_right_building4'] = $inspection_images['text_right_building4'];
            $data['text_left_building2'] = $inspection_images['text_left_building2'];
            $data['text_left_building4'] = $inspection_images['text_left_building4'];
            $data['text_back_building2'] = $inspection_images['text_back_building2'];
            $data['text_back_building4'] = $inspection_images['text_back_building4'];

            $response['data'] = $data;
            $response['status'] = $this->status[0];


            print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));


        } else {
            $response['status'] = $this->status[1];
            print_r(json_encode($response, JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

        }
    }

    function get_images($image, $inspection_id, $folder_name, $checked, $comments)
    {

        $countfiles = count($_FILES[$image]['name']);


        $dataImage = array();

        for ($i = 0; $i < $countfiles; $i++) {


            $uploaddir = $folder_name . '/';
            $checked1 = $checked[$i];
            $comments1 = $comments[$i];
            $i1 = $image . '_' . $i;

            $imageName = time() . '_' . $i1 . '_' . ($_FILES[$image]['name'][$i]);
            $imageName_o = time() . '_' . $i1 . '_' . ($_FILES[$image . '_o']['name'][$i]);

            $image_building = $uploaddir . $imageName;
            $image_building_o = $uploaddir . $imageName_o;

            $move_result = move_uploaded_file($_FILES[$image]['tmp_name'][$i], $image_building);

            $move_result_o = move_uploaded_file($_FILES[$image . '_o']['tmp_name'][$i], $image_building_o);

            $dataArray = array();
            if ($move_result && $move_result_o) {

                $image_building1 = $imageName;
                $image_building1_o = $imageName_o;


                $dataArray = array(
                    'inspection_id' => $inspection_id,
                    'stucco_label' => $image,
                    'stucco_label_o' => $image . '_o',
                    'upload_link' => $uploaddir,
                    'stucco_check' => $checked1,
                    'stucco_comments' => $comments1,
                    'stucco_value_src' => $image_building1,
                    'stucco_value_src_o' => $image_building1_o
                );

            }


            $dataImage[] = $dataArray;
            //array_push($dataImage, $dataArray);


        }


        $insert = false;

        $insert = $this->utility_model->insert_Image('ins_stucco_image', $dataImage);


    }

}
