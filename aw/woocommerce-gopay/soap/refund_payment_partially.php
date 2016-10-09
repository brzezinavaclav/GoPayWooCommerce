<?php
/**
 * Castecne vraceni platby (refundace)
 */

require_once('../config.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

try {
	
	// ID platby, kterou chcete refundovat
	$paymentSessionId = 3000000001;

	$amount = 100;                       // castka, kterou chcete z platby refundovat - v halerich
	$description = "popis refundace";    // poznamka k refundaci

	/*
	 * Nacist data objednavky, zde z testovacich duvodu vse napevno primo v testovaci tride Order
	 * Upravte dle ulozeni vasich objednavek 
	 */
	$order = new Order();
	$order->loadByPaymentSessionId($paymentSessionId); // ! UPRAVTE !

	GopaySoap::refundPaymentPartially($paymentSessionId,
									$amount,
									$order->getCurrency(),
									$description,
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
