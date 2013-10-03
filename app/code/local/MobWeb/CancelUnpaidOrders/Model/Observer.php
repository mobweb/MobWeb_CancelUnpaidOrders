<?php

class MobWeb_CancelUnpaidOrders_Model_Observer
{
	public function cancelUnpaidOrders()
	{
		// Get the settings
		$this->cancelPaymentMethods = explode(',', Mage::getStoreConfig('cancelunpaidorders/settings/payment_methods'));
		$this->cancelStatus = explode(',', Mage::getStoreConfig('cancelunpaidorders/settings/order_status'));
		$this->expirationTime = Mage::getStoreConfig('cancelunpaidorders/settings/expiration_time_days')*24+Mage::getStoreConfig('cancelunpaidorders/settings/expiration_time_hours')*60*60;
		$this->logFile = 'system.log';

		// Check the settings
		if(!count($this->cancelPaymentMethods) || !count($this->cancelStatus)) {
			Mage::log('Unable to cancel orders automatically, required settings missing', NULL, $this->logFile);
			return;
		}

		// Get any orders that match the settings
		$orders = Mage::getModel('sales/order')->getCollection();
        $orders->addAttributeToFilter('status', array('in' => $this->cancelStatus));
        $orders->addAttributeToFilter('created_at', array('lt' => date('Y-m-d H:i:s', time() - $this->expirationTime)));
        $orders->getSelect()->join(array('p' => $orders->getResource()->getTable('sales/order_payment')), 'p.parent_id = main_table.entity_id', array());
        $orders->addFieldToFilter('method', array('in' => $this->cancelPaymentMethods));

		// Cancel the orders
		Mage::log(sprintf('Cancelling %d orders.', count($orders)), NULL, $this->logFile);
		foreach($orders AS $order) {
			Mage::log(sprintf('Trying to cancel order %s.', $order->getRealOrderId()), NULL, $this->logFile);
		    try{
				// Check if the order has any invoices
				$invoices = $order->getInvoiceCollection();
				if(count($invoices)) {
					// Cancel all these invoices
					Mage::log(sprintf('Cancelling %d invoices for order %s.', count($invoices), $order->getRealOrderId()), NULL, $this->logFile);
				    foreach($invoices AS $invoice) {
						Mage::log(sprintf('Trying to cancel invoice %s.', $invoice->getId()), NULL, $this->logFile);
				    	// Check if the invoice is still open
				    	if($invoice->getState() == $invoice::STATE_OPEN) {
							// Cancel the invoice
					    	if($invoice->canCancel()) {
					    		$invoice->cancel();
					    		$invoice->save();
					    		Mage::log(sprintf('Invoice %s canncelled.', $invoice->getId()), NULL, $this->logFile);
					    	} else {
					    		// Unable to cancel invoice
					    		Mage::log(sprintf('Unable to cancel invoice %d: Reason unknown', $invoice->getId(), $order->getRealOrderId()), NULL, $this->logFile);
					    	}
					    } else {
					    	// Unable to cancel invoice, it isn't open anymore
					    	Mage::log(sprintf('Unable to cancel invoice %d: Invoice not open anymore. Aborting cancel attempt for order %s.', $invoice->getId(), $order->getRealOrderId()), NULL, $this->logFile);

					    	// Since an uncancelled invoice also means we can't
					    	// cancel the current order, abort the cancelling
					    	// of the order
					    	continue 2;
					    }
				    }
			    }

			    // Once all the invoices have been cancelled, also cancel
			    // the order
		        $order = Mage::getModel('sales/order')->load($order->getId());
		        if($order->canCancel()) {
		            $order->cancel();
		            $order->addStatusHistoryComment('Order automatically canceled!', $order->getStatus());
		        	$order->save();
		        	Mage::log(sprintf('Order %d cancelled.', $order->getRealOrderId()), NULL, $this->logFile);
		        }
		        else {
		        	// Unable to cancel order
					Mage::log(sprintf('Unable to cancel order: %d: Reason unknown.', $order->getRealOrderId()), NULL, $this->logFile);
		        }
		    }
		    catch(Exception $e){
	        	// Unable to cancel order
	        	Mage::log(sprintf('Unable to cancel order: %d: %s.', $order->getRealOrderId(), $e->getMessage()), NULL, $this->logFile);
		        continue;
		    }
		}
	}
}