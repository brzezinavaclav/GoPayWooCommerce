<?php
/**
 * Vytvoreni opakovane platby na strane GoPay
 */

require_once('../config.php');
require_once('../order.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

/*
 * Nacist data objednavky, zde z testovacich duvodu vse napevno primo v testovaci tride Order
 * Upravte dle ulozeni vasich objednavek 
 */
$order = new Order();
$order->load(); // ! UPRAVTE !

try {
	
	// ID platby (rodicovske), ke ktere ma byt vytvorena opakovana platba. Rodicovska platba je vytvorena pomoci metody createRecurrentPayment (viz gopay_soap.php)
	$paymentSessionId = 3000000001;
	
	$recurrentOrderNumber = $order->getOrderNumber() + 1;
	$recurrentTotalPrice = $order->getTotalPrice() + 10;
	$recurrentCurrency = $order->getCurrency();
	$recurrentProductName = $order->getProductName();
	
	GopaySoap::performRecurrence($paymentSessionId,
								$recurrentOrderNumber,
								(int)$recurrentTotalPrice,
								$recurrentCurrency,
								$recurrentProductName,
								(float)GOID,
								SECURE_KEY);
	
	// opakovana platba vytvorena
	
} catch (Exception $e) {
	
	if ($e->getMessage() == GopayHelper::CALL_RESULT_ACCEPTED) {
		// Vytvoreni opakovane platby bylo zarazeno ke zpracovani. Po urcite dobe je nutne dotazat stav platby, zda je jiz platba dokoncena

	} else {
		// Osetreni chyby v pripade chybneho vytvoreni opakovane platby
		exit;
	}

}
?>
