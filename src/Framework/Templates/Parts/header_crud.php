<?php
/**
 * Created by PhpStorm.
 * User: Florian Moser
 * Date: 04.09.2015
 * Time: 10:50
 */ ?>
<?php if (!is_ajax_request()) {
    include $_SERVER['DOCUMENT_ROOT'] . "/Framework/Templates/Parts/header_content.php"; ?>
    <div class="row content">
    <?php
} else { ?>
    <div class="row no-gutters content clearfix">
    <?php
}
include $_SERVER['DOCUMENT_ROOT'] . "/Framework/Templates/Parts/message_template.php";
include $_SERVER['DOCUMENT_ROOT'] . "/Framework/Templates/Parts/loading_replacement.php" ?>