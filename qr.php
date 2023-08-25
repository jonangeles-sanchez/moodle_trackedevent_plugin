<?php

require(__DIR__.'/../../config.php');
include('lib/phpqrcode/qrlib.php');

$qrid = required_param('qrid', PARAM_INT);
$eventid = required_param('eventid', PARAM_INT);

QRcode::png($CFG->wwwroot.'/mod/trackedevent/checkin.php?eventid='.$eventid.'&qrid='.$qrid, false,
	QR_ECLEVEL_L, 10);

?>
