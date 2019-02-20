<?php

class Projects extends MY_User_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function search($project_id) {
        $this->load->helper("file");
        $str_search = file_get_contents("./application/data/search_model.json");
        $search_model = json_decode($str_search, TRUE);

        $this->load->model('Project_model');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('table_1', 'Table (Clause 1)', 'required');
        $samples = $this->Project_model->get_samples($project_id);

        if ($this->form_validation->run() == FALSE) {
            // Check if there is a previous search on a different project
            $search_data = $this->session->userdata('search_data');
            if ($search_data['project_id'] && $search_data['project_id'] <> $project_id) {
                // Remove previous search data
                $this->session->unset_userdata('search_data');
            }

            $project = $this->Project_model->get_project($project_id);
            // Load the search data model 
            $this->session->set_userdata('search_model', $search_model);

            $data = array(
                "id" => $project_id,
                "project" => $project,
                "samples" => $samples,
                "section" => 'projects'
            );
            $this->load->view('templates/header', $data);
            $this->load->view('projects/search', $data);
            $this->load->view('templates/footer', $data);
        } else {
            // Retrieve form data
            $bin_cols = $this->input->post("bin");
            $contig_cols = $this->input->post("contig");
            $gene_cols = $this->input->post("gene");
            $sel_cols = sizeof($bin_cols) + sizeof($contig_cols) + sizeof($gene_cols);
            $param_names = array_keys($this->input->post());
            $errorMsg = "";
            $clauses = array();
            foreach ($param_names as $param) {
                if (strpos($param, "table_") === 0) {
                    // Get the clause index
                    $n = substr($param, 6);
                    if ($this->validateClause($n) == TRUE) {
                    $clause = array(
                        "table" => $this->input->post($param),
                        "field" => $this->input->post("field_{$n}"),
                        "op" => $this->input->post("oper_{$n}"),
                        "value" => $this->input->post("value_{$n}")
                    );
                    // Create the clause
                    $clauses[] = $clause;
                    }
                    else {
                        $errorMsg = "Missing parameteres in Search criteria, clause num. {$n}";
                    }
                }
            }

            if ($sel_cols == 0) {
                $errorMsg = "Please, select at least one column to retrieve";
            } else if (sizeof($clauses) == 0) {
                $errorMsg = "Please, specify at least one search criteria";
            }

            if (isset($errorMsg) && strlen($errorMsg) > 0) {
                log_message("debug", "_________> {$errorMsg}");
                $project = $this->Project_model->get_project($project_id);
                // Load the search data model 
                $this->session->set_userdata('search_model', $search_model);

                $data = array(
                    "id" => $project_id,
                    "project" => $project,
                    "samples" => $samples,
                    "section" => 'projects',
                    "error" => $errorMsg
                );
                $this->load->view('templates/header', $data);
                $this->load->view('projects/search', $data);
                $this->load->view('templates/footer', $data);

                return;
            }

            // Store search data into session
            $search_data = array(
                "bin_cols" => $bin_cols,
                "contig_cols" => $contig_cols,
                "gene_cols" => $gene_cols,
                "operator" => $this->input->post("logical_oper"),
                "clauses" => $clauses,
                "project_id" => $project_id,
                "page_size" => 100,
                "page" => 1
            );

            $this->load->library('session');
            $this->session->set_userdata('search_data', $search_data);

            // Build the search query
            $this->load->library("SearchQueryBuilder");
            $sql = SearchQueryBuilder::buildQuery($project_id, $bin_cols, $contig_cols, $gene_cols
                            , $this->input->post("logical_oper"), $clauses, $search_model, $samples);
            // Execute query
            $results = $this->Project_model->search_query($sql);
            $num_results = sizeof($results);

            // Do results pagination
            $page = $search_data["page"];
            $page_size = $search_data["page_size"];
            $aux_results = array_slice($results, ($page - 1) * $page_size, $page_size);

            $data = array(
                "query_summary" => SearchQueryBuilder::buildQueryString($this->input->post("logical_oper"), $clauses),
                "num_results" => $num_results,
                "page" => $page,
                "page_size" => $page_size,
                "num_of_pages" => round(ceil($num_results / $page_size)),
                "results" => $aux_results,
                "section" => 'projects'
            );

            $this->load->view('templates/header', $data);
            $this->load->view('projects/search_results', $data);
            $this->load->view('templates/footer', $data);
        }
    }

    function predef_search($project_id, $table, $prop_table, $property, $value) {
        $this->load->helper("file");
        $str_search = file_get_contents("./application/data/search_model.json");
        $search_model = json_decode($str_search, TRUE);
        $property = urldecode($property);
        $value = urldecode($value);
        // Define which are the predefined columns to be retrieved by the search engine
        $bin_cols = NULL;
        $contig_cols = NULL;
        $gene_cols = NULL;
        switch ($table) {
            case "bin":
                $bin_cols = ["Name", "Method", "Taxonomy", "Size", "Contig_num", "gc_per", "Chimerism", "Contamination", "Strain_Het", "norm_counts", "coverage"];
                $contig_cols = NULL;
                $gene_cols = NULL;
                break;
            case "contig":
                $contig_cols = ["Name", "Taxonomy", "Size", "Genes_num", "gc_per", "Chimerism", "norm_counts", "coverage"];
                $bin_cols = NULL;
                $gene_cols = NULL;
                break;
            case "gene":
                $gene_cols = ["ORF", "name", "taxonomy", "gc_per", "kegg_id", "kegg_function", "kegg_pathway", "cog_id", "cog_function", "cog_pathway", "Pfam", "norm_counts", "raw_counts"];
                $bin_cols = NULL;
                $contig_cols = NULL;
                break;
        }
        // Define the search clause
        $clause = array(
            "table" => $prop_table,
            "field" => $property,
            "op" => "Equals",
            "value" => $value
        );
        $clauses[] = $clause;

        // Store search data into session
        $search_data = array(
            "bin_cols" => $bin_cols,
            "contig_cols" => $contig_cols,
            "gene_cols" => $gene_cols,
            "operator" => "AND",
            "clauses" => $clauses,
            "project_id" => $project_id,
            "page_size" => 100,
            "page" => 1
        );

        $this->load->library('session');
        $this->session->set_userdata('search_data', $search_data);

        $this->load->model("Project_model");
        $samples = $this->Project_model->get_samples($project_id);

        // Build the search query
        $this->load->library("SearchQueryBuilder");
        $sql = SearchQueryBuilder::buildQuery($project_id, $bin_cols, $contig_cols, $gene_cols
                        , "AND", $clauses, $search_model,$samples);
        // Execute query
        $results = $this->Project_model->search_query($sql);
        $num_results = sizeof($results);

        // Do results pagination
        $page = $search_data["page"];
        $page_size = $search_data["page_size"];
        $aux_results = array_slice($results, ($page - 1) * $page_size, $page_size);

        $data = array(
            "query_summary" => SearchQueryBuilder::buildQueryString("AND", $clauses),
            "num_results" => $num_results,
            "page" => $page,
            "page_size" => $page_size,
            "num_of_pages" => round(ceil($num_results / $page_size)),
            "results" => $aux_results,
            "section" => 'projects'
        );

        $this->load->view('templates/header', $data);
        $this->load->view('projects/search_results', $data);
        $this->load->view('templates/footer', $data);
    }

    function change_page_size($page_size) {
        $this->load->library('session');
        $this->load->helper("file");
        $this->load->library("SearchQueryBuilder");
        $this->load->model("Project_model");
        
        $str_search = file_get_contents("./application/data/search_model.json");
        $search_model = json_decode($str_search, TRUE);

        $search_data = $this->session->userdata("search_data");
        // TODO: Validate that there there is search data stored in the session
        $search_data["page_size"] = $page_size;
        $search_data["page"] = 1;
        $this->session->set_userdata("search_data", $search_data);
        $samples = $this->Project_model->get_samples($search_data["project_id"]);
        
        $sql = SearchQueryBuilder::buildQuery($search_data["project_id"], $search_data["bin_cols"]
                , $search_data["contig_cols"], $search_data["gene_cols"]
                , $search_data["operator"], $search_data["clauses"], $search_model, $samples);
        // Execute query
        $results = $this->Project_model->search_query($sql);
        $num_results = sizeof($results);

        // Do results pagination
        $page = 1;
        $aux_results = array_slice($results, ($page - 1) * $page_size, $page_size);

        $data = array(
            "query_summary" => SearchQueryBuilder::buildQueryString($search_data["operator"], $search_data["clauses"]),
            "num_results" => $num_results,
            "page" => $page,
            "page_size" => $search_data["page_size"],
            "num_of_pages" => round(ceil($num_results / $search_data["page_size"])),
            "results" => $aux_results
        );

        $this->load->view('templates/header', $data);
        $this->load->view('projects/search_results', $data);
        $this->load->view('templates/footer', $data);
    }

    function go_to_page($page) {
        $this->load->library('session');
        $this->load->helper("file");
        $this->load->library("SearchQueryBuilder");
        $this->load->model("Project_model");
        $str_search = file_get_contents("./application/data/search_model.json");
        $search_model = json_decode($str_search, TRUE);

        $search_data = $this->session->userdata("search_data");
        // TODO: Validate that there there is search data stored in the session
        $search_data["page"] = $page;
        $this->session->set_userdata("search_data", $search_data);

        $samples = $this->Project_model->get_samples($search_data["project_id"]);
        $sql = SearchQueryBuilder::buildQuery($search_data["project_id"], $search_data["bin_cols"]
                        , $search_data["contig_cols"], $search_data["gene_cols"]
                        , $search_data["operator"], $search_data["clauses"], $search_model,$samples);
        // Execute query
        $results = $this->Project_model->search_query($sql);
        $num_results = sizeof($results);

        // Do results pagination
        $aux_results = array_slice($results, ($search_data["page"] - 1) * $search_data["page_size"], $search_data["page_size"]);

        $data = array(
            "query_summary" => SearchQueryBuilder::buildQueryString($search_data["operator"], $search_data["clauses"]),
            "num_results" => $num_results,
            "page" => $search_data["page"],
            "page_size" => $search_data["page_size"],
            "num_of_pages" => round(ceil($num_results / $search_data["page_size"])),
            "results" => $aux_results
        );

        $this->load->view('templates/header', $data);
        $this->load->view('projects/search_results', $data);
        $this->load->view('templates/footer', $data);
    }

    function new_search($project_id) {
        $this->session->unset_userdata('search_data');
        redirect("Projects/search/{$project_id}");
    }

    function validateClause($n) {
        if ($this->input->post("table_{$n}") !== NULL && strlen($this->input->post("table_{$n}")) > 0
            && $this->input->post("field_{$n}") !== NULL && strlen($this->input->post("field_{$n}")) > 0
            && $this->input->post("oper_{$n}") !== NULL && strlen($this->input->post("oper_{$n}")) > 0
            && $this->input->post("value_{$n}") !== NULL && strlen($this->input->post("value_{$n}")) > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
    
    function export_csv() {
        $this->load->library('session');
        $this->load->helper("file");
        $this->load->library("SearchQueryBuilder");
        $this->load->model("Project_model");
        $this->load->helper('download');
        $str_search = file_get_contents("./application/data/search_model.json");
        $search_model = json_decode($str_search, TRUE);

        // Create export file
        $rand_folder = rand(0,1000000);
        $dir = $this->config->item('download_dir');
        $download_dir = "{$dir}/{$rand_folder}/";
        mkdir($download_dir,0777);
        
        $search_data = $this->session->userdata("search_data");

        $samples = $this->Project_model->get_samples($search_data["project_id"]);
        $sql = SearchQueryBuilder::buildQuery($search_data["project_id"], $search_data["bin_cols"]
                        , $search_data["contig_cols"], $search_data["gene_cols"]
                        , $search_data["operator"], $search_data["clauses"], $search_model,$samples);
        // Execute query
        $results = $this->Project_model->search_query($sql);
        if (sizeof($results) > 0) {
            // write results to the file
            $file = fopen("$download_dir/matame_export.csv","w");
            if ($file === FALSE) {
                log_message('ERROR', error_get_last());
                return;
            }
            else {
            // Write header
            $header = array_keys($results[0]);
            $header_str = implode(",", $header);
            fwrite($file, $header_str."\n");
            foreach($results as $row) {
                $row = array_map(function ($r) {return "\"{$r}\"";}, $row);
                $row_str = implode(",", $row);
                fwrite($file, $row_str."\n");
            }
            fclose($file);
            
            // Download
                force_download("$download_dir/matame_export.csv", NULL);
                return;
            }
        } else {
            redirect("Projects/go_to_page/".$search_data["page"]);
        }
    }
}
