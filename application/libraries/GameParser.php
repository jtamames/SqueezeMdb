<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

abstract class GameParser {
            
    protected $samples_cols_match;

    protected $error_message;

    abstract protected function get_column_names();
    
    public function get_error() {
        return $this->error_message;
    }
    
    protected function validate_header($header, $sample) {
        $num_samples = sizeof($sample);
        $col_names = $this->get_column_names();
        $num_fixed_cols = sizeof($col_names);
        log_message("DEBUG", "SMPS: {$num_samples} FXCOLS: {$num_fixed_cols} HEAD: ".sizeof($header));
        if (2*$num_samples + $num_fixed_cols != sizeof($header)) {
            $this->error_message = "Wrong number of columns";
            return FALSE;
        }
        // Validate fixed name columns
        $sample_cols = 0;
        for ($i = 0; $i < sizeof($header); $i++) {
            
            if ($i < $num_fixed_cols) {
                if ($header[$i] != $col_names[$i]) {
                    $this->error_message = "Wrong header: Expecting ".$col_names[$i];
                    return FALSE;
                }
            } else {
                $aux_sample = "";
                if (strpos($header[$i],"RPKM") === 0) {
                    $aux_sample = substr($header[$i],5);
                    $this->samples_cols_match[$aux_sample]["norm"] = $i;
                }
                elseif (strpos($header[$i],"Coverage") === 0) {
                    $aux_sample = substr($header[$i],9);
                    $this->samples_cols_match[$aux_sample]["cover"] = $i;
                }
                else {
                    $this->error_message = "Wrong header: Unknown column '".$header[$i]."'";
                    return FALSE;
                }
                $sample_cols++;
            }
        }
        if ($sample_cols != $num_samples*2) {
            $this->error_message = "Missing sample data columns";
            return FALSE;
        }
        $this->error_message = "";
        return TRUE;
    }
}