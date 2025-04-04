<?php
namespace Svaapta\AddToCartRestrict\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Svaapta\AddToCartRestrict\Logger\Logger;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Execute observer to log order details after order confirmation
     *
     * 
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get the order object from the event
        $order = $observer->getEvent()->getOrder();

        if ($order) {
            // Build a log message with order details
            $message = sprintf(
                "Order Confirmation: Order #%s placed. Customer: %s. Total: %s %s. Items: %s",
                $order->getIncrementId(),
                $order->getCustomerEmail(),
                $order->getGrandTotal(),
                $order->getBaseCurrencyCode(),
                $this->getOrderItems($order)
            );

            // Log the order details to the custom log file
            $this->logger->info($message);
        }
    }

    /**
     * Retrieve order items as a formatted string.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    protected function getOrderItems($order)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = sprintf('%s (Qty: %s)', $item->getName(), $item->getQtyOrdered());
        }
        return implode(', ', $items);
    }
}
