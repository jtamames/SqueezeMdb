<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class SearchQueryBuilder {

    public static function buildQueryString($oper, $clauses) {
        $string = "";
        foreach ($clauses as $clause) {
            if (strlen($string) > 0) {
                $string = $string . " {$oper} ";
            }
            $string = $string . $clause["table"] . "." . $clause["field"] . " ";
            if ($clause["op"] == 'Equals' || $clause["op"] == 'Contains' || $clause["op"] == 'Starts with') {
                $string = $string . $clause["op"] . " '" . $clause["value"] . "'";
            } else {
                $string = $string . $clause["op"] . " " . $clause["value"];
            }
        }
        return $string;
    }

    public static function buildQuery($project_id, $bin_cols, $contig_cols, $gene_cols, $oper
    , $clauses, $model, $samples) {
        $has_abundances = FALSE;
        if (isset($bin_cols) && sizeof($bin_cols) > 0) {
            foreach ($bin_cols as $bin_col) {
                if ($bin_col == "norm_counts" || $bin_col == "coverage") {
                    $has_abundances = TRUE;
                    break;
                }
            }
        }
        if ($has_abundances == FALSE) {
            if (isset($contig_cols) && sizeof($contig_cols) > 0) {
                foreach ($contig_cols as $contig_col) {
                    if ($contig_col == "norm_counts" || $contig_col == "coverage") {
                        $has_abundances = TRUE;
                        break;
                    }
                }
            }
            if ($has_abundances == FALSE) {
                if (isset($gene_cols) && sizeof($gene_cols) > 0) {
                    foreach ($gene_cols as $gene_col) {
                        if ($gene_col == "norm_counts" || $gene_col == "raw_counts") {
                            $has_abundances = TRUE;
                            break;
                        }
                    }
                }
            }
        }
        $sql = NULL;
        //if ($has_abundances == TRUE) {
        if (FALSE == TRUE) {
            $sql = self::buildQueryWithAbundances($project_id, $bin_cols, $contig_cols, $gene_cols, $oper, $clauses, $model, $samples);
        } else {
            $sql = self::buildQueryWithoutAbundances($project_id, $bin_cols, $contig_cols, $gene_cols, $oper, $clauses, $model, $samples);
        }

        return $sql;
    }

    public static function buildQueryWithoutAbundances($project_id, $bin_cols, $contig_cols, $gene_cols, $oper
    , $clauses, $model, $samples) {
        $tables = array();
        // Build select section
        // Check from what tables we have to extract info
        $select = "SELECT ";
        $bin_counts = FALSE;
        $bin_coverage = FALSE;
        $has_sequence = FALSE;
        $has_method = FALSE;
        
        $group = "GROUP BY ";
        if (isset($bin_cols) && sizeof($bin_cols) > 0) {
            $tables["bin"] = TRUE;
            foreach ($bin_cols as $bin_col) {
                if ($bin_col == "norm_counts") {
                    $bin_counts = TRUE;
                } else if ($bin_col == "coverage") {
                    $bin_coverage = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = $select . ", ";
                        $group = "{$group}, ";
                    }
                    if ($bin_col == "method") {
                        $has_method = TRUE;
                        $select = $select . "Bin_Contig.{$bin_col} AS \"Bin {$bin_col}\"";
                        $group = "{$group} Bin_Contig.{$bin_col}";
                    } else {
                        $select = $select . "Bin.{$bin_col} AS \"Bin {$bin_col}\"";
                        $group = "{$group} Bin.{$bin_col}";
                    }
                }
            }
        }
        // Add abundances cols if selected
        if ($bin_counts == TRUE || $bin_coverage == TRUE) {
            if (strlen($select) > 7) {
                $select = "{$select}, ";
                $group = "{$group}, ";
            }
            $select = $select . self::get_bin_abundances_cols($bin_coverage, $bin_counts, $samples, TRUE);
            $group = $group . self::get_bin_abundances_cols($bin_coverage, $bin_counts, $samples, FALSE);
        }
        $contig_counts = FALSE;
        $contig_coverage = FALSE;
        if (isset($contig_cols) && sizeof($contig_cols) > 0) {
            $tables["contig"] = TRUE;
            foreach ($contig_cols as $contig_col) {
                if ($contig_col == "norm_counts") {
                    $contig_counts = TRUE;
                } else if ($contig_col == "coverage") {
                    $contig_coverage = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = "{$select}, ";
                        $group = "{$group}, ";
                    }
                    $select = $select . "Contig.{$contig_col} AS \"Contig {$contig_col}\"";
                    $group = $group . "Contig.{$contig_col}";
                }
            }
        }
        // Add abundances cols if selected
        if ($contig_counts == TRUE || $contig_coverage == TRUE) {
            if (strlen($select) > 7) {
                $select = "{$select}, ";
                $group = "{$group}, ";
            }
            $select = $select . self::get_contig_abundances_cols($contig_coverage, $contig_counts, $samples, TRUE);
            $group = $group . self::get_contig_abundances_cols($contig_coverage, $contig_counts, $samples, FALSE);
        }
        $gene_norm = FALSE;
        $gene_raw = FALSE;
        if (isset($gene_cols) && sizeof($gene_cols) > 0) {
            $tables["gene"] = TRUE;
            foreach ($gene_cols as $gene_col) {
                if ($gene_col == "norm_counts") {
                    $gene_norm = TRUE;
                } else if ($gene_col == "raw_counts") {
                    $gene_raw = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = "{$select}, ";
                        $group = "{$group}, ";
                    }
                    if ($gene_col == "sequence") {
                        $has_sequence = TRUE;
                        $select = $select . "Sequence.{$gene_col} AS \"Gene Sequence\"";
                        $group = $group . "Sequence.{$gene_col}";
                    } else {
                        $select = $select . "Gene.{$gene_col} AS \"Gene {$gene_col}\"";
                        $group = $group . "Gene.{$gene_col}";
                    }
                }
            }
        }
        // Add abundances cols if selected
        if ($gene_norm == TRUE || $gene_raw == TRUE) {
            if (strlen($select) > 7) {
                $select = "{$select}, ";
                $group = "{$group}, ";
            }
            $select = $select . self::get_gene_abundances_cols($gene_raw, $gene_norm, $samples, TRUE);
            $group = $group . self::get_gene_abundances_cols($gene_raw, $gene_norm, $samples, FALSE);
        }
        // Build where section
        $where = "";
        foreach ($clauses as $clause) {
            if (strlen($where) == 0) {
                $where = " WHERE Sample.project_ID={$project_id} AND (";
            } else {
                $where = $where . " {$oper} ";
            }
            // Build clause itself
            $field = $model[$clause["table"]][$clause["field"]]["col"];
            $sql_operator = self::sqlOperator($clause["op"], $clause["value"]);
            $where = $where . $clause["table"] . "." . $field . " " . $sql_operator;

            switch ($clause["table"]) {
                case "Gene":
                    $tables["gene"] = TRUE;
                    break;
                case "Contig":
                    $tables["contig"] = TRUE;
                    break;
                case "Bin":
                    $tables["bin"] = TRUE;
                    break;
            }
        }
        $where = $where . ")";

        // Build the FROM section
        $from = " FROM ";
        if (array_key_exists("gene", $tables)) {
            $from = $from . " Gene JOIN Sample_Gene ON (Gene.id=Sample_Gene.gene_id) JOIN Sample ON (Sample_Gene.sample_ID=Sample.id)";
            if ($has_sequence == TRUE) {
                $from = $from. " LEFT JOIN Sequence ON (Sequence.gene_id=Gene.id) ";
            }
            if ($gene_norm == TRUE || $gene_raw == TRUE) {
                $from = $from . "JOIN ( " . self::get_gene_abundances($project_id, $gene_raw, $gene_norm, $samples) . " ) gabun ON (gabun.gene_id=Gene.id) ";
            }
        }
        if (array_key_exists("contig", $tables)) {
            if (strlen($from) == 6) {
                $from = $from . " Contig JOIN Sample_Contig ON (Contig.id=Sample_Contig.contig_id) JOIN Sample ON (Sample_Contig.sample_ID=Sample.id) ";
            } else {
                $from = $from . " JOIN Contig ON (Gene.contig_id=Contig.id) ";
            }
            if ($contig_counts == TRUE || $contig_coverage == TRUE) {
                $from = $from . "JOIN (" . self::get_contig_abundances($project_id, $contig_counts, $contig_coverage, $samples) . ") cabun ON (cabun.contig_id=Contig.id) ";
            }
        }
        if (array_key_exists("bin", $tables)) {
            if (strlen($from) == 6) {
                $from = $from . " Bin JOIN Sample_Bin ON (Bin.id=Sample_Bin.bin_id) JOIN Sample ON (Sample_Bin.sample_ID=Sample.id) ";
                if ($has_method == TRUE) {
                    $from = $from . " JOIN Bin_Contig ON (Bin_Contig.bin_id=Bin.id) ";
                } 
            } else {
                if (!array_key_exists("contig", $tables)) {
                    $from = $from . " JOIN Contig ON (Gene.contig_id=Contig.id) ";
                }
                $from = $from . " JOIN Bin_Contig ON (Bin_Contig.contig_id=Contig.id) JOIN Bin ON (Bin.id=Bin_Contig.bin_id) ";
            }
            if ($bin_counts == TRUE || $bin_coverage == TRUE) {
                $from = $from . "JOIN (" . self::get_bin_abundances($project_id, $bin_coverage, $bin_counts, $samples) . ") babun ON (babun.bin_id=Bin.id) ";
            }
        }
        $query = $select . $from . $where . " {$group}";
        log_message("debug", "Search query: {$query}");
        return $query;
    }

    static protected function sqlOperator($oper, $value) {
        $text = NULL;
        switch ($oper) {
            case "Equals":
                $text = "='{$value}'";
                break;
            case "Contains":
                $text = " LIKE '%{$value}%'";
                break;
            case "Starts with":
                $text = " LIKE '{$value}%'";
                break;
            default:
                $text = $oper . $value;
                break;
        }
        return $text;
    }

    static protected function get_gene_abundances_cols($raw, $norm, $samples, $alias) {
        $cols = "";
        foreach ($samples as $sample) {
            if ($raw == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " gabun." . $sample->name . "_raw_counts" . ($alias == TRUE ? " as gene_" . $sample->name . "_raw_counts" : "");
            }
            if ($norm == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " gabun." . $sample->name . "_norm_counts" . ($alias == TRUE ? " as gene_" . $sample->name . "_norm_counts" : "");
            }
        }

        return $cols;
    }

    static protected function get_bin_abundances_cols($coverage, $norm, $samples, $alias) {
        $cols = "";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " babun." . $sample->name . "_coverage" . ($alias == TRUE ? " as bin_" . $sample->name . "_coverage" : "");
            }
            if ($norm == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " babun." . $sample->name . "_norm_counts" . ($alias == TRUE ? " as bin_" . $sample->name . "_norm_counts" : "");
            }
        }

        return $cols;
    }

    static protected function get_contig_abundances_cols($coverage, $norm, $samples, $alias) {
        $cols = "";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " cabun." . $sample->name . "_coverage" . ($alias == TRUE ? " as contig_" . $sample->name . "_coverage" : "");
            }
            if ($norm == TRUE) {
                $cols = (strlen($cols) > 0 ? "{$cols}," : "") . " cabun." . $sample->name . "_norm_counts" . ($alias == TRUE ? " as contig_" . $sample->name . "_norm_counts" : "");
            }
        }

        return $cols;
    }

    static protected function get_gene_abundances($project_id, $raw, $norm, $samples) {
        $sql = "SELECT gene_id";
        foreach ($samples as $sample) {
            if ($raw == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sg.raw_counts,0)) as " . $sample->name . "_raw_counts";
            }
            if ($norm == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sg.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Gene sg JOIN Sample sam ON (sg.Sample_id=sam.id) WHERE sam.project_id={$project_id} GROUP BY gene_id";

        return $sql;
    }

    static protected function get_contig_abundances($project_id, $coverage, $norm, $samples) {
        $sql = "SELECT contig_id";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sc.coverage,0)) as " . $sample->name . "_coverage";
            }
            if ($norm == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sc.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Contig sc JOIN Sample sam ON (sc.Sample_id=sam.id) WHERE sam.project_id={$project_id} GROUP BY contig_id";

        return $sql;
    }

    static protected function get_bin_abundances($project_id, $coverage, $norm, $samples) {
        $sql = "SELECT bin_id";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sb.coverage,0)) as " . $sample->name . "_coverage";
            }
            if ($norm == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sb.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Bin sb JOIN Sample sam ON (sb.Sample_id=sam.id) WHERE sam.project_id=" . $project_id . " GROUP BY bin_id";

        return $sql;
    }

    static public function buildQueryWithAbundances($project_id, $bin_cols, $contig_cols, $gene_cols, $oper
    , $clauses, $model, $samples) {
        $tables = array();
        $bin_counts = FALSE;
        $bin_coverage = FALSE;
        $contig_counts = FALSE;
        $contig_coverage = FALSE;
        $gene_norm = FALSE;
        $gene_raw = FALSE;
        $has_sequence = FALSE;
        $has_method = FALSE;
        $select = "SELECT ";
        $group = "GROUP BY ";

        // Check bin columns and build select 
        if (isset($bin_cols) && sizeof($bin_cols) > 0) {
            $tables["bin"] = TRUE;
            foreach ($bin_cols as $bin_col) {
                log_message("DEBUG", "::::::::> bin COL: ${bin_col}");
                if ($bin_col == "norm_counts") {
                    $bin_counts = TRUE;
                } else if ($bin_col == "coverage") {
                    $bin_coverage = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = $select . ", ";
                        $group = $group . ", ";
                    }
                    if ($bin_col == "method") {
                        log_message("DEBUG", "::::::::> bin 0 has method");
                        $has_method = TRUE;
                        $select = $select . "Bin_Contig.{$bin_col} AS \"Bin {$bin_col}\"";
                        $group = $group . "Bin_Contig.{$bin_col}";
                    } else {
                        $select = $select . "Bin.{$bin_col} AS \"Bin {$bin_col}\"";
                        $group = $group . "Bin.{$bin_col}";
                    }
                }
            }
            if ($bin_counts == TRUE || $bin_coverage == TRUE) {
                if (strlen($select) > 7) {
                    $select = "{$select}, ";
                    $group = "{$group}, ";
                }
                //$select = "{$select}babun.*";
                $select = "{$select}".self::get_bin_abundances_cols($bin_coverage, $bin_counts, $samples, TRUE);
                $group = "{$group}".self::get_bin_abundances_cols($bin_coverage, $bin_counts, $samples, FALSE);
            }
        }
        // Check contig columns and build select 
        if (isset($contig_cols) && sizeof($contig_cols) > 0) {
            $tables["contig"] = TRUE;
            foreach ($contig_cols as $contig_col) {
                if ($contig_col == "norm_counts") {
                    $contig_counts = TRUE;
                } else if ($contig_col == "coverage") {
                    $contig_coverage = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = "{$select}, ";
                        $group = $group . ", ";
                    }
                    $select = $select . "Contig.{$contig_col} AS \"Contig {$contig_col}\"";
                    $group = $group . "Contig.{$contig_col}";
                }
            }
            if ($contig_counts == TRUE || $contig_coverage == TRUE) {
                if (strlen($select) > 7) {
                    $select = "{$select}, ";
                    $group = "{$group}, ";
                }
                //$select = "{$select}cabun.*";
                $select = "{$select}".self::get_contig_abundances_cols($contig_coverage, $contig_counts, $samples, TRUE);
                $group = "{$group}".self::get_contig_abundances_cols($contig_coverage, $contig_counts, $samples, FALSE);
            }
        }

        // Check gene columns and build select 
        if (isset($gene_cols) && sizeof($gene_cols) > 0) {
            $tables["gene"] = TRUE;
            foreach ($gene_cols as $gene_col) {
                if ($gene_col == "norm_counts") {
                    $gene_norm = TRUE;
                } else if ($gene_col == "raw_counts") {
                    $gene_raw = TRUE;
                } else {
                    if (strlen($select) > 7) {
                        $select = "{$select}, ";
                        $group = "{$group}, ";
                    }
                    if ($gene_col == "sequence") {
                        $has_sequence = TRUE;
                        $select = $select . "Sequence.{$gene_col} AS \"Gene Sequence\"";
                        $group = $group . "Sequence.{$gene_col}";
                    } else {
                        $select = $select . "Gene.{$gene_col} AS \"Gene {$gene_col}\"";
                        $group = $group . "Gene.{$gene_col}";
                    }
                }
            }
            // Add abundances cols if selected
            if ($gene_norm == TRUE || $gene_raw == TRUE) {
                $gene_abundances = TRUE;
                if (strlen($select) > 7) {
                    $select = "{$select}, ";
                    $group = "{$group}, ";
                }
                //$select = "{$select}gabun.*";
                $select = "{$select}".self::get_gene_abundances_cols($gene_raw, $gene_norm, $samples, TRUE);
                $group = "{$group}".self::get_gene_abundances_cols($gene_raw, $gene_norm, $samples, FALSE);
            }
        }

        // BUILD FROM CLAUSE
        // Buid the subquery that applies the search criteria
        $subquery = self::buildSubquery($project_id, $tables, $oper, $clauses, $model);
        $from = "FROM ({$subquery}) ids INNER JOIN " . self::buildFromClause($tables,FALSE,$has_method, $has_sequence);
        // Abundances subqueries
        if ($bin_counts == TRUE || $bin_coverage == TRUE) {
            $from = $from . " INNER JOIN (" . self::buildBinAbundancesSubquery($samples, $project_id
                            , $tables, $oper, $clauses, $model, $bin_counts, $bin_coverage) . ") babun ON (ids.bin_id=babun.bin_id)";
        }
        if ($contig_counts == TRUE || $contig_coverage == TRUE) {
            $from = $from . " INNER JOIN (" . self::buildContigAbundancesSubquery($samples, $project_id
                            , $tables, $oper, $clauses, $model, $contig_counts, $contig_coverage) . ") cabun ON (ids.contig_id=cabun.contig_id)";
        }
        if ($gene_norm == TRUE || $gene_raw == TRUE) {
            $from = $from . " INNER JOIN (" . self::buildGeneAbundancesSubquery($samples, $project_id
                            , $tables, $oper, $clauses, $model, $gene_raw, $gene_norm) . ") gabun ON (ids.gene_id=gabun.gene_id)";
        }

        $sql = "{$select} {$from} {$group}";
        log_message("DEBUG", $sql);
        return $sql;
    }

    protected static function buildSubquery($project_id, $tables, $oper, $clauses, $model, $column = NULL) {
        $select = "SELECT";
        $group = "GROUP BY";
        if (!isset($column) || $column == NULL) {
            if (array_key_exists("bin", $tables)) {
                $select = "{$select} Bin.id as bin_id";
                $group = "{$group} Bin.id";
            }
            if (array_key_exists("contig", $tables)) {
                if (strlen($select) > 6) {
                    $select = "{$select},";
                    $group = "{$group},";
                }
                $select = "{$select} Contig.id as contig_id";
                $group = "{$group} Contig.id";
            }
            if (array_key_exists("gene", $tables)) {
                if (strlen($select) > 6) {
                    $select = "{$select},";
                    $group = "{$group},";
                }
                $select = "{$select} Gene.id as gene_id";
                $group = "{$group} Gene.id";
            }
        } else {
            switch ($column) {
                case "bin":
                    $select = "{$select} Bin.id as bin_id";
                    $group = "{$group} Bin.id";
                    break;
                case "contig":
                    $select = "{$select} Contig.id as contig_id";
                    $group = "{$group} Contig.id";
                    break;
                case "gene":
                    $select = "{$select} Gene.id as gene_id";
                    $group = "{$group} Gene.id";
                    break;
            }
        }
        // 
        // Build where section
        $where = "";
        foreach ($clauses as $clause) {
            if (strlen($where) == 0) {
                $where = " WHERE Sample.project_ID={$project_id} AND (";
            } else {
                $where = $where . " {$oper} ";
            }
            // Build clause itself
            $field = $model[$clause["table"]][$clause["field"]]["col"];
            $sql_operator = self::sqlOperator($clause["op"], $clause["value"]);
            $where = $where . $clause["table"] . "." . $field . " " . $sql_operator;

            switch ($clause["table"]) {
                case "Gene":
                    $tables["gene"] = TRUE;
                    break;
                case "Contig":
                    $tables["contig"] = TRUE;
                    break;
                case "Bin":
                    $tables["bin"] = TRUE;
                    break;
            }
        }
        $where = $where . ")";

        // Build the FROM section
        $from = "FROM " . self::buildFromClause($tables,TRUE);

        $sql = "{$select} {$from} {$where} {$group}";
        log_message("DEBUG", "::::::> {$sql}");

        return $sql;
    }

    protected static function buildFromClause($tables, $isSubQuery = FALSE, $has_method = FALSE, $has_sequence = FALSE) {
        $from = "";
        log_message("DEBUG", "sub: {$isSubQuery} Meth: {$has_method} seq: {$has_sequence}");
        if (array_key_exists("gene", $tables)) {
            $from = $from . " Gene " . ($isSubQuery == FALSE ? " ON (ids.gene_id=Gene.id) " : "") . "JOIN Sample_Gene ON (Gene.id=Sample_Gene.gene_id) JOIN Sample ON (Sample_Gene.sample_ID=Sample.id)";
            if ($has_sequence == TRUE) {
                $from = $from . " LEFT JOIN Sequence ON (Sequence.gene_id=Gene.id)";
            }
        }
        if (array_key_exists("contig", $tables)) {
            if (strlen($from) == 0) {
                $from = $from . " Contig " . ($isSubQuery == FALSE ? " ON (ids.contig_id=Contig.id) " : "") . "JOIN Sample_Contig ON (Contig.id=Sample_Contig.contig_id) JOIN Sample ON (Sample_Contig.sample_ID=Sample.id) ";
            } else {
                $from = $from . " JOIN Contig ON (Gene.contig_id=Contig.id) ";
            }
        }
        if (array_key_exists("bin", $tables)) {
            if (strlen($from) == 0) {
                $from = $from . "Bin " . ($isSubQuery == FALSE ? " ON (ids.bin_id=Bin.id) " : "") . "JOIN Sample_Bin ON (Bin.id=Sample_Bin.bin_id) JOIN Sample ON (Sample_Bin.sample_ID=Sample.id) ";
                if ($has_method == TRUE) {
                    $from = $from . " JOIN Bin_Contig ON (Bin_Contig.bin_id=Bin.id) ";
                }
            } else {
                if (!array_key_exists("contig", $tables)) {
                    $from = $from . " JOIN Contig ON (Gene.contig_id=Contig.id) ";
                }
                $from = $from . " JOIN Bin_Contig ON (Bin_Contig.contig_id=Contig.id) JOIN Bin ON (Bin.id=Bin_Contig.bin_id) ";
            }
        }
        return $from;
    }

    protected static function buildGeneAbundancesSubquery($samples, $project_id
    , $tables, $oper, $clauses, $model, $raw, $norm) {

        $sql = "SELECT gene_id";
        foreach ($samples as $sample) {
            if ($raw == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sg.raw_counts,0)) as " . $sample->name . "_raw_counts";
            }
            if ($norm == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sg.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Gene sg JOIN Sample sam ON (sg.Sample_id=sam.id)";
        $sql = $sql . " WHERE sg.gene_id IN (" . self::buildSubquery($project_id, $tables, $oper, $clauses, $model, "gene") . ")";
        $sql = $sql . " GROUP BY gene_id";

        return $sql;
    }

    protected static function buildContigAbundancesSubquery($samples, $project_id
    , $tables, $oper, $clauses, $model, $counts, $coverage) {

        $sql = "SELECT contig_id";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sc.coverage,0)) as " . $sample->name . "_coverage";
            }
            if ($counts == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sc.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Contig sc JOIN Sample sam ON (sc.Sample_id=sam.id)";
        $sql = $sql . " WHERE sc.contig_id IN (" . self::buildSubquery($project_id, $tables, $oper, $clauses, $model, "contig") . ")";
        $sql = $sql . " GROUP BY contig_id";

        return $sql;
    }

    protected static function buildBinAbundancesSubquery($samples, $project_id
    , $tables, $oper, $clauses, $model, $counts, $coverage) {

        $sql = "SELECT bin_id";
        foreach ($samples as $sample) {
            if ($coverage == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sb.coverage,0)) as " . $sample->name . "_coverage";
            }
            if ($counts == TRUE) {
                $sql = $sql . ", sum( if( sam.name = '" . $sample->name . "',sb.norm_counts,0)) as " . $sample->name . "_norm_counts";
            }
        }
        $sql = $sql . " FROM Sample_Bin sb JOIN Sample sam ON (sb.Sample_id=sam.id)";
        $sql = $sql . " WHERE sb.bin_id IN (" . self::buildSubquery($project_id, $tables, $oper, $clauses, $model, "bin") . ")";
        $sql = $sql . " GROUP BY bin_id";

        return $sql;
    }

}
