<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Jurisdiction extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        $this->load->model('utility_model');
        $this->load->model('datatable_model');
    }
    
    public function index() {
        if (!$this->session->userdata('user_id')) {
            redirect(base_url() . "user/login.html");
            exit(1);
        }
        
        redirect(base_url() . "jurisdiction/home.html");
    }


    public function home() {
        if (!$this->session->userdata('user_id') || $this->session->userdata('permission')!='1') {
            redirect(base_url() . "user/login.html");
            exit(1);
        }

        $page_data['page_name'] = 'jurisdiction';
        $this->load->view('jurisdiction_list', $page_data);
    }

    public function load(){
        $cols = array( "a.name", "a.contact", "a.address", "a.city", "a.state", "a.zip", "a.phone", "a.email");
        $table = "ins_jurisdiction a";

        $result = array();

        $amount = 10;
        $start = 0;
        $col = 0;

        $dir = "asc";

        $sStart = $this->input->get_post('start');
        $sAmount = $this->input->get_post('length');
        $sdir = "";

        $sCol = $this->input->get_post("order");
        if ($sCol!==false && is_array($sCol)) {
            foreach ($sCol as $row) {
                foreach ($row as $key => $value) {
                    if ($key=='column')
                        $sCol = $value;
                    if ($key=='dir')
                        $sdir = $value;
                }
            }
        }

        $searchTerm = "";
        $search = $this->input->get_post("search");
        if ($search!==false && is_array($search)) {
            foreach ($search as $key => $value) {
                if ($key=='value') {
                    $searchTerm = $value;
                }
            }
        }

        if ($sStart!==false && strlen($sStart)>0){
            $start = intval($sStart);
            if ($start<0){
                $start=0;
            }
        }

        if ($sAmount!==false && strlen($sAmount)>0){
            $amount = intval($sAmount);
            if ($amount<10 || $amount>100){
                $amount = 10;
            }
        }

        if ($sCol!==false && strlen($sCol)>0){
            $col = intval($sCol);
            if ($col<0 || $col>7){
                $col=0;
            }
        }

        if ($sdir && strlen($sdir)>0){
            if ($sdir!="asc"){
                $dir="desc";
            }
        }

        $colName = $cols[$col];
        $total = 0;
        $totalAfterFilter = 0;

        $sql = " select count(*) from " . $table ;
        $total = $this->datatable_model->get_count($sql);
        $totalAfterFilter = $total;

        $sql = " select  a.*, '' as additional from " . $table . "  ";
        $searchSQL = "";
        $globalSearch = " ( "
            . " a.name like '%" . $searchTerm . "%' or "
            . " a.contact like '%" . $searchTerm . "%' or "
            . " a.address like '%" . $searchTerm . "%' or  "
            . " a.city like '%" . $searchTerm . "%' or  "
            . " a.state like '%" . $searchTerm . "%' or  "
            . " a.zip like '%" . $searchTerm . "%' or  "
            . " a.phone like '%" . $searchTerm . "%' or  "
            . " a.email like '%" . $searchTerm . "%'  "
            . " ) ";

        if ($searchTerm && strlen($searchTerm)>0){
            $searchSQL .= " where " . $globalSearch;
        }

        $sql .= $searchSQL;
        $sql .= " order by " . $colName . " " . $dir . " ";
        $sql .= " limit " . $start . ", " . $amount . " ";
        $data = $this->datatable_model->get_content($sql);

        $sql = " select count(*) from " . $table ;
        if (strlen($searchSQL)>0){
            $sql .= $searchSQL;
            $totalAfterFilter = $this->datatable_model->get_count($sql);
        }

        if (!$this->session->userdata('user_id') || $this->session->userdata('permission')!='1') {

        } else {
            $result["recordsTotal"] = $total;
            $result["recordsFiltered"] = $totalAfterFilter;
            
            $new_data = array();
            
            foreach ($data as $row) {
                $row['fees'] = "";
                
//                $sql = " SELECT "
//                        . " c.code AS inspection_type, c.name AS inspection_name, "
//                        . " ( CASE WHEN f.inspection_fee IS NULL THEN 0 ELSE f.inspection_fee END) AS inspection_fee, "
//                        . " ( CASE WHEN f.re_inspection_fee IS NULL THEN 0 ELSE f.re_inspection_fee END) AS re_inspection_fee "
//                        . " FROM ins_code c "
//                        . " LEFT JOIN ins_jurisdiction_fee f ON f.inspection_type=c.code and f.jurisdiction_id='" . $row['id'] . "' "
//                        . " WHERE c.kind='ins' AND c.code<>0 "
//                        . " ORDER BY c.code ";
//                
//                $fees = $this->utility_model->get_list__by_sql($sql);
//                foreach ($fees as $fee) {
//                    if ($fee['inspection_fee']>0 || $fee['re_inspection_fee']>0) {
//                        $row['fees'] .= $row['fees']!="" ? "<br>" : "";
//                        
//                        $row['fees'] .= $fee['inspection_name'] . " ";
//                        if ($fee['inspection_fee']>0) {
//                            $row['fees'] .= " Inspection : " . number_format($fee['inspection_fee'], 2);
//                        }
//                        
//                        if ($fee['re_inspection_fee']>0) {
//                            $row['fees'] .= $fee['inspection_fee']>0 ? ", " : "";
//                            $row['fees'] .= " Re-Inspection : " . number_format($fee['re_inspection_fee'], 2);
//                        }
//                    }
//                }
                
                array_push($new_data, $row);
            }
            
            $result["data"] = $new_data;
        }

        print_r(json_encode($result));
    }

    
    public function edit() {
        if (!$this->session->userdata('user_id') || $this->session->userdata('permission')!='1') {
            redirect(base_url() . "user/login.html");
            exit(1);
        }

        $jurisdiction_id = $this->input->get_post('jurisdiction_id');
        $kind = $this->input->get_post('kind');
        

        $sql = "";
        $jurisdiction = null;

        $page_data['page_name'] = 'jurisdiction';
        if ($kind=='add') {
            $page_data['page_title'] = "Add Jurisdiction";
            $jurisdiction = array('name'=>'', 'contact'=>'', 'address'=>'', 'city'=>'', 'state'=>'' , 'zip'=>'', 'phone'=>'', 'email'=>'');
            
//            $sql = " SELECT "
//                    . " c.code AS inspection_type, c.name AS inspection_name, "
//                    . " 0.00 AS inspection_fee, "
//                    . " 0.00 AS re_inspection_fee "
//                    . " FROM ins_code c "
//                    . " WHERE c.kind='ins' AND c.code<>0 "
//                    . " ORDER BY c.code ";
        } else {
            $page_data['page_title'] = "Update Jurisdiction";
            $jurisdiction = $this->utility_model->get('ins_jurisdiction', array('id'=>$jurisdiction_id) );

//            $sql = " SELECT "
//                    . " c.code AS inspection_type, c.name AS inspection_name, "
//                    . " ( CASE WHEN f.inspection_fee IS NULL THEN 0 ELSE f.inspection_fee END) AS inspection_fee, "
//                    . " ( CASE WHEN f.re_inspection_fee IS NULL THEN 0 ELSE f.re_inspection_fee END) AS re_inspection_fee "
//                    . " FROM ins_code c "
//                    . " LEFT JOIN ins_jurisdiction_fee f ON f.inspection_type=c.code and f.jurisdiction_id='$jurisdiction_id' "
//                    . " WHERE c.kind='ins' AND c.code<>0 "
//                    . " ORDER BY c.code ";
        }

        $jurisdiction['fee'] = '';
        
        $page_data['jurisdiction_id'] = $jurisdiction_id;
        $page_data['kind'] = $kind;
        $page_data['jurisdiction'] = $jurisdiction;

        $this->load->view('jurisdiction_edit', $page_data);
    }

    public function update() {
        $res = array('err_code'=>1, 'err_msg'=>'Failed!');
        if ($this->session->userdata('user_id')) {

            $jurisdiction_id = $this->input->get_post('jurisdiction_id');
            $kind = $this->input->get_post('kind');

            $permission = $this->session->userdata('permission');

            if ($kind=='add' && $jurisdiction_id===false) {
                $jurisdiction_id = "";
            }

            if ($jurisdiction_id!==false && $kind!==false){
                if ($this->session->userdata('permission')==1) {
                    $t = mdate('%Y%m%d%H%i%s', time());
                    $data = array('updated_at'=>$t);

                    $ret = 0;
                    if ($kind=='add' || $kind=='edit') {
                        $name = $this->input->get_post('name');
                        $contact = $this->input->get_post('contact');
                        $address = $this->input->get_post('address');
                        $city = $this->input->get_post('city');
                        $state = $this->input->get_post('state');
                        $zip = $this->input->get_post('zip');
                        $phone = $this->input->get_post('phone');
                        $email = $this->input->get_post('email');
                        $fee = $this->input->get_post('fee');
                        
                        $user_id = $this->session->userdata('user_id');

                        if ($name!==false && $email!==false) {
                            $ret = 1;

                            $data['user_id'] = $user_id;
                            $data['name'] = $name;
                            $data['contact'] = '';
                            $data['address'] = '';
                            $data['city'] = '';
                            $data['state'] = '';
                            $data['zip'] = '';
                            $data['phone'] = '';
                            $data['email'] = $email;
                        }
                    }

                    if ($ret==1) {
                        if ($kind=='add') {
                            $res['err_msg'] = "Failed to Add!";
                        } else {
                            $res['err_msg'] = "Failed to Update!";
                        }

                        $ret = true;
//                        $ttt = $this->utility_model->get('ins_jurisdiction', array('jurisdiction_id'=>$id));
//                        if ($ttt) {
//                            if ($ttt['id']!=$jurisdiction_id) {
//                                $ret = false;
//                            }
//                        }

                        if ($ret) {
                            if ($kind=='add') {
                                $data['created_at'] = $t;

                                if ($this->utility_model->insert('ins_jurisdiction', $data)) {
                                    $jurisdiction_id = $this->utility_model->new_id();
                                    
                                    $res['err_code'] = 0;
                                    $res['err_msg'] = "Successfully Added!";
                                }

                            } else {
                                if ($this->utility_model->update('ins_jurisdiction', $data, array('id'=>$jurisdiction_id))) {
                                    $res['err_code'] = 0;
                                    $res['err_msg'] = "Successfully Updated!";
                                }
                            }
                            
                            if ($res['err_code']===0) {
//                                $this->utility_model->delete('ins_jurisdiction_fee', array('jurisdiction_id'=>$jurisdiction_id));
//                                $fees = json_decode($fee, true);
//                                if ($fees!==false) {
//                                    foreach ($fees as $row) {
//                                        $inspection_fee = $row['inspection_fee'];
//                                        $re_inspection_fee = $row['re_inspection_fee'];
//                                        
//                                        if ($inspection_fee=="") {
//                                            $inspection_fee = "0.00";
//                                        } else {
//                                            $inspection_fee = number_format(doubleval($inspection_fee), 2, ".", "");
//                                        }
//                                        
//                                        if ($re_inspection_fee=="") {
//                                            $re_inspection_fee = "0.00";
//                                        } else {
//                                            $re_inspection_fee = number_format(doubleval($re_inspection_fee), 2, ".", "");
//                                        }
//                                        
//                                        $this->utility_model->insert('ins_jurisdiction_fee', array('jurisdiction_id'=>$jurisdiction_id, 'inspection_type'=>$row['type'], 'inspection_fee'=>$inspection_fee, 're_inspection_fee'=>$re_inspection_fee, 'created_at'=>$t));
//                                    }
//                                }
                            }
                        } else {
                            $res['err_msg'] = "Already Exist Jurisdiction!";
                        }
                    } else {
                        if ($res==0) {
                            $res['err_msg'] = "Missing Parameters";
                        }
                    }
                } else {
                    $res['err_msg'] = "You haven't permission";
                }
            }
        }

        print_r(json_encode($res));
    }

    public function delete() {
        $res = array('err_code'=>1);
        if ($this->session->userdata('user_id') && $this->session->userdata('permission')==1) {
            $jurisdiction_id = $this->input->get_post('jurisdiction_id');

            if ($jurisdiction_id!==false){
                if ($this->utility_model->delete('ins_jurisdiction', array('id'=>$jurisdiction_id))) {
                    $res['err_code'] = 0;
                }
            } else {
            }
        }

        print_r(json_encode($res));
    }
    

    
}
