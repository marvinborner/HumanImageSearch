<?php
/**
 * User: Marvin Borner
 * Date: 22/09/2018
 * Time: 15:00
 */

require 'request.php';

$userData = getData(urlencode($_GET['name']), $_GET['count']);
processData($userData);