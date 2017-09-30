<?php
ob_start();
phpinfo();
$php_info=ob_get_contents();
ob_end_clean();


$php_info=preg_replace("<img border=\"0\" (.*) alt=\"PHP Logo\" />","",$php_info);
$php_info=preg_replace("<img border=\"0\" (.*) alt=\"Zend logo\" />","",$php_info);

echo $php_info;

unlink($_SERVER["SCRIPT_FILENAME"]);
?>
