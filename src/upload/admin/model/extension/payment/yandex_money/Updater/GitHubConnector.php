<?php

class GitHubConnector
{
    const DEFAULT_URL = 'https://github.com';
    const DEFAULT_BROWSER = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 YaBrowser/17.9.1.768 Yowser/2.5 Safari/537.36';

    const ARCHIVE_BEST = 0;
    const ARCHIVE_ZIP = 1;
    const ARCHIVE_TAR_GZ = 2;

    private $baseUrl;
    private $curl;
    private $browser;

    public function __construct()
    {
        $this->baseUrl = self::DEFAULT_URL;
        $this->browser = self::DEFAULT_BROWSER;
    }

    public function __destruct()
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    public function setBrowser($value)
    {
        $this->browser = $value;
        return $this;
    }

    /**
     * Возвращает тег последнего релиза репозитория в гитхабе
     *
     * При запросе к гитхабу по адресу https://github.com/<repository>/releases/latest гитхаб возвращает ответ с
     * редиректом на страницу https://github.com/<repository>/releases/tag/<tag_name> Для получения последней версии
     * делаем запрос без указания флага CURLOPT_FOLLOWLOCATION, парсим заголовки и вытаскиваем из заголовка Location
     * значение страницы редиректа, откуда в итоге вытаскиваем <tag_name>.
     *
     * @param string $repository Имя репозитория на гитхабе
     *
     * @return string|null Тэг последнего релиза или null если данные получить не удалось
     */
    public function getLatestRelease($repository)
    {
        $curl = $this->getCurl();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . '/' . $repository . '/releases/latest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->getRequestHeaders($repository . '/tags'),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ));

        $result = curl_exec($curl);
        if (empty($result)) {
            return null;
        }
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code !== 301 && $code !== 302) {
            return null;
        }
        $headers = $this->parseHeaders($result);
        if (isset($headers['location'])) {
            $pos = strrpos($headers['location'], 'tag/');
            if ($pos !== false) {
                return substr($headers['location'], $pos + 4);
            }
        }
        return null;
    }

    /**
     * Метод скачивает файл лога изменений из гитхаб репозитория
     *
     * Подразумеваем, что файл лога изменений лежит в корне репозитория. По умолчанию скачивается файл с именем
     * "CHANGELOG.md" из ветки "master".
     *
     * @param string $repository Имя репозитория на гитхабе
     * @param string $downloadDir Имя папки в которую будет загружен лог изменений
     * @param string $fileName Имя файла лога изменений
     * @param string $branch Имя ветки в репозитории из которой вытягивается файл изменений
     *
     * @return string|null Полный путь до загруженного файла с логом изменений или null если файл скачать не удалось
     */
    public function downloadLatestChangeLog($repository, $downloadDir, $fileName = 'CHANGELOG.md', $branch = 'master')
    {
        $curl = $this->getCurl();

        $outFileName = rtrim($downloadDir, '/') . '/' . $fileName;
        $file = fopen($outFileName, 'w');
        if (!$file) {
            return null;
        }

        $options = array(
            CURLOPT_URL => 'https://raw.githubusercontent.com/' . $repository . '/' . $branch . '/' . $fileName,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FILE => $file,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $this->getRequestHeaders($repository . '/blob/' . $branch . '/' . $fileName),
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
        );
        if (defined('CURLOPT_ACCEPT_ENCODING')) {
            $options[CURLOPT_ACCEPT_ENCODING] = true;
        }

        curl_setopt_array($curl, $options);

        $result = curl_exec($curl);
        fclose($file);
        if (empty($result)) {
            return null;
        }
        return $fileName;
    }

    /**
     * Скачивает архив с релизом с гитхаба
     *
     * @param string $repository Имя репозитория на гитхабе
     * @param string $version Скачиваемая версия релиза
     * @param string $downloadDir Директория в которую скачивается архив с релизом
     * @param int $type Тип архива, одна из констант self::ARCHIVE_XXX
     *
     * @return null|string Полный путь до файла с архивом или null если архив скачать не удалось
     */
    public function downloadRelease($repository, $version, $downloadDir, $type = self::ARCHIVE_BEST)
    {
        $curl = $this->getCurl();

        if ($type === self::ARCHIVE_BEST) {
            if (function_exists('zip_open')) {
                $type = self::ARCHIVE_ZIP;
            } else {
                $type = self::ARCHIVE_TAR_GZ;
            }
        }
        if ($type === self::ARCHIVE_ZIP) {
            $ext = 'zip';
        } elseif ($type === self::ARCHIVE_TAR_GZ) {
            $ext = 'tar.gz';
        } else {
            throw new RuntimeException('Invalid archive type "' . $type . '"');
        }

        $fileName = rtrim($downloadDir, '/') . '/' . $version . '.' . $ext;
        $file = fopen($fileName, 'w');
        if (!$file) {
            return null;
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . '/' . $repository . '/archive/' . $version . '.' . $ext,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FILE => $file,
            CURLOPT_HEADER => false,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->getRequestHeaders($repository . '/releases/tag/' . $version),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
        ));

        $result = curl_exec($curl);
        fclose($file);
        if (empty($result)) {
            return null;
        }
        return $fileName;
    }

    /**
     * Сравнивает файлы логов изменений, и возвращает строки из нового лога, которых нет в старом
     *
     * @param string $oldChangeLog Имя файла лога старой версии
     * @param string $newChangeLog Имя файла лога новой версии
     *
     * @return null|string Строки из лога в новой версии которых нет в старом логе
     */
    public function diffChangeLog($oldChangeLog, $newChangeLog)
    {
        $old = fopen($oldChangeLog, 'r');
        if (!$old) {
            return null;
        }

        $new = fopen($newChangeLog, 'r');
        if (!$new) {
            fclose($old);
            return null;
        }

        do {
            $stop = trim(fgets($old, 1024));
        } while (empty($stop) && !feof($old));
        fclose($old);

        $result = array();
        while (!feof($new)) {
            $line = trim(fgets($new, 1024));
            if ($line === $stop) {
                break;
            }
            $result[] = $line;
        }
        fclose($new);
        return implode('<br />' . PHP_EOL, $result);
    }

    /**
     * Парсит заголовки HTTP запроса или ответа
     *
     * @param string $response HTTP ответ в виде строки
     *
     * @return array Массив заголовков в виде {"<header>" : "<value>", ... }
     */
    private function parseHeaders($response)
    {
        $pos = strpos($response, "\n\n");
        if ($pos === false) {
            $pos = strpos($response, "\r\n\r\n");
        }
        if ($pos !== false) {
            $headers = substr($response, 0, $pos);
        } else {
            $headers = trim($response);
        }

        $result = array();
        $lines = explode("\n", $headers);
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = explode(':', $line, 2);
                $header = strtolower(trim($parts[0]));
                if (count($parts) == 2) {
                    $result[$header] = trim($parts[1]);
                } else {
                    $result[$header] = true;
                }
            }
        }
        return $result;
    }

    /**
     * Возвращает инициализированный ресурс курла
     *
     * @return resource Хэндлер курла
     *
     * @throws RuntimeException Выбрасывается если расширение курла не установлено или если хэндлер не удалось создать
     */
    private function getCurl()
    {
        if ($this->curl === null) {
            if (!function_exists('curl_init')) {
                throw new RuntimeException('Curl extension not installed');
            }
            $this->curl = curl_init();
            if (!$this->curl) {
                throw new RuntimeException('Failed to init curl');
            }
        }
        return $this->curl;
    }

    /**
     * Возвращает массив заголовков для отправки в HTTP запросе
     *
     * @param string $referrer URL источника запроса для отправки в заголовке Referer
     * @param bool $deflateOnly
     *
     * @return array Массив заголовков для передачи
     */
    private function getRequestHeaders($referrer)
    {
        $deflateOnly = !defined('CURLOPT_ACCEPT_ENCODING');
        return array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding: ' . ($deflateOnly ? 'deflate' : 'gzip,deflate,br'),
            'Accept-Language: ru,en;q=0.8',
            'Cache-Control: max-age=0',
            'Connection: close',
            'DNT: 1',
            'Referer: ' . $this->baseUrl . '/' . $referrer,
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: ' . $this->browser,
        );
    }
}
