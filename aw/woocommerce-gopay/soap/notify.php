<?php
/**
 * Provede overeni zaplacenosti objednavky po prichodu http notifikace. 
 */
require_once('../config.php');
require_once('../order.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

/*
 * Parametry obsazene v notifikaci platby, predavane od GoPay e-shopu
 */
$returnedPaymentSessionId = $_GET['paymentSessionId'];
$returnedParentPaymentSessionId = $_GET['parentPaymentSessionId'];
$returnedGoId = $_GET['targetGoId'];
$returnedOrderNumber = $_GET['orderNumber'];
$returnedEncryptedSignature = $_GET['encryptedSignature'];

/*
 * Nacist data objednavky dle prichoziho paymentSessionId, 
 * zde z testovacich duvodu vse primo v testovaci tride Order
 * Upravte dle ulozeni vasich objednavek
 */
$order = new Order();

if (empty($returnedParentPaymentSessionId)) {
	// notifikace o bezne platbe
	$order->loadByPaymentSessionId($returnedPaymentSessionId); // ! UPRAVTE !

} else {
	// notifikace o rekurentni platbe
	$order->loadByPaymentSessionId($returnedParentPaymentSessionId); // ! UPRAVTE !
	
}

/*
 * Kontrola validity parametru v http notifikaci, opatreni proti podvrzeni potvrzeni platby (notifikace)
 */
try {

	GopayHelper::checkPaymentIdentity(
				(float)$returnedGoId,
				(float)$returnedPaymentSessionId,
				(float)$returnedParentPaymentSessionId,
				$returnedOrderNumber,
				$returnedEncryptedSignature,
				(float)GOID,
				$order->getOrderNumber(),
				SECURE_KEY);

	/*
	 * Kontrola zaplacenosti objednavky na strane GoPay
	 */
	$result = GopaySoap::isPaymentDone(
									(float)$returnedPaymentSessionId,
									(float)GOID,
									$order->getOrderNumber(),
									(int)$order->getTotalPrice(),
									$order->getCurrency(),
									$order->getProductName(),
									SECURE_KEY);

	if ($result["sessionState"] == GopayHelper::PAID) {
		/*
		 * Zpracovat pouze objednavku, ktera jeste nebyla zaplacena 
		 */
		if (empty($returnedParentPaymentSessionId)) {
			// notifikace o bezne platbe

			if ($order->getState() != GopayHelper::PAID) {
	
				/*
				 *  Zpracovani objednavky  ! UPRAVTE !
				 */
				$order->processPayment();
			}
	
		} else {
			// notifikace o rekurentni platbe

			/*
			 * Je potreba kontrolovat, jestli jiz toto paymentSessionId neni zaplaceno, aby pri 
			 * opakovane notifikaci nedoslo k duplicitnimu zaznamu o zaplaceni 
			 * a nasledne zaznamenat $returnedPaymentSessionId pro kontroly u dalsich opakovanych plateb
			 */
			if ($order->isPaidRecurrentPayment($returnedPaymentSessionId) != true) {
	
				/*
				 *  pridani paymentSessionId do seznamu uhrazenych opakovanych plateb
				 */
				$order->addPaidRecurrentPayment($returnedPaymentSessionId);
			}

		}
	
	} else if ( $result["sessionState"] == GopayHelper::CANCELED) {
		/* Platba byla zrusena objednavajicim */
		$order->cancelPayment();
	
	} else if ( $result["sessionState"] == GopayHelper::TIMEOUTED) {
		/* Platnost platby vyprsela  */
		$order->timeoutPayment();
	
	} else if ( $result["sessionState"] == GopayHelper::REFUNDED) {
		/* Platba byla vracena - refundovana */
		$order->refundePayment();
	
	} else if ( $result["sessionState"] == GopayHelper::AUTHORIZED) {
		/* Platba byla autorizovana, ceka se na dokonceni  */
		$order->autorizePayment();
	
	} else {
		header("HTTP/1.1 500 Internal Server Error");
		exit(0);
	
	}

} catch (Exception $e) {
	/*
	 * Nevalidni informace z http notifikaci - prevdepodobne pokus o podvodne zaslani notifikace
	 */
	header("HTTP/1.1 500 Internal Server Error");
	exit(0);
}
?>