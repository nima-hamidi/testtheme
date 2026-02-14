<?php
/**
 * آرشیو ژانر - استفاده از آرشیو رمان با فیلتر ژانر
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ست کردن ژانر فعلی و ارسال به آرشیو
$_GET['genre'] = get_queried_object()->slug;
get_template_part( 'archive', 'novel' );