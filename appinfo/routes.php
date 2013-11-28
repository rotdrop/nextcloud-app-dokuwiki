<?php

OC::$CLASSPATH['DWEMBED\AuthHooks'] = OC_App::getAppPath("dokuwikiembed") . '/lib/auth.php';

$this->create('dokuwikirefresh', '/refresh')->post()->action('DWEMBED\AuthHooks', 'refresh');

?>