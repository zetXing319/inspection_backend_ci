<?php

class Utility_model extends CI_Model    {

    private $encrypt_key = "_inspection_e3_sciences_";
    
    public function __construct() {
        parent::__construct();
    }
    
    public function start() {
        $this->db->trans_start();
    }
    public function complete() {
        $this->db->trans_complete();
    }
    
    public function begin() {
        $this->db->trans_begin();
    }
    public function commit() {
        $this->db->trans_commit();
    }
    public function rollback() {
        $this->db->trans_rollback();
    }
    
    public function escape($str) {
        return $this->db->escape($str);
    }
    
    public function insert($table, $data){
        return $this->db->insert($table, $data);
    }

    public function checkduplicated($table, $info)
    {
        $this->db->where($info);
        $this->db->from($table);
        $query = $this->db->get();
        return $query->num_rows();
    }


    

    public function updateinfo($table, $info)
    {
        return $this->db->update($table,$info);
    }

    public function new_id() {
        return $this->db->insert_id();
    }
    
    public function update($table, $data, $id){
        return $this->db->update($table, $data, $id);
    }
    function updateRecords($table, $data, $where_condition)
    {
        $this->db->where($where_condition);

        $query = $this->db->update($table, $data); 

        return $query;
    }
    public function get($table, $cond){
        $query = $this->db->get_where($table, $cond);
        return $query->row_array();
    }
    public function     get__by_sql($sql){
        $query = $this->db->query($sql);
        return $query->row_array();
    }
    public function get_list($table, $cond=''){
        $query=null;
        if (is_array($cond)){
            $query = $this->db->get_where($table, $cond);
        } else {
            $query = $this->db->get($table);
        }
        return $query->result_array();
    }

      public function getImageList($table, $cond){
             $this->db->select('*');
            $this->db->where($cond);
            $query = $this->db->get($table);
       
        return $query->result_array();
    }
    public function get_count($table, $cond){
        $query = $this->db->get_where($table, $cond);
        return $query->num_rows();
    }
    public function get_count__by_sql($sql){
        $query = $this->db->query($sql);
        return $query->num_rows();
    }
    
    public function get_list__by_order($table, $cond, $order){
        foreach ($order as $row) {
            $this->db->order_by($row['name'], $row['order']);
        }
        
        $query = $this->db->get_where($table, $cond);
        return $query->result_array();
    }
    
    public function get_list__by_sql($sql){
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function delete($table, $cond){
       return $this->db->delete($table , $cond);
    }

    public function deletealldata($table){
        return $this->db->empty_table($table);
     }
 
    public function get_field__by_sql($sql, $field){
        $query = $this->db->query($sql);
        $row = $query->row_array();
        if ($row){
            return isset($row[$field]) ? $row[$field] : '';
        }
        
        return '';
    }
    
    public function encode($key) {
        return base64_encode($key . $this->encrypt_key);
    }
    
    public function decode($key) {
        $key = base64_decode($key);
        $enc_position = strpos($key, $this->encrypt_key);
        $key = substr($key, 0, $enc_position-2);
        
        return $key;
    }
    
    public function escape_filename($filename) {
        $filename = str_replace("/", "-", $filename);
        return $filename;
    }
     function community_autocomplete($community){
        $this->db->select('*');
        $this->db->like('community_name', $community , 'both');
       //  $this->db->group_by('community_id'); 
        $this->db->order_by('community_name', 'ASC');
        // $this->db->limit(10);
        return $this->db->get('ins_community')->result_array();       
    }

    function fetch_data($query)
 {

    $offercountsql ="select * from ins_community WHERE community_id = '".$query."' limit 1";    
    $offercountrun = $this->db->query($offercountsql);
    $result= $offercountrun->result_array();   

   $offercountsql_build ="select * from ins_building WHERE job_number like '".$result[0]['community_id']."%' limit 1";    
   $offercountrun_build = $this->db->query($offercountsql_build);
   $result_build = $offercountrun_build->result_array(); 
          return $result12=array(
                         "community_name"=>$result[0]['community_id'],
                         "community_id"=>$result[0]['community_name'],
                         "city"=>$result[0]['city'],
                         "region"=>$result[0]['region'],
                         "state"=>$result[0]['state'],
                         "zip"=>$result[0]['zip'],
                       "job_number"=>$result_build[0]['job_number']
                             ); 
         
 }


     function fetchDataAddressByjobnumber($query,$job_number)
 {
    $extra='';
    if(!empty($query)){
        $extra="address like '%".$query."%' AND ";
    }

  //echo $offercountsql ="select * from ins_building inner join  ins_building_unit ON ins_building.job_number = ins_building_unit.job_number  WHERE  ".$extra." ins_building_unit.job_number   like '" . $job_number ."%' limit 20;";
   // $offercountsql ="select * from ins_building_unit WHERE ".$extra." job_number  like '" . $job_number ."%' limit 20;";
    $offercountsql ="select address from ins_building WHERE ".$extra." job_number  like '" . $job_number ."%' UNION select address from ins_building   where  ".$extra." job_number  like '" . $job_number ."%'";
    $offercountrun = $this->db->query($offercountsql);
   return $result = $offercountrun->result_array();
    
 }
    
    
    public function has_permission($permission, $kind=1) {
        switch ($kind) {
            case 1:
                if ($permission==1) {
                    return true;
                }
                break;
                
            case 2:
                if ($permission==1) {
                    return true;
                }
                if ($permission==2) {
                    return true;
                }
                break;
                
            case 3:
                if ($permission==1) {
                    return true;
                }
                if ($permission==2) {
                    return true;
                }
                if ($permission==3) {
                    return true;
                }
                break;
                
            case 4:
                if ($permission==1) {
                    return true;
                }
                if ($permission==2) {
                    return true;
                }
                if ($permission==3) {
                    return true;
                }
                if ($permission==4) {
                    return true;
                }
                break;
            case 5:
                if ($permission==1) {
                    return true;
                }
                if ($permission==2) {
                    return true;
                }
                if ($permission==3) {
                    return true;
                }
                if ($permission==4) {
                    return true;
                }
                if ($permission==5) {
                    return true;
                }
                break;
                
            case 0:
                if ($permission==0) {
                    return true;
                }
                break;
        }
        
        return false;
    }

}
