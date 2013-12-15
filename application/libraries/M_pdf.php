<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'/third_party/mpdf/mpdf.php';

class M_pdf {

    public $pdf;

    public function initialize($page_size="A4-L", $orientation="L"){
        $this->pdf = new mPDF("UTF-8", $page_size, 0, '', 10, 10, 10, 10, 5, 5, $orientation);
    }

    
    public function setSize($page_size="LETTER", $orientation="P"){
        //($mode = '', $format = 'A4', $default_font_size = 0, $default_font = '', 
        //                                          $mgl = 15, $mgr = 15, $mgt = 16, $mgb = 16, 
        //                                                              $mgh = 9, $mgf = 9, $orientation = 'P')
        $this->pdf = new mPDF("UTF-8", $page_size, 0, '', 10, 10, 10, 10, 5, 5, $orientation);
    }
}
