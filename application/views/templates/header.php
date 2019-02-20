<!DOCTYPE html >
<html>
    <?php
    $ci = get_instance();
    $language = $ci->session->userdata('language');
    $user = $ci->session->userdata('user');
    $user_type = $ci->session->userdata('user_type');
    $isLoggedIn = $ci->session->userdata('isLoggedIn');
    $version = 0;
    ?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>SqueezeM - Metagenomic Analysis Tool Addressing Microbial Ecology</title>
        <meta content='' name='description' />
        <meta name="robots" content="index,follow,archive" />
        <meta charset="UTF-8" />
        <meta name="language" content="es"/>
        <meta name="keywords" content="" />
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <!-- <link rel="icon" type="image/vnd.microsoft.icon" href="<?php echo $ci->config->base_url() ?>/resources/images/favicon.ico" /> 
        <link rel="shortcut icon" href="<?php echo $ci->config->base_url() ?>/resources/images/favicon.ico"> -->
        <link href="<?php echo $ci->config->base_url() ?>/resources/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $ci->config->base_url() ?>/resources/css/game.css" rel="stylesheet">
        <meta property='fb:admins' content=''/>
        <meta property='og:description' content=''/>
        <meta property='og:image' content=''/>
        <meta property='og:site_name' content=''/>
        <meta property='og:title' content=''/>
        <meta property='og:type' content=''/>
        <meta property='og:url' content=''/>
        <script type="text/javascript" src="<?php echo $ci->config->base_url() ?>/resources/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="<?php echo $ci->config->base_url() ?>/resources/js/game.js?v=<?php echo $version ?>"></script>

    </head>
    <body>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                if ($("#logout")) {
                    $("#logout").click(function (event) {
                        document.location.href = "<?php echo site_url("logout") ?>";
                        event.preventDefault();
                    });
                }
                $("#menu_users").click(function (event) {
                    document.location.href = "<?php echo site_url("admin/Users") ?>";
                    event.preventDefault();
                });
                $("#menu_projects").click(function (event) {
                    document.location.href = "<?php echo site_url("admin/Projects") ?>";
                    event.preventDefault();
                });
            });
        </script>

        <script type="text/javascript" src="<?php echo $ci->config->base_url() ?>/resources/js/bootstrap.js"></script>
        <div class="container">
            <?php if (isset($isLoggedIn) && $isLoggedIn == TRUE) { ?>
                <nav class="navbar navbar-default">
                    <div class="container-fluid">
                        <!-- Brand and toggle get grouped for better mobile display -->
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                                <span class="sr-only">Toggle navigation</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                            <a class="navbar-brand" href="#">SqueezeM</a>
                        </div>
                        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                            <ul class="nav navbar-nav">
                                <li <?= ((!isset($section) || $section == "projects") ? ' class="active"' : '') ?>><a id="menu_projects" href="#">Projects<span class="sr-only">(current)</span></a></li>
                                <?php if ($user_type == USER_TYPE_ADMIN) {?>
                                <li <?= ((isset($section) && $section == "users") ? ' class="active"' : '') ?>><a id="menu_users" href="#">Users</a></li>
                                <?php } ?>
                            </ul>
                            <?php if (isset($isLoggedIn) && $isLoggedIn == TRUE) { ?>
                                <button id="logout" type="button" class="btn btn-default navbar-btn navbar-right">Logout</button>
                            <?php } ?>
                        </div>
                    </div>
                </nav>
            <?php } ?>
