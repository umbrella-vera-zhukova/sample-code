<?php

namespace SOME\ServiceBundle\Controller;

use SOME\CoreBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SOME\CoreBundle\Entity\General\Finance;

/**
 * Listener for paypal IPN requests
 * @Route("/svc/listener")
 */
class ListenerController extends Controller
{

    /**
     * Listener for paypal IPN requests on user make deposit
     * 
     * Non completed payments will not processed
     * 
     * @Route("/paypal-ipn",name="SOME_svc_paypal_ipn")
     */
    public function paypalIpnAction(Request $request)
    {
        //create the instance of IPN
        $ipn = \SOME\ServiceBundle\Payment\Paypal\Ipn::getInstance($this->container->getParameter('paypal'), $this->container->getParameter('kernel.logs_dir'));

        //verify the IPN
        if (FALSE !== ($ipn_data = $ipn->isVerified()))
        {
            $request->getContent();
            // validate the ipn data
            if ($this->_validatePaypalIPN($ipn_data))
            {
                $em = $this->getDoctrine()->getManager();

                // create an order
                $order = new Finance\PaypalOrder;
                $order
                        ->setItem(Finance\PaymentOrder::ITEM_DEPOSIT)
                        ->setOrderId($ipn_data['txn_id'])
                        ->setPaymentDate(new \DateTime($ipn_data['payment_date']))
                        ->setStatus($ipn_data['payment_status'])
                        ->setAmount($ipn_data['mc_gross'])
                        ->setFee($ipn_data['mc_fee'])
                ;
                $em->persist($order);
                // if account exists then create a transaction for him & associate order with it
                if ($account = $this->getDoctrine()->getRepository('SOMECoreBundle:General\Account')->findOneByEmail($ipn_data['custom']))
                {
                    $transaction = new Finance\AccountTransaction;
                    $transaction
                            ->setAccount($account)
                            ->setAmount($ipn_data['mc_gross'])
                            ->setDetails('Loaded by Paypal.')
                            ->setPaymentOrder($order)
                            ->setType(Finance\AccountTransaction::TYPE_DEBIT)

                    ;
                    $em->persist($transaction);
                }

                $em->flush();
                
                $this->_recalcAccountDeposit($account);
                
            }
        }

        return new Response(
                //content
                '',
                //status code
                200,
                //headers: guess a content-type
                array('Content-Type' => 'text/plain')
        );
    }

    /**
     * Listener for 2checkout approved payment requests on user make deposit
     * 
     * @Route("/2checkout-ins",name="SOME_svc_2checkout_ins")
     */
    public function twoCheckoutInsAction(Request $request)
    {
        $ins = \SOME\ServiceBundle\Payment\TwoCheckout\Ins::getInstance($this->container->getParameter('2checkout_secret_word'), $this->container->getParameter('kernel.logs_dir'));

        $params = $request->request->all();

        // verify the request
        if ($ins->isVerified($params))
        {
             // validate the request parameters
            if ($this->_validateTwoCheckoutINS($params))
            {
                $em = $this->getDoctrine()->getManager();

                // create an order
                $order = new Finance\TwoCheckoutOrder;
                $order
                        ->setItem(Finance\PaymentOrder::ITEM_DEPOSIT)
                        ->setOrderId($params['sale_id'])
                        ->setPaymentDate(new \DateTime($params['sale_date_placed']))
                        ->setStatus("Fraud status: {$params['fraud_status']}. Invoice status: {$params['invoice_status']}")
                        ->setAmount($params['invoice_list_amount'])
                ;
                $em->persist($order);
                // if account exists then create a transaction for him & associate order with it
                if ($account = $this->getDoctrine()->getRepository('SOMECoreBundle:General\Account')->findOneByEmail($this->_detectUsernameInItemName($params['item_name_1'])))
                {
                    $transaction = new Finance\AccountTransaction;
                    $transaction
                            ->setAccount($account)
                            ->setAmount($params['invoice_list_amount'])
                            ->setDetails('Loaded by 2Checkout.')
                            ->setPaymentOrder($order)
                            ->setType(Finance\AccountTransaction::TYPE_DEBIT)

                    ;
                    $em->persist($transaction);
                }

                $em->flush();
                
                $this->_recalcAccountDeposit($account);

            }
        }
        return new Response(
                //content
                '',
                //status code
                200,
                //headers: guess a content-type
                array('Content-Type' => 'text/plain')
        );
    }

    protected function _validatePaypalIPN($data)
    {
        /**
         * check the txn_type - should be web_accept for buy now button
         * @see https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/#id08CTB0S055Z
         */
        if ('web_accept' !== $data['txn_type'])
            return false;

        /**
         * was payment completed?
         */
        if ('completed' !== strtolower($data['payment_status']))
            return false;

        /**
         * maybe this order has already been created?
         */
        if ($this->getDoctrine()->getRepository('SOMECoreBundle:General\Finance\PaypalOrder')->findOneByOrderId($data['txn_id']))
            return false;

        /**
         * check the merchant id/email
         */
        if (strtolower($this->container->getParameter('paypal_merchant')) !== $data['business'])
            return false;

        /**
         * check item name
         */
        if ($this->container->getParameter('finance_deposit_name') !== $data['item_name'])
            return false;

        /**
         * check currency
         */
        if ($this->container->getParameter('finance_currency') !== $data['mc_currency'])
            return false;

        /**
         * check if custom data received (it should contain the user's username)
         */
        if (!isset($data['custom']))
            return false;

        /**
         * check if seller got non-zero amount
         */
        if ($data['mc_gross'] - $data['mc_fee'] == 0)
            return false;

        return true;
    }

    protected function _validateTwoCheckoutINS($data)
    {
        /**
         * check the message type - will accept payment on INVOICE_STATUS_CHANGED 
         * @see https://www.2checkout.com/documentation/notifications/invoice-status-changed
         */
        if ('INVOICE_STATUS_CHANGED' !== $data['message_type'])
            return false;

        /**
         * was payment completed? - fraud status must be = pass and invoice_status must be = deposited
         */
        if ('pass' !== strtolower($data['fraud_status']) || 'deposited' !== $data['invoice_status'])
            return false;

        /**
         * maybe this order has already been created?
         */
        if ($this->getDoctrine()->getRepository('SOMECoreBundle:General\Finance\TwoCheckoutOrder')->findOneByOrderId($data['sale_id']))
            return false;

        /**
         * check the seller id
         */
        if (strtolower($this->container->getParameter('2checkout_sid')) !== $data['vendor_id'])
            return false;

        /**
         * check item name for being correct & username for existance. For 2checkout we have to pass a username inside item name so that it can be retrieved from INS message.
         * So, the item name is like: '%finance_deposit_name% username'. E.g.: 'SOME deposit account@account.com'
         */
        if (! $this->_detectUsernameInItemName($data['item_name_1']))
            return false;

        /**
         * check seller currency
         */
        if ($this->container->getParameter('finance_currency') !== $data['list_currency'])
            return false;

        return true;
    }
    
    protected function _detectUsernameInItemName($item_name)
    {
        preg_match('/^' . $this->container->getParameter('finance_deposit_name') . '\s(.*)$/', $item_name, $matches);
        if(empty($matches))
            return null;
        
        if(isset($matches[1]))
            return $matches[1];
        
        return null;
    }
    
    /**
     * Launch a job to recalculate user deposit
     */
    protected function _recalcAccountDeposit($account)
    {
        // attach send email job
        \SOME\ServiceBundle\PHPResque\Job\Finance\RecalcAccountDepositJob::attach(array(
            'id' => $account->getId(),
        ));
    }

}
