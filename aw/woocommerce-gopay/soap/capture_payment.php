<?php
/**
 * Dokonceni platby na strane GoPay
 */

require_once('../config.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

try {
	
	// ID platby, kterou chcete dokoncit - vytvoreni pomoci metody createPreAutorizedPayment (viz gopay_soap.php)
	$paymentSessionId = 3000000001;
	GopaySoap::capturePayment($paymentSessionId,
							(float)GOID,
							SECURE_KEY);

	// platba korektne dokoncena

} catch (Exception $e) {
	
	if ($e->getMessage() == GopayHelper::CALL_RESULT_ACCEPTED) {
		// Dokonceni platby bylo zarazeno ke zpracovani. Po urcite dobe je nutne dotazat stav platby, zda je jiz platba dokoncena

	} else {
		// Osetreni chyby v pripade chyby pri dokoncovani platby
		exit;
	}
}
?>
