<?php
require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class SequenceFileParser extends GameParser {

    static protected $col_names = array("ORF", "AASEQ");
    protected $error_message;

    public function get_error() {
        return $this->error_message;
    }

    protected function get_column_names() {
        return self::$col_names;
    }

    protected function validate_header($header, $samples) {
        $col_names = $this->get_column_names();
        $num_fixed_cols = sizeof($col_names);
        if ($num_fixed_cols > sizeof($header)) {
            $this->error_message = "Wrong number of columns";
            return FALSE;
        }
        // Validate fixed name columns
        for ($i = 0; $i < sizeof($header); $i++) {
            if ($header[$i] != $col_names[$i]) {
                $this->error_message = "Wrong header: Expecting " . $col_names[$i];
                return FALSE;
            }
        }
        $this->error_message = "";
        return TRUE;
    }
    
    function parse_header($filehandle) {
        $this->row = 0;
        while (($data = fgetcsv($filehandle, 1000, "\t")) !== FALSE) {
            $this->row++;
            if ((sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                return $this->validate_header($data, NULL);
            }            
        }
        $this->error_message = "Error parsing Genes file: no header found";
        return FALSE;
    }
    
    public function parse($filename) {
        $seqs = array();
        $handle = fopen($filename, "rb");
        $header_read = FALSE;
        $this->row = 0;
        $col_num = sizeof(self::$col_names);
        while (($data = fgetcsv($handle, 20000, "\t")) !== FALSE) {
            $this->row++;
            if (!$header_read && (sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                $header_read = TRUE;
                $this->validate_header($data, NULL);
                $col_num += sizeof($this->sample_props_cols);
            } elseif ($header_read) {
                if (sizeof($data) != $col_num) {
                    $this->error_message = "Missing columns at row {$this->row}: ".$data[0]." Expected {$col_num} and found ".sizeof($data);
                    return FALSE;
                }
                $seq = array("gene" => trim($data[0]), "sequence" => trim($data[1]));
                $seqs[] = $seq;
            }
        }
        return $seqs;
    }

    function parse_data($filehandle, $num_rows) {
        $seqs = array();
        // Check the file handle
        if (!isset($filehandle) || $filehandle == NULL) {
            log_message("error", "Error parsing Genes file: Invalid file  handle");
            $this->error_message = "Error parsing Genes file: Invalid file  handle";
            return FALSE;
        }

        $j = 0;
        while ($j < $num_rows && (($data = fgetcsv($filehandle, 20000, "\t")) !== FALSE)) {
            if (sizeof($data) > 0 && (strpos($data[0], "#") !== 0)) {
                if (sizeof($data) != sizeof(self::$col_names)) {
                    $this->error_message = "Insuficient number of columns at row " . $this->row;
                    log_message('error', "Insuficient number of columns at row " . $this->row . " Cols detected: " . sizeof($data) . " Starting with: " . $data[0] . ", Ending with: " . $data[sizeof($data) - 1]);
                    return FALSE;
                } else {
                    $seq = array("gene" => trim($data[0]), "sequence" => trim($data[1]));
                    $seqs[] = $seq;
                }
            }
            $j++;
            $this->row++;
        }
        return $seqs;
    }

}
