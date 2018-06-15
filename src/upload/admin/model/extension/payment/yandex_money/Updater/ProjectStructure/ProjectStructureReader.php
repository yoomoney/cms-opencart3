<?php

namespace YandexMoney\Updater\ProjectStructure;

/**
 * Класс ридера настроек файлов и директорий проекта
 *
 * @package YandexMoney\Updater\ProjectStructure
 */
class ProjectStructureReader
{
    /**
     * Читает настройки директорий и файлов проекта из файла
     * @param string $fileName Имя файла с настройками директорий и файлов
     * @param string $sourceDirectory Директория в которую данные загружаются в CMS
     * @return RootDirectory Информация о директориях и файлах проекта
     */
    public function readFile($fileName, $sourceDirectory)
    {
        $fd = fopen($fileName, 'rb');
        if (!$fd) {
            throw new \RuntimeException('Failed to open file "' . $fileName . '"');
        }
        $root = $this->readContent(fread($fd, filesize($fileName)), $sourceDirectory);
        fclose($fd);
        return $root;
    }

    /**
     * Парсит настройки директорий и файлов проекта из строки
     * @param string $content Строка с настройками проекта
     * @param string $sourceDirectory Директория в которую данные загружаются в CMS
     * @return RootDirectory Информация о директориях и файлах проекта
     */
    public function readContent($content, $sourceDirectory)
    {
        $root = new RootDirectory($sourceDirectory);
        foreach (explode("\n", $content) as $line) {
            $line = rtrim($line, "\r");
            $entry = explode(':', $line);
            if ($entry[0] == 'b') {
                $root->setProjectPath($entry[1]);
                continue;
            }
            if (count($entry) < 2) {
                continue;
            }
            if (count($entry) == 2) {
                $entry[2] = $entry[1];
            }
            $entries = $this->parsePlaceholders($entry);
            $this->addEntries($root, $entries);
        }
        return $root;
    }

    /**
     * Добавляет в настройки проекта директории и файлы из массива настроек
     * @param RootDirectory $root Объект с информацией о директориях и файлах проекта
     * @param array $entries Массив с директориями и файлами проекта
     */
    private function addEntries(RootDirectory $root, $entries)
    {
        foreach ($entries as $entry) {
            if (substr($entry[2], -1) == '/') {
                $entry[2] .= pathinfo($entry[1], PATHINFO_BASENAME);
            }
            if ($entry[0] == 'f') {
                $root->factoryFile($entry[1], $entry[2]);
            } elseif ($entry[0] == 'd') {
                $root->factoryDirectory($entry[1], $entry[2]);
            }
        }
    }

    /**
     * На вход получает массив вида [<type>, <project_path>, <cms_path>], преобразует шаблоны вида {<path1>,<path2>} и
     * возвращает массив настроек файлов и директорий с готовыми именами файлов
     * @param array $entry Массив с настройками файла или директории
     * @return array Массив с настройками файлов и директорий
     */
    private function parsePlaceholders($entry)
    {
        if (preg_match_all('/\{([^\}]+)\}/', $entry[1], $matches)) {
            $previous = array();
            $replace = array();
            for ($index = count($matches[0]) - 1; $index >= 0; $index--) {
                $match = $matches[0][$index];
                $tmp = explode(',', $matches[1][$index]);
                $replace = array();
                foreach ($tmp as $rep) {
                    if (!empty($previous)) {
                        foreach ($previous as $prev) {
                            $prev[$match] = $rep;
                            $replace[] = $prev;
                        }
                    } else {
                        $replace[] = array(
                            $match => $rep,
                        );
                    }
                }
                $previous = $replace;
            }
            $entries = array();
            foreach ($replace as $rep) {
                $entries[] = array(
                    $entry[0],
                    strtr($entry[1], $rep),
                    strtr($entry[2], $rep),
                );
            }
        } else {
            $entries = array($entry);
        }
        return $entries;
    }
}