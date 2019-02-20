<?php
    $ci = get_instance();
    $ci->load->helper("search_helper");
    $search_data = $ci->session->userdata('search_data');
    $project_id = $search_data["project_id"];
?>
<script>
    jQuery(document).ready(function () {
       $("#export").click(function (event) {
           //$("#loading_message").addClass("show").removeClass("hidden");
           document.location.href = "<?php echo site_url("Projects/export_csv/")?>";
       });
       $("#page_size").change(function(event) {
            size = $(this).val();
            document.location.href = "<?php echo site_url("Projects/change_page_size/") ?>"+size;
            event.preventDefault();
        });
       var pageOfPages = <?=round(($page-1)/10)?>;
       $("#more").click(function(ev) {
            document.location.href = "<?php echo site_url('Projects/go_to_page/')?>"+(pageOfPages+1)*10+1;
            event.preventDefault();
            return;           
       });
       
        $(".page_links").each(function (index) {
            $(this).click(function(ev) {
                document.location.href = "<?php echo site_url("Projects/go_to_page/")?>"+$(this).html();
                ev.preventDefault();
                return;
            });
        });
        
        $("#first").click(function (event) {
            document.location.href = "<?php echo site_url('Projects/go_to_page/1')?>";
            event.preventDefault();
            return;            
        });
        $("#last").click(function (event) {
            document.location.href = "<?php echo site_url("Projects/go_to_page/{$num_of_pages}")?>";
            event.preventDefault();
            return;            
        });
        $("#previous").click(function (event) {
            document.location.href = "<?php echo site_url("Projects/go_to_page/".($page-1))?>";
            event.preventDefault();
            return;            
        });
        $("#next").click(function (event) {
            document.location.href = "<?php echo site_url("Projects/go_to_page/".($page+1))?>";
            event.preventDefault();
            return;            
        });
        $("#new_search").click(function (event) {
            document.location.href = "<?php echo site_url("Projects/new_search/{$project_id}")?>";
            event.preventDefault();
            return;            
        });
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url("Projects/search/{$project_id}")?>";
            event.preventDefault();
            return;            
        });
    });
</script>

<div class="row">
    
</div>
<div id="search_summary" class="row">
    <div id="search_detail" class="col-md-6">
        <h4>Search: <small><?=$query_summary?></small></h4>
    </div>
    <div class="col-md-2"><?=($page-1)*$page_size+1?>-<?=$page*$page_size?> of <?=$num_results?></div>
    <div id="page_size_container" class="col-md-1">
        <select class="form-control input-sm" id="page_size">
          <option value="10" <?=($page_size == 10?'selected':'')?>>10</option>
          <option value="50" <?=($page_size == 50?'selected':'')?>>50</option>
          <option value="100" <?=($page_size == 100?'selected':'')?>>100</option>
          <option value="250" <?=($page_size == 250?'selected':'')?>>250</option>
          <option value="500" <?=($page_size == 500?'selected':'')?>>500</option>
        </select>        
    </div>
    <div id="export" class="col-md-1 col-md-offset-2">
        <button id="export" class="btn btn-default btn-sm">Export</button>
    </div>
    
</div> <!-- end search summary -->
<div id="search_results" class="row">
    <?php if (isset($results) && sizeof($results) > 0) {
        $header = array_keys($results[0]);
        ?>
        <table class="table">
            <thead>
                <?php foreach ($header as $value) {?>
                <th><?=$value?></th>
                <?php } ?>
            </thead>
            <tbody>
                <?php foreach ($results as $row) {?>
                <tr>
                    <?php $i = 0;
                    foreach ($row as $field) {?>
                    <td><?=generate_link($project_id,$header[$i],$field)?></td>
                    <?php 
                    $i++;
                    } ?>
                </tr>
               <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
    <div class="alert alert-warning" role="alert">Your search returned no results</div>
<?php } ?>
</div> <!-- end search results -->
<div id="search_pagination" class="row">
<?php if ($num_of_pages > 1) { ?>
    <div class="col-md-8 col-md-offset-2">
        <nav aria-label="Page navigation">
        <ul id="pages" class="pagination">
            <li class="<?php if ($page == 1) {echo 'dissabled';} ?>"><a id="first" href="#" aria-label="Previous"><span aria-hidden="true">&lt;&lt;</span></a></li>
            <li class="<?php if ($page == 1) {echo 'dissabled';} ?>"><a id="previous" href="#" aria-label="Previous"><span aria-hidden="true">&lt;</span></a></li>
    <?php $offset = floor(($page-1)/10)*10;
    for ($i = $offset; $i < $num_of_pages && $i < $offset+10; $i++) { ?>
                <li class="<?= (($i + 1) == $page ? 'active' : '') ?>"><a class="page_links" href="#"><?= ($i + 1) ?></a></li>
        <?php } ?>
            <?php if ($num_of_pages > 10) {?>
                <li class=""><a id="more" href="#" aria-label="More"><span aria-hidden="true">...</span></a></li>
            <?php } ?>
            <li class="<?php if ($page == $num_of_pages) {echo 'dissabled';} ?>"><a id="next" href="#" aria-label="Next"><span aria-hidden="true">&gt;</span></a></li>
            <li class="<?php if ($page == $num_of_pages) {echo 'dissabled';} ?>"><a id="last" href="#" aria-label="Next"><span aria-hidden="true">&gt;&gt;</span></a></li>
        </ul>
        </nav>
    </div>
<?php } ?>
</div> <!--  end of pagination -->
<div id="search_controls" class="row">
    <div class="col-md-1 col-md-offset-5"><button class="btn btn-default" id="back">Back</button></div>
    <div lass="col-md-1"><button id="new_search" class="btn btn-primary">New search</button></div>
</div> <!--  end of search controls -->
<div class="floating_message hidden" id="loading_message">
    <div id="spinner" class="center-block">
        <img src="<?php echo $ci->config->base_url()?>/resources/images/spinner_game.gif" width="95" height="95"/>
        <div style="margin: 8px;"><h4>Searching data...</h4></div>
    </div>
</div>