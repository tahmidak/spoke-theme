<?php
/**
 * header.php — Spoke Theme
 * Called by get_header() from Tutor LMS and other plugins.
 * block_template_part() gives the correct FSE rendering context.
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<?php block_template_part( 'header' ); ?>