<?php
/**
 * Provede overeni zaplacenosti objednavky po zpetnem presmerovani z platebni brany
 * 
 */
require_once('../config.php');
require_once('../order.php');

require_once('../../api/gopay_helper.php');
require_once('../../api/gopay_soap.php');

/*
 * Parametry obsazene v redirectu po potvrzeni / zruseni platby, predavane od GoPay e-shopu
 */
$returnedPaymentSessionId = $_GET['paymentSessionId'];
$returnedGoId = $_GET['targetGoId'];
$returnedOrderNumber = $_GET['orderNumber'];
$returnedEncryptedSignature = $_GET['encryptedSignature'];

/*
 * Nacist data objednavky dle prichoziho paymentSessionId, zde z testovacich duvodu vse primo v testovaci tride Order
 * Upravte dle ulozeni vasich objednavek
 */
$order = new Order();
$order->loadByPaymentSessionId($returnedPaymentSessionId); // ! UPRAVTE !

/*
 * Kontrola validity parametru v redirectu, opatreni proti podvrzeni potvrzeni / zruseni platby
 */
try {
	GopayHelper::checkPaymentIdentity(
				(float)$returnedGoId,
				(float)$returnedPaymentSessionId,
				null,
				$returnedOrderNumber,
				$returnedEncryptedSignature,
				(float)GOID,
				$order->getOrderNumber(),
				SECURE_KEY);

	/*
	 * Kontrola zaplacenosti objednavky na serveru GoPay
	 */
	$result = GopaySoap::isPaymentDone(
				(float)$returnedPaymentSessionId,
				(float)GOID,
				$order->getOrderNumber(),
				(int)$order->getTotalPrice(),
				$order->getCurrency(),
				$order->getProductName(),
				SECURE_KEY);

	if ($result["sessionState"] == GopayHelper::PAID ) {
		/*
		 * Zpracovat pouze objednavku, ktera jeste nebyla zaplacena 
		 */
		if ($order->getState() != GopayHelper::PAID) {
	
			/*
			 *  Zpracovani objednavky  ! UPRAVTE !
			 */
			$order->processPayment();
		}
	
		/*
		 * Presmerovani na prezentaci uspesne platby
		 */
		$location = SUCCESS_URL;
	
	} else if ( $result["sessionState"] == GopayHelper::PAYMENT_METHOD_CHOSEN) {
		/* Platba ceka na zaplaceni */
		$location = SUCCESS_URL;
	
		
	} else if ( $result["sessionState"] == GopayHelper::CREATED) {
		/* Platba nebyla zaplacena */
		$location = FAILED_URL;
	
	} else if ( $result["sessionState"] == GopayHelper::CANCELED) {
		/* Platba byla zrusena objednavajicim */
		$order->cancelPayment();
		$location = FAILED_URL;
		
	} else if ( $result["sessionState"] == GopayHelper::TIMEOUTED) {
		/* Platnost platby vyprsela  */
		$order->timeoutPayment();
		$location = FAILED_URL;
		
	} else if ( $result["sessionState"] == GopayHelper::AUTHORIZED) {
		/* Platba byla autorizovana, ceka se na dokonceni  */
		$order->autorizePayment();
		$location = SUCCESS_URL;
	
	} else if ( $result["sessionState"] == GopayHelper::REFUNDED) {
		/* Platba byla vracena - refundovana  */
		$order->refundPayment();
		$location = SUCCESS_URL;
	
	} else {
		/* Chyba ve stavu platby */
		$location = FAILED_URL;
		$result["sessionState"] = GopayHelper::FAILED;
		
	}

	header('Location: ' . $location . "?sessionState=" . $result["sessionState"] . "&sessionSubState=" . $result["sessionSubState"]);
	exit;

} catch (Exception $e) {
	/*
	 * Nevalidni informace z redirectu
	 */
	header('Location: ' . FAILED_URL . "?sessionState=" . GopayHelper::FAILED);
	exit;
	
}
?>