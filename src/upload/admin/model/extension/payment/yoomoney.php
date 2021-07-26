<?php

class ModelExtensionPaymentYoomoney extends Model
{
    /**
     * string
     */
    const MODULE_VERSION = '2.1.3';
    const YOOMONEY_EVENT_SECOND_RECEIPT_CODE = 'yoomoney_second_receipt_trigger';

    private $kassaModel;
    private $walletModel;
    private $client;
    private $repository = 'yoomoney/cms-opencart3';
    private $backupDirectory = 'yoomoney/backup';
    private $versionDirectory = 'yoomoney/updates';
    private $downloadDirectory = 'yoomoney';

    public function install()
    {
        $this->log('info', 'install yoomoney module');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'yoomoney_payment` (
                `order_id`          INTEGER  NOT NULL,
                `payment_id`        CHAR(36) NOT NULL,
                `status`            ENUM(\'pending\', \'waiting_for_capture\', \'succeeded\', \'canceled\') NOT NULL,
                `amount`            DECIMAL(11, 2) NOT NULL,
                `currency`          CHAR(3) NOT NULL,
                `payment_method_id` CHAR(36) NOT NULL,
                `paid`              ENUM(\'Y\', \'N\') NOT NULL,
                `created_at`        DATETIME NOT NULL,
                `captured_at`       DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                `receipt`           TEXT DEFAULT NULL,

                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_pk` PRIMARY KEY (`order_id`),
                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_unq_payment_id` UNIQUE (`payment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'yoomoney_refunds` (
                `refund_id`         CHAR(36) NOT NULL,
                `order_id`          INTEGER  NOT NULL,
                `payment_id`        CHAR(36) NOT NULL,
                `status`            ENUM(\'pending\', \'succeeded\', \'canceled\') NOT NULL,
                `amount`            DECIMAL(11, 2) NOT NULL,
                `currency`          CHAR(3) NOT NULL,
                `created_at`        DATETIME NOT NULL,
                `authorized_at`     DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                INDEX `'.DB_PREFIX.'yoomoney_refunds_idx_order_id` (`order_id`),
                INDEX `'.DB_PREFIX.'yoomoney_refunds_idx_payment_id` (`payment_id`),
                CONSTRAINT `'.DB_PREFIX.'yoomoney_refunds_pk` PRIMARY KEY (`refund_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');

        $this->db->query('
            CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'yoomoney_product_properties` (
                `product_id`        INTEGER  NOT NULL,
                `payment_subject`   VARCHAR(256),
                `payment_mode`      VARCHAR(256),
                                
                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_pk` PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');
    }

    public function uninstall()
    {
        $this->log('info', 'uninstall yoomoney module');
    }

    public function log($level, $message, $context = null)
    {
        if ($this->getKassaModel()->getDebugLog()) {
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

    /**
     * устанавливает event для отправки второго чека
     */
    public function installEventForSecondReceipt()
    {
        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode(self::YOOMONEY_EVENT_SECOND_RECEIPT_CODE);
        $this->model_setting_event->addEvent(
            self::YOOMONEY_EVENT_SECOND_RECEIPT_CODE,
            "catalog/model/checkout/order/addOrderHistory/after",
            "extension/payment/yoomoney/hookOrderStatusChange"
        );
    }

    /**
     * @param int $orderId
     *
     * @return string|null
     */
    public function findPaymentIdByOrderId($orderId)
    {
        $sql       = 'SELECT `payment_id` FROM `'.DB_PREFIX.'yoomoney_payment` WHERE `order_id` = '.(int)$orderId;
        $resultSet = $this->db->query($sql);
        if (empty($resultSet) || empty($resultSet->num_rows)) {
            return null;
        }

        return $resultSet->row['payment_id'];
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function findPayments($offset = 0, $limit = 0)
    {
        $res = $this->db->query('SELECT * FROM `'.DB_PREFIX.'yoomoney_payment` ORDER BY `order_id` DESC LIMIT '.(int)$offset.', '.(int)$limit);
        if ($res->num_rows) {
            return $res->rows;
        }

        return array();
    }

    /**
     * @return int
     */
    public function countPayments()
    {
        $res = $this->db->query('SELECT COUNT(*) AS `count` FROM `'.DB_PREFIX.'yoomoney_payment`');
        if ($res->num_rows) {
            return $res->row['count'];
        }

        return 0;
    }

    /**
     * @param $payments
     *
     * @return \YooKassa\Model\PaymentInterface[]
     */
    public function updatePaymentsStatuses($payments)
    {
        $result = array();

        $client   = $this->getClient();
        $statuses = array(
            \YooKassa\Model\PaymentStatus::PENDING,
            \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE,
        );
        foreach ($payments as $payment) {
            if (in_array($payment['status'], $statuses)) {
                try {
                    $paymentObject = $client->getPaymentInfo($payment['payment_id']);
                    if ($paymentObject === null) {
                        $this->updatePaymentStatus($payment['payment_id'],
                            \YooKassa\Model\PaymentStatus::CANCELED);
                    } else {
                        $result[] = $paymentObject;
                        if ($paymentObject->getStatus() !== $payment['status']) {
                            $this->updatePaymentStatus($payment['payment_id'], $paymentObject->getStatus(),
                                $paymentObject->getCapturedAt());
                        }
                    }
                } catch (\Exception $e) {
                    $this->log('warning', 'Error while update payment status. Payment id: '.$payment['payment_id']);
                }
            }
        }

        return $result;
    }

    /**
     * @param \YooKassa\Model\PaymentInterface $payment
     * @param bool $fetchPayment
     *
     * @return bool
     */
    public function capturePayment($payment, $fetchPayment = true)
    {
        if ($fetchPayment) {
            $client = $this->getClient();
            try {
                $payment = $client->getPaymentInfo($payment->getId());
            } catch (Exception $e) {
                $this->log('error', 'Payment '.$payment->getId().' not fetched from API in capture method');

                return false;
            }
        }

        if ($payment->getStatus() !== \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            return $payment->getStatus() === \YooKassa\Model\PaymentStatus::SUCCEEDED;
        }

        $client = $this->getClient();
        try {
            $builder = \YooKassa\Request\Payments\Payment\CreateCaptureRequest::builder();
            $builder->setAmount($payment->getAmount());
            $request = $builder->build();
            $result  = $client->capturePayment($request, $payment->getId());
            if ($result === null) {
                throw new RuntimeException('Failed to capture payment after 3 retries');
            }
            if ($result->getStatus() !== $payment->getStatus()) {
                $this->updatePaymentStatus($payment->getId(), $result->getStatus(), $result->getCapturedAt());
            }
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function cancelPayment($payment)
    {
        try {
            $response = $this->getClient()->cancelPayment($payment->getId());
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());
            $response = null;
        }
        if ($response !== null) {
            $payment = $response;
            $this->updatePaymentStatus($payment->getId(), $payment->getStatus(), $payment->getCapturedAt());
        }

        return $payment;
    }

    /**
     * @param int $orderId
     * @param array $orderInfo
     * @param \YooKassa\Model\PaymentInterface $payment
     * @param int $statusId
     */
    public function confirmOrderPayment($orderId, $orderInfo, $payment, $statusId)
    {
        $sql     = 'UPDATE `'.DB_PREFIX.'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
                   .(int)$orderId.' AND `order_status_id` <= 1';
        $comment = 'Номер транзакции: '.$payment->getId().'. Сумма: '.$payment->getAmount()->getValue()
                   .' '.$payment->getAmount()->getCurrency();
        $this->db->query($sql);

        $orderInfo['order_status_id'] = $statusId;
        $this->updateOrderStatus($orderId, $orderInfo, $comment);
    }

    public function updateOrderStatus($order_id, $order_info, $comment = '')
    {
        if ($order_info && $order_info['order_status_id']) {
            $sql = "UPDATE `".DB_PREFIX."order` SET order_status_id = '".(int)$order_info['order_status_id']
                   ."', date_modified = NOW() WHERE order_id = '".(int)$order_id."'";
            $this->db->query($sql);

            $sql = "INSERT INTO ".DB_PREFIX."order_history SET order_id = '".(int)$order_id
                   ."', order_status_id = '".(int)$order_info['order_status_id']."', notify = 0, comment = '"
                   .$this->db->escape($comment)."', date_added = NOW()";
            $this->db->query($sql);
        }
    }

    private function updatePaymentStatus($paymentId, $status, $capturedAt = null)
    {
        $sql = 'UPDATE `'.DB_PREFIX.'yoomoney_payment` SET `status` = \''.$status.'\'';
        if ($capturedAt !== null) {
            $sql .= ', `captured_at`=\''.$capturedAt->format('Y-m-d H:i:s').'\'';
        }
        if ($status !== \YooKassa\Model\PaymentStatus::CANCELED && $status !== \YooKassa\Model\PaymentStatus::PENDING) {
            $sql .= ', `paid`=\'Y\'';
        }
        $sql .= ' WHERE `payment_id`=\''.$paymentId.'\'';
        $this->db->query($sql);
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderRefunds($orderId)
    {
        $sql       = 'SELECT * FROM `'.DB_PREFIX.'yoomoney_refunds` WHERE `order_id` = '.(int)$orderId;
        $recordSet = $this->db->query($sql);
        $result    = array();
        foreach ($recordSet->rows as $record) {
            if ($record['status'] === \YooKassa\Model\RefundStatus::PENDING) {
                $this->checkRefundStatus($record['refund_id']);
            }
            $result[] = $record;
        }

        return $result;
    }

    private function checkRefundStatus($refundId)
    {
        try {
            $refund = $this->getClient()->getRefundInfo($refundId);
        } catch (\Exception $e) {
            return;
        }
        $sql = 'UPDATE `'.DB_PREFIX.'yoomoney_payment` SET `status` = \''
               .$this->db->escape($refund->getStatus()).'\'';
        $sql .= ' WHERE `refund_id` = \''.$this->db->escape($refund->getId()).'\'';
        $this->db->escape($sql);
    }

    /**
     * @param string $paymentId
     *
     * @return \YooKassa\Model\PaymentInterface|null
     */
    public function fetchPaymentInfo($paymentId)
    {
        try {
            $payment = $this->getClient()->getPaymentInfo($paymentId);
        } catch (Exception $e) {
            $this->log('error', 'Failed to fetch payment info: '.$e->getMessage());
            $payment = null;
        }

        return $payment;
    }

    public function getKassaModel()
    {
        if ($this->kassaModel === null) {
            require_once __DIR__.'/yoomoney/YooMoneyKassaModel.php';
            $this->kassaModel = new YooMoneyKassaModel($this->config);
            $this->kassaModel->setApiClient($this->getClient());
        }

        return $this->kassaModel;
    }

    public function getWalletModel()
    {
        if ($this->walletModel === null) {
            require_once __DIR__.'/yoomoney/YooMoneyWalletModel.php';
            $this->walletModel = new YooMoneyWalletModel($this->config);
        }

        return $this->walletModel;
    }

    public function refundPayment($payment, $order, $amount, $comment)
    {
        try {
            $builder = \YooKassa\Request\Refunds\CreateRefundRequest::builder();
            $builder->setAmount($amount)
                    ->setCurrency(\YooKassa\Model\CurrencyCode::RUB)
                    ->setPaymentId($payment->getId())
                    ->setDescription($comment);
            $request = $builder->build();
        } catch (Exception $e) {
            $this->log('error', 'Failed to create refund: '.$e->getMessage());

            return null;
        }

        try {
            $response = $this->getClient()->createRefund($request);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create refund: '.$e->getMessage());

            return null;
        }

        if ($response !== null) {
            $this->insertRefund($response, $order['order_id']);
        }

        return $response;
    }

    /**
     * @param \YooKassa\Model\RefundInterface $refund
     * @param int $orderId
     */
    private function insertRefund($refund, $orderId)
    {
        $sql = 'INSERT INTO `'.DB_PREFIX.'yoomoney_refunds`('
               .'`refund_id`, `order_id`, `payment_id`, `status`, `amount`, `currency`, `created_at`'
               .') VALUES ('
               ."'".$this->db->escape($refund->getId())."',"
               .(int)$orderId.","
               ."'".$this->db->escape($refund->getPaymentId())."',"
               ."'".$this->db->escape($refund->getStatus())."',"
               ."'".$this->db->escape($refund->getAmount()->getValue())."',"
               ."'".$this->db->escape($refund->getAmount()->getCurrency())."',"
               ."'".$this->db->escape($refund->getCreatedAt()->format('Y-m-d H:i:s'))."'"
               .')';
        $this->db->query($sql);
    }

    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new \YooKassa\Client();
            $this->client->setAuth(
                $this->getKassaModel()->getShopId(),
                $this->getKassaModel()->getPassword()
            );
            $this->client->setLogger($this);
            $userAgent = $this->client->getApiClient()->getUserAgent();
            $userAgent->setCms('OpenCart', VERSION);
            $userAgent->setModule('YooMoney',self::MODULE_VERSION);
        }

        return $this->client;
    }

    public function getBackupList()
    {
        $result = array();

        $this->preventDirectories();
        $dir = DIR_DOWNLOAD.'/'.$this->backupDirectory;

        $handle = opendir($dir);
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            if ($ext === 'zip') {
                $backup            = array(
                    'name' => pathinfo($entry, PATHINFO_FILENAME).'.zip',
                    'size' => $this->formatSize(filesize($dir.'/'.$entry)),
                );
                $parts             = explode('-', $backup['name'], 3);
                $backup['version'] = $parts[0];
                $backup['time']    = $parts[1];
                $backup['date']    = date('d.m.Y H:i:s', $parts[1]);
                $backup['hash']    = $parts[2];
                $result[]          = $backup;
            }
        }

        return $result;
    }

    public function createBackup($version)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $sourceDirectory = dirname(realpath(DIR_CATALOG));
        $reader          = new \YooMoney\Updater\ProjectStructure\ProjectStructureReader();
        $root            = $reader->readFile(dirname(__FILE__).'/yoomoney/'.$this->getMapFileName(),
            $sourceDirectory);

        $rootDir  = $version.'-'.time();
        $fileName = $rootDir.'-'.uniqid('', true).'.zip';

        $dir = DIR_DOWNLOAD.'/'.$this->backupDirectory;
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                $this->log('error', 'Failed to create backup directory: '.$dir);

                return false;
            }
        }

        try {
            $fileName = $dir.'/'.$fileName;
            $archive  = new \YooMoney\Updater\Archive\BackupZip($fileName, $rootDir);
            $archive->backup($root);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create backup: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function restoreBackup($fileName)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $fileName = DIR_DOWNLOAD.'/'.$this->backupDirectory.'/'.$fileName;
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        try {
            $sourceDirectory = dirname(realpath(DIR_CATALOG));
            $archive         = new \YooMoney\Updater\Archive\RestoreZip($fileName);
            $archive->restore('file_map.map', $sourceDirectory);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            if ($e->getPrevious() !== null) {
                $this->log('error', $e->getPrevious()->getMessage());
            }

            return false;
        }

        return true;
    }

    public function removeBackup($fileName)
    {
        $this->preventDirectories();

        $fileName = DIR_DOWNLOAD.'/'.$this->backupDirectory.'/'.str_replace(array('/', '\\'), array('', ''), $fileName);
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        if (!unlink($fileName) || file_exists($fileName)) {
            $this->log('error', 'Failed to unlink file "'.$fileName.'"');

            return false;
        }

        return true;
    }

    public function checkModuleVersion($useCache = true)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $file = DIR_DOWNLOAD.'/'.$this->downloadDirectory.'/version_log.txt';

        if ($useCache) {
            if (file_exists($file)) {
                $content = preg_replace('/\s+/', '', file_get_contents($file));
                if (!empty($content)) {
                    $parts = explode(':', $content);
                    if (count($parts) === 2) {
                        if (time() - $parts[1] < 3600 * 8) {
                            return array(
                                'tag'     => $parts[0],
                                'version' => preg_replace('/[^\d\.]+/', '', $parts[0]),
                                'time'    => $parts[1],
                                'date'    => $this->dateDiffToString($parts[1]),
                            );
                        }
                    }
                }
            }
        }

        $connector = new GitHubConnector();
        $version   = $connector->getLatestRelease($this->repository);
        if (empty($version)) {
            return array();
        }

        $cache = $version.':'.time();
        file_put_contents($file, $cache);

        return array(
            'tag'     => $version,
            'version' => preg_replace('/[^\d\.]+/', '', $version),
            'time'    => time(),
            'date'    => $this->dateDiffToString(time()),
        );
    }

    public function downloadLastVersion($tag, $useCache = true)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $dir = DIR_DOWNLOAD.'/'.$this->versionDirectory;
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                $this->log('error', sprintf($this->language->log('updater_error_create_directory'), $dir));

                return false;
            }
        } elseif ($useCache) {
            $fileName = $dir.'/'.$tag.'.zip';
            if (file_exists($fileName)) {
                return $fileName;
            }
        }

        $connector = new GitHubConnector();
        $fileName  = $connector->downloadRelease($this->repository, $tag, $dir);
        if (empty($fileName)) {
            $this->log('error', $this->language->log('updater_error_load'));

            return false;
        }

        return $fileName;
    }

    public function unpackLastVersion($fileName)
    {
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        try {
            $sourceDirectory = dirname(realpath(DIR_CATALOG));
            $archive         = new \YooMoney\Updater\Archive\RestoreZip($fileName);
            $archive->restore($this->getMapFileName(), $sourceDirectory);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            if ($e->getPrevious() !== null) {
                $this->log('error', $e->getPrevious()->getMessage());
            }

            return false;
        }

        return true;
    }

    public function getChangeLog($currentVersion, $newVersion)
    {
        $connector = new GitHubConnector();

        $dir          = DIR_DOWNLOAD.'/'.$this->downloadDirectory;
        $newChangeLog = $dir.'/CHANGELOG-'.$newVersion.'.md';
        if (!file_exists($newChangeLog)) {
            $fileName = $connector->downloadLatestChangeLog($this->repository, $dir);
            if (!empty($fileName)) {
                rename($dir.'/'.$fileName, $newChangeLog);
            }
        }

        $oldChangeLog = $dir.'/CHANGELOG-'.$currentVersion.'.md';
        if (!file_exists($oldChangeLog)) {
            $fileName = $connector->downloadLatestChangeLog($this->repository, $dir);
            if (!empty($fileName)) {
                rename($dir.'/'.$fileName, $oldChangeLog);
            }
        }

        $result = '';
        if (file_exists($newChangeLog)) {
            $result = $connector->diffChangeLog($oldChangeLog, $newChangeLog);
        }

        return $result;
    }

    private function formatSize($size)
    {
        static $sizes = array(
            'B',
            'kB',
            'MB',
            'GB',
            'TB',
        );

        $i = 0;
        while ($size > 1024) {
            $size /= 1024.0;
            $i++;
        }

        return number_format($size, 2, '.', ',').'&nbsp;'.$sizes[$i];
    }

    private function loadClasses()
    {
        if (!class_exists('GitHubConnector')) {
            $path = dirname(__FILE__).DIRECTORY_SEPARATOR.'yoomoney'.DIRECTORY_SEPARATOR.'Updater'.DIRECTORY_SEPARATOR;
            require_once $path.'GitHubConnector.php';
            require_once $path.'ProjectStructure/EntryInterface.php';
            require_once $path.'ProjectStructure/DirectoryEntryInterface.php';
            require_once $path.'ProjectStructure/FileEntryInterface.php';
            require_once $path.'ProjectStructure/AbstractEntry.php';
            require_once $path.'ProjectStructure/DirectoryEntry.php';
            require_once $path.'ProjectStructure/FileEntry.php';
            require_once $path.'ProjectStructure/ProjectStructureReader.php';
            require_once $path.'ProjectStructure/ProjectStructureWriter.php';
            require_once $path.'ProjectStructure/RootDirectory.php';
            require_once $path.'Archive/BackupZip.php';
            require_once $path.'Archive/RestoreZip.php';
        }
    }

    private function dateDiffToString($timestamp)
    {
        return date('d.m.Y H:i:s', $timestamp);
    }

    private function preventDirectories()
    {
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->downloadDirectory);
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->backupDirectory);
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->versionDirectory);
    }

    private function checkDirectory($directoryName)
    {
        if (!file_exists($directoryName)) {
            mkdir($directoryName);
        }
        if (!is_dir($directoryName)) {
            throw new RuntimeException('Invalid configuration: "'.$directoryName.'" is not directory');
        }
    }

    private function getMapFileName()
    {
        return 'opencart-3.map';
    }
}
