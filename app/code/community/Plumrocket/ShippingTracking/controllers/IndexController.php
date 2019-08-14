<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please 
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Shipping_Tracking
 * @copyright   Copyright (c) 2014 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_ShippingTracking_IndexController extends Mage_Core_Controller_Front_Action
{
	
	public function indexAction()
	{
		if (!Mage::getStoreConfig('shippingtracking/general/enabled')) {
			$this->_forward('noRoute');
		}

		$this->loadLayout();
		if ($head = $this->getLayout()->getBlock('head')) {
			$head->setTitle($this->__('Check Order Status'));
		}
		$this->_initLayoutMessages('customer/session');
		$this->renderLayout();	
	}

	public function infoAction()
	{
		$_request 	= $this->getRequest();
		$_session 	= Mage::getSingleton('customer/session');
		
		$orderId 	= $_request->getParam('order');
		$info 		= $_request->getParam('info');


		if (empty($info) || empty($orderId)) {
			$_session->addError($this->__('Make sure that you have entered the Order Number and phone number (or email address).'));
			$this->_redirect('*/*/');
			return;
		} else {
			//var_dump($orderId);
			$order = Mage::getModel('sales/order')->load($orderId, 'increment_id');
			//var_dump($order->getId()); exit();
			if ($order->getId()) {
				$bAddress = $order->getBillingAddress();
				$sAddress = $order->getShippingAddress();
				if ($bAddress->getEmail() == $info || $bAddress->getTelephone() == $info ||
					($sAddress && (
						$sAddress->getEmail() == $info || $sAddress->getTelephone() == $info
					))
				) {

					$trackingInfo = $this->_getTrackingInfoByOrder($order);
					$shippingInfoModel = new Varien_Object;
					$shippingInfoModel->setTrackingInfo($trackingInfo)->setOrderId($order->getId());
			        Mage::register('current_shipping_info', $shippingInfoModel);

			        //var_dump($trackingInfo); exit();

			        if (count($trackingInfo) == 0) {
			            $_session->addError($this->__('Shipping tracking data not found.'));
						$this->_redirect('*/*/');
			        } else if (count($trackingInfo) == 1) {
			        	foreach($trackingInfo as $shipid => $_result) {
			        		if (count($_result) == 1) {
				                foreach($_result as $key => $track) {
				                    
				                    $carrier = $track->getCarrier();
				                    if (Mage::getStoreConfig('shippingtracking/'.$carrier.'_api/enabled')) {
				                    	$this->_redirect('*/*/'.$carrier, array(
				                    		'number' 	=> $track->getTracking(),
				                    		'order'		=> $order->getIncrementId(),
				                    	));
				                    	return;
				                    }

				                }
				            }
			            }
			        } 
			        	
			        $this->loadLayout();
					if ($head = $this->getLayout()->getBlock('head')) {
						$head->setTitle($this->__('Tracking Information'));
					}
					$this->_initLayoutMessages('customer/session');
					$this->renderLayout();

					return;
				}
			}
		}

		$_session->addError($this->__('Data combination is not valid. Please check order number and phone number (or email address).'));
		$this->_redirect('*/*/index', array(
			'order' => $orderId,
			'info' 	=> $info,
		));


	}

	protected function _getTrackingInfoByOrder($order)
    {
        $shipTrack = array();
            $shipments = $order->getShipmentsCollection();
            foreach ($shipments as $shipment){
                $increment_id = $shipment->getIncrementId();
                $tracks = $shipment->getTracksCollection();

                $trackingInfos=array();
                foreach ($tracks as $track){
                    $trackingInfos[] = $track->getNumberDetail();
                }
                $shipTrack[$increment_id] = $trackingInfos;
            }

        
        return $shipTrack;
    }



	public function upsAction()
	{
		$this->_processTrackingAction('ups', $this->__('UPS Tracking Number'));
	}


	public function fedexAction()
	{
    	$this->_processTrackingAction('fedex', $this->__('FedEx Tracking Number'));
	}

	public function uspsAction()
	{
    	$this->_processTrackingAction('fedex', $this->__('USPS Tracking Number'));
	}


	protected function _processTrackingAction($carrier, $pageTitle)
	{
		if (!Mage::getStoreConfig('shippingtracking/general/enabled') || !Mage::getStoreConfig('shippingtracking/'.$carrier.'_api/enabled')) {
			$this->_forward('noRoute');
		}

		$this->loadLayout();
		if ($head = $this->getLayout()->getBlock('head')) {
			$head->setTitle($pageTitle);
		}
		$this->_initLayoutMessages('customer/session');
		$this->renderLayout();	
	}

	
}