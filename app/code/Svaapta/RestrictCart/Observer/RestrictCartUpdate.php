<?php
namespace Svaapta\RestrictCart\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Svaapta\RestrictCart\Logger\Logger;

class RestrictCartUpdate implements ObserverInterface
{
    protected $logger;
    protected $messageManager;

    // Add a static flag to prevent recursion
    private static $isProcessing = false;

    public function __construct(
        Logger $logger,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    public function execute(Observer $observer)
    {

        // Check if the observer is already processing to prevent recursion
        if (self::$isProcessing) {
            return;
        }

        // Set the flag to true to indicate processing
        self::$isProcessing = true;

        $this->logger->info('Restricted Cart Update Trigger.');


        // Get the quote item and user details
        $item = $observer->getEvent()->getData('item');
        $quote = $item->getQuote();
        $customer = $quote->getCustomer();

        // Log the item and customer details
        $this->logger->info('Item Data: ' . print_r($item->getData(), true));

        // Calculate the total quantity in the cart
        $totalQty = 0;
        foreach ($quote->getAllItems() as $cartItem) {
            $totalQty += $cartItem->getQty();
        }
        
        // Check if the user is restricted
        if ($this->isUserRestricted($customer)) {

            $this->logger->info('Total Quantity in Cart: ' . $totalQty);

            // Check if the total quantity exceeds the limit
            $maxAllowedQty = 5; // Maximum allowed quantity
            if ($totalQty > $maxAllowedQty) {
                // Revert the item quantity to its original value
                $originalQty = $item->getOrigData('qty');
                $item->setQty($originalQty);
    
                // // Add an error message
                // $this->messageManager->addErrorMessage(__('You can only have a maximum of %1 items in your cart.', $maxAllowedQty));
            }
        }
        

        // Reset the flag after processing
        self::$isProcessing = false;
    }

    /**
     * Check if the user is restricted
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return bool
     */
    private function isUserRestricted($customer)
    {
        // Add your custom logic to check if the user is restricted
        // Example: Check customer group or custom attribute
        $restrictedGroupIds = [0,1]; // Example restricted group IDs
        return in_array($customer->getGroupId(), $restrictedGroupIds);
    }
}