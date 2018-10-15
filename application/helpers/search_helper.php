<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function generate_link($project_id, $column, $value) {
    $link = NULL;
    $aux_value = rawurlencode($value);
    switch (strtolower($column)) {
        case "contig name":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Contig/name/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "gene taxonomy":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Gene/taxonomy/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "gene kegg_id":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Gene/KEGG%20ID/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "gene cog_id":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Gene/Cog%20ID/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "gene kegg_pathway":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Gene/KEGG%20Pathway/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "gene cog_pathway":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/gene/Gene/Cog%20Pathway/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "contig taxonomy":
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/contig/Contig/taxonomy/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "bin name";
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/contig/Bin/name/{$aux_value}")."\")'>{$value}</a>";
            break;
        case "bin taxonomy";
            $link = "<a href='#' onclick='searchLink(\"".site_url("Projects/predef_search/{$project_id}/bin/Bin/taxonomy/{$aux_value}")."\")'>{$value}</a>";
            break;
        default:
            $link = $value;
            break;
    }
    return $link;
}