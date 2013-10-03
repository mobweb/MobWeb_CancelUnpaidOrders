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
		Mage::log(sprintf('Automatically cancelling %d orders', count($orders)), NULL, $this->logFile);
		foreach($orders AS $order) {
		    try{
		        $order = Mage::getModel('sales/order')->load($order->getId());
		        if($order->canCancel()) {
		            $order->cancel();
		            $order->addStatusHistoryComment('Order automatically canceled', $order->getStatus());
		        	$order->save();
		        }
		        else {
		        	// Unable to cancel order
					Mage::log(sprintf('Unable to cancel order: %d (Blocked by Magento)', $order->getRealOrderId()), NULL, $this->logFile);
		        }
		    }
		    catch(Exception $e){
	        	// Unable to cancel order
	        	Mage::log(sprintf('Unable to cancel order: %d (%s)', $order->getRealOrderId(), $e->getMessage()), NULL, $this->logFile);
		        continue;
		    }
		}
	}
}