<?php
/**
 * Vraceni platby (refundace)
 */

require_once('../config.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

try {
	
	// ID platby, kterou chcete refundovat
	$paymentSessionId = 3000000001;
	GopaySoap::refundPayment($paymentSessionId,
							(float)GOID,
							SECURE_KEY);

} catch (Exception $e) {
	
	if ($e->getMessage() == GopayHelper::CALL_RESULT_ACCEPTED) {
		// Vraceni platby bylo zarazeno ke zpracovani. Po urcite dobe je nutne dotazat stav platby, zda je jiz platba dokoncena

	} else {
		// Osetreni chyby v pripade chybneho zruseni opakovani platby
		exit;
	}

}
?>
