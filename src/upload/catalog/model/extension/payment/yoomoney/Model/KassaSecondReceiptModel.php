<?php


namespace YooMoneyModule\Model;

use Config;
use Exception;
use Log;
use ModelExtensionPaymentYoomoney;
use YooKassa\Client;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Common\Exceptions\BadApiRequestException;
use YooKassa\Common\Exceptions\ExtensionNotFoundException;
use YooKassa\Common\Exceptions\ForbiddenException;
use YooKassa\Common\Exceptions\InternalServerError;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Common\Exceptions\ResponseProcessingException;
use YooKassa\Common\Exceptions\TooManyRequestsException;
use YooKassa\Common\Exceptions\UnauthorizedException;
use YooKassa\Model\PaymentInterface;
use YooKassa\Model\PaymentStatus;
use YooKassa\Model\Receipt\PaymentMode;
use YooKassa\Model\ReceiptCustomer;
use YooKassa\Model\ReceiptItem;
use YooKassa\Model\ReceiptType;
use YooKassa\Model\Settlement;
use YooKassa\Request\Receipts\CreatePostReceiptRequest;
use YooKassa\Request\Receipts\PaymentReceiptResponse;
use YooKassa\Request\Receipts\ReceiptResponseInterface;
use YooKassa\Request\Receipts\ReceiptResponseItem;
use YooKassa\Request\Receipts\ReceiptResponseItemInterface;

class KassaSecondReceiptModel
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var int|string
     */
    private $orderId;

    /**
     * @var array
     */
    private $orderInfo;

    /**
     * @var PaymentInterface
     */
    private $paymentInfo;

    /**
     * @var KassaModel
     */
    private $kassaModel;

    /**
     * @var string
     */
    private $settlementsSum;


    /**
     * @var Client
     */
    protected $client;

    /**
     * KassaSecondReceiptModel constructor.
     * @param $config
     * @param $session
     * @param $orderId
     * @param $paymentInfo
     * @param $orderInfo
     */
    public function __construct($config, $session, $orderId, $paymentInfo, $orderInfo)
    {
        $this->config      = $config;
        $this->session     = $session;
        $this->orderId     = $orderId;
        $this->orderInfo   = $orderInfo;
        $this->paymentInfo = $paymentInfo;
        $this->kassaModel  = new KassaModel($config);
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new \YooKassa\Client();
            $this->client->setAuth(
                $this->kassaModel->getShopId(),
                $this->kassaModel->getPassword()
            );

            $userAgent = $this->client->getApiClient()->getUserAgent();
            $userAgent->setCms('OpenCart', VERSION);
            $userAgent->setModule('YooMoney', ModelExtensionPaymentYoomoney::MODULE_VERSION);
        }

        return $this->client;
    }

    /**
     * @param $statusId
     * @return bool
     */
    public function sendSecondReceipt($statusId)
    {
        $this->log("info", "Hook send second receipt");

        if (!$this->isNeedSecondReceipt($statusId)) {
            $this->log("info", "Second receipt isn't need");
            return false;
        } elseif (!$this->isPaymentInfoValid($this->paymentInfo)) {
            $this->log("error", "Invalid paymentInfo");
            return false;
        } elseif (empty($this->orderInfo)) {
            $this->log("error", "Invalid orderInfo orderId = " . $this->orderId);
            return false;
        }

        try {
            $lastReceipt = $this->getLastReceipt($this->paymentInfo->getid());
        } catch (Exception $e) {
            $this->log("error", "Get last receipt error: " . $e->getMessage());
            return false;
        }

        if (empty($lastReceipt)) {
            return false;
        }

        $receiptRequest = $this->buildSecondReceipt($lastReceipt, $this->paymentInfo, $this->orderInfo);

        if (!empty($receiptRequest)) {

            $this->log("info", "Second receipt request data: " . json_encode($receiptRequest->jsonSerialize()));

            try {
                $response = $this->getClient()->createReceipt($receiptRequest);
            } catch (\Exception $e) {
                $this->log("error", "Request second receipt error: " . $e->getMessage());
                return false;
            }

            $this->log("info", "Request second receipt result: " . json_encode($response->jsonSerialize()));
            $this->generateSettlementsAmountSum($response);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getSettlementsSum()
    {
        return $this->settlementsSum;
    }

    /**
     * @param ReceiptResponseInterface $response
     */
    private function generateSettlementsAmountSum($response)
    {
        $amount = 0;

        foreach ($response->getSettlements() as $settlement) {
            $amount += $settlement->getAmount()->getIntegerValue();
        }

        $this->settlementsSum = number_format($amount / 100.0, 2, '.', ' ');
    }

    /**
     * @param ReceiptResponseInterface $lastReceipt
     * @param PaymentInterface $paymentInfo
     * @param $orderInfo
     *
     * @return void|CreatePostReceiptRequest
     */
    private function buildSecondReceipt($lastReceipt, $paymentInfo, $orderInfo)
    {
        if ($lastReceipt instanceof ReceiptResponseInterface) {
            if ($lastReceipt->getType() === "refund") {
                return;
            }

            $resendItems = $this->getResendItems($lastReceipt->getItems());

            if (count($resendItems['items']) < 1) {
                $this->log("info", "Second receipt isn't need");
                return;
            }

            try {
                $receiptBuilder = CreatePostReceiptRequest::builder();
                $customer = $this->getReceiptCustomer($orderInfo);

                if (empty($customer)) {
                    $this->log("error", "Need customer phone or email for second receipt");
                    return;
                }

                $receiptBuilder->setObjectId($paymentInfo->getId())
                    ->setType(ReceiptType::PAYMENT)
                    ->setItems($resendItems['items'])
                    ->setSettlements(
                        array(
                            new Settlement(
                                array(
                                    'type' => 'prepayment',
                                    'amount' => array(
                                        'value' => $resendItems['amount'],
                                        'currency' => 'RUB',
                                    ),
                                )
                            ),
                        )
                    )
                    ->setCustomer($customer)
                    ->setSend(true);

                return $receiptBuilder->build();
            } catch (\Exception $e) {
                $this->log("error", $e->getMessage() . ". Property name:". $e->getProperty());
            }
        } else {
            $this->log("info", "Second receipt isn't need");
        }
    }

    /**
     * @param PaymentInterface $paymentInfo
     * @return bool
     */
    private function isPaymentInfoValid($paymentInfo)
    {
        if (empty($paymentInfo)) {
            $this->log("error", "Fail send second receipt paymentInfo is null: " . print_r($paymentInfo, true));
            return false;
        }

        if ($paymentInfo->getStatus() !== PaymentStatus::SUCCEEDED) {
            $this->log("error", "Fail send second receipt payment have incorrect status: " . $paymentInfo->getStatus());
            return false;
        }

        return true;
    }

    /**
     * @param $orderInfo
     * @return ReceiptCustomer
     */
    private function getReceiptCustomer($orderInfo)
    {
        $customerData = array();

        if (isset($orderInfo['email']) && !empty($orderInfo['email'])) {
            $customerData['email'] = $orderInfo['email'];
        }

        if (isset($orderInfo['telephone']) && !empty($orderInfo['telephone'])) {
            $customerData['phone'] = preg_replace('/\D/', '', $orderInfo['telephone']);
        }


        return new ReceiptCustomer($customerData);
    }

    /**
     * @param $statusId
     * @return bool
     */
    private function isNeedSecondReceipt($statusId)
    {
        if (!$this->kassaModel->isSendReceipt()) {
            return false;
        } elseif (!$this->kassaModel->isSecondReceipt()) {
            return false;
        } elseif ($statusId != $this->kassaModel->getSecondReceiptStatus()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $paymentId
     * @return mixed|ReceiptResponseInterface
     *
     * @throws ApiException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws ForbiddenException
     * @throws InternalServerError
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    private function getLastReceipt($paymentId)
    {
        $paymentReceipts = $this->getClient()->getReceipts(array('payment_id' => $paymentId))->getItems();
        $lastPaymentReceipt = array_shift($paymentReceipts);

        if ($lastPaymentReceipt) {
            $refundReceipts = $this->getRefundReceipts($paymentId);
            if (count($refundReceipts)) {
                return $this->createNewPaymentReceipt($lastPaymentReceipt, $refundReceipts);
            } else {
                return $lastPaymentReceipt;
            }
        }

        return null;
    }

    /**
     * @param string $paymentId
     * @return ReceiptResponseInterface[]
     *
     * @throws ApiException
     * @throws BadApiRequestException
     * @throws ExtensionNotFoundException
     * @throws ForbiddenException
     * @throws InternalServerError
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    private function getRefundReceipts($paymentId)
    {
        $refundReceipts = array();
        $refunds = $this->getClient()->getRefunds(array('payment_id' => $paymentId))->getItems();
        foreach ($refunds as $refund) {
            $refundReceipts = array_merge(
                $refundReceipts,
                $this->getClient()->getReceipts(array('refund_id' => $refund->getId()))->getItems()
            );
        }
        return $refundReceipts;
    }

    /**
     * @param ReceiptResponseInterface $lastPaymentReceipt
     * @param ReceiptResponseInterface[] $refundReceipts
     * @return PaymentReceiptResponse
     *
     * @throws Exception
     */
    private function createNewPaymentReceipt($lastPaymentReceipt, $refundReceipts)
    {
        $newReceiptItems = array();
        foreach ($lastPaymentReceipt->getItems() as $paymentReceiptItem) {
            $newReceiptItem = new ReceiptResponseItem($paymentReceiptItem->jsonSerialize());
            $newQuantity = $newReceiptItem->getQuantity();
            foreach ($refundReceipts as $refundReceipt) {
                foreach ($refundReceipt->getItems() as $refundReceiptItem) {
                    if ($paymentReceiptItem->getDescription() == $refundReceiptItem->getDescription() &&
                        $paymentReceiptItem->getPrice()->getValue() == $refundReceiptItem->getPrice()->getValue()) {
                        $newQuantity -= $refundReceiptItem->getQuantity();
                    }
                }
            }
            if ($newQuantity > 0) {
                $newReceiptItem->setQuantity($newQuantity);
                $newReceiptItems[] = $newReceiptItem->jsonSerialize();
            }
        }
        /** @var PaymentReceiptResponse $lastPaymentReceipt */
        return new PaymentReceiptResponse([
            'id' => $lastPaymentReceipt->getId(),
            'payment_id' => $lastPaymentReceipt->getPaymentId(),
            'type' => $lastPaymentReceipt->getType(),
            'status' => $lastPaymentReceipt->getStatus(),
            'items' => $newReceiptItems,
        ]);
    }

    /**
     * @param ReceiptResponseItemInterface[] $items
     *
     * @return array
     */
    private function getResendItems($items)
    {
        $resendItems = array(
            'items'  => array(),
            'amount' => 0,
        );

        foreach ($items as $item) {
            if ($item->getPaymentMode() === PaymentMode::FULL_PREPAYMENT) {
                $item->setPaymentMode(PaymentMode::FULL_PAYMENT);
                $resendItems['items'][] = new ReceiptItem($item->jsonSerialize());
                $resendItems['amount'] += $item->getAmount() / 100.0;
            }
        }

        return $resendItems;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, $context = array())
    {
        if ($this->kassaModel->getDebugLog()) {
            $log     = new Log('yoomoney.log');
            $search  = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[]  = '{'.$key.'}';
                    $replace[] = (is_array($value)||is_object($value)) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                }
            }
            $sessionId = $this->session->getId();
            $userId    = 0;
            if (isset($this->session->data['user_id'])) {
                $userId = $this->session->data['user_id'];
            }
            if (empty($search)) {
                $log->write('['.$level.'] ['.$userId.'] ['.$sessionId.'] - '.$message);
            } else {
                foreach ($search as $object) {
                    if (stripos($message, $object) === false) {
                        $label = trim($object, "{}");
                        $message .= " \n{$label}: {$object}";
                    }
                }
                $log->write(
                    '['.$level.'] ['.$userId.'] ['.$sessionId.'] - '
                    .str_replace($search, $replace, $message)
                );
            }
        }
    }
}