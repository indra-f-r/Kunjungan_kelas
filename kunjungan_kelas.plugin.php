<?php
/**
 * Plugin Name: Kunjungan Kelas
 * Plugin URI: https://github.com/indra-f-r
 * Description: Plugin untuk Pengunjung Per Kelas
 * Version: 1.0.0
 * Author: Indra Febriana Rulliawan (indra.f.rulliawan@gmail.com)
 * Author URI: https://github.com/indra-f-r
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// register menu ke modul Keanggotaan
$plugin->registerMenu('membership', 'Kunjungan Kelas', __DIR__ . '/index.php');
