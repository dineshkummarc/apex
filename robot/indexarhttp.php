<?php
$info='';
$response = http_get("http://intranet.cujae.edu.cu/", array("timeout"=>1), $info);
print_r($info);

?>