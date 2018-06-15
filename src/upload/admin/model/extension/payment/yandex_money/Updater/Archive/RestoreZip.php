<?php

namespace YandexMoney\Updater\Archive;

use RuntimeException;
use YandexMoney\Updater\ProjectStructure\DirectoryEntryInterface;
use YandexMoney\Updater\ProjectStructure\EntryInterface;
use YandexMoney\Updater\ProjectStructure\FileEntryInterface;
use YandexMoney\Updater\ProjectStructure\ProjectStructureReader;
use YandexMoney\Updater\ProjectStructure\RootDirectory;
use ZipArchive;

/**
 * Класс распаковки архива и встраивания его в CMS
 *
 * @package YandexMoney\Updater\Archive
 */
class RestoreZip
{
    /**
     * @var ZipArchive Инстанс архива
     */
    private $zip;

    /**
     * Конструктор, открывает архив
     * @param string $fileName Имя файла архива
     */
    public function __construct($fileName)
    {
        if (!file_exists($fileName)) {
            throw new RuntimeException('Archive file "' . $fileName . '" not exists');
        } elseif (!is_file($fileName)) {
            throw new RuntimeException('Invalid archive file "' . $fileName . '"');
        } elseif (!is_readable($fileName)) {
            throw new RuntimeException('Archive file "' . $fileName . '" not readable');
        }
        $this->zip = new ZipArchive();
        $result = $this->zip->open($fileName);
        if ($result !== true) {
            throw new RuntimeException('Failed to open zip archive "' . $fileName . '"', $result);
        }
    }

    /**
     * Дектсрктор, закрывает файл архива, если он не закрыт
     */
    public function __destruct()
    {
        if ($this->zip !== null) {
            $this->close();
        }
    }

    /**
     * Закрывает файл архива, если он не закрыт
     */
    public function close()
    {
        $this->zip->close();
        $this->zip = null;
    }

    /**
     * Восстанавливает файлы из архива
     * @param string $mapFileName Имя файла с настройками проекта внутри архива
     * @param string $destinationDirectory Корневая директория CMS в которую происходит распаковка файлов
     */
    public function restore($mapFileName, $destinationDirectory)
    {
        if (!file_exists($destinationDirectory)) {
            if (!mkdir($destinationDirectory)) {
                throw new RuntimeException('Failed to create destination directory "' . $destinationDirectory . '"');
            }
        } elseif (!is_dir($destinationDirectory)) {
            throw new RuntimeException('Invalid destination directory "' . $destinationDirectory . '"');
        } elseif (!is_writable($destinationDirectory)) {
            throw new RuntimeException('Destination directory "' . $destinationDirectory . '" not writable');
        }

        $directoryName = $this->zip->getNameIndex(0);
        $root = $this->factoryProject($mapFileName, $destinationDirectory);

        $directories = $this->prepareDirectories($root, $directoryName);
        if (!empty($directories)) {
            $this->restoreDirectories($directories);
        }

        foreach ($root->getFileEntries() as $entry) {
            $this->restoreFile($directoryName, $entry);
        }
    }

    /**
     * Создаёт из файла с настройками объект с настройками файлов и директорий
     * @param string $mapFileName Имя файла с настройками, который находится в архиве
     * @param string $destinationDirectory Корневая директория CMS
     * @return RootDirectory Объект с настройками файлов и директорий проекта
     */
    private function factoryProject($mapFileName, $destinationDirectory)
    {
        $content = '';
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $name = $this->zip->getNameIndex($i);
            if ($mapFileName === pathinfo($name, PATHINFO_BASENAME)) {
                $content = $this->zip->getFromIndex($i);
                break;
            }
        }
        if (empty($content)) {
            throw new RuntimeException('Map file "' . $mapFileName . '" not found in archive');
        }
        $reader = new ProjectStructureReader();
        return $reader->readContent($content, $destinationDirectory);
    }

    /**
     * Пробегается по всем директориям проекта, проверяет их налилчие в архиве, создаёт в структуре проекта, если их
     * нет и возвращает массив всех директорий проекта в виде {"<archive_path>": DirectoryEntryInterface, ...}
     * @param RootDirectory $root Настройки проекта
     * @param string $directoryName Имя корневой директории внутри архива
     * @return DirectoryEntryInterface[] Список всех директорий проекта
     */
    private function prepareDirectories(RootDirectory $root, $directoryName)
    {
        $directories = array();
        foreach ($root->getDirectoryEntries() as $entry) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $path = str_replace(DIRECTORY_SEPARATOR, '/', $directoryName . $entry->getProjectPath()) . '/';
            } else {
                $path = $directoryName . $entry->getProjectPath() . '/';
            }
            $tmp = $this->zip->getFromName($path);
            if ($tmp === false) {
                throw new RuntimeException('Directory "' . $path . '" not exists in archive');
            }
            $this->prepareDirectory($entry->getAbsolutePath());
            $directories[$path] = $entry;
        }
        return $directories;
    }

    /**
     * Подготавливает директорию - создаёт рекурсивно нужные директории в структуре CMS
     * @param string $directory Полный путь до нужной директории
     * @throws RuntimeException Выбрасывается если не удалось создать какую-либо директорию
     */
    private function prepareDirectory($directory)
    {
        if (!file_exists($directory)) {
            $this->prepareDirectory(dirname($directory));
            if (!mkdir($directory)) {
                throw new RuntimeException('Failed to create directory "' . $directory . '"');
            }
        }
    }

    /**
     * Копирует содержимое переданных директорий в структуру CMS
     * @param DirectoryEntryInterface[] $directories Массив копируемых директорий
     */
    private function restoreDirectories($directories)
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $name = $this->zip->getNameIndex($i);
            foreach ($directories as $dir => $entry) {
                if (strncmp($dir, $name, strlen($dir)) === 0 && $name !== $dir) {
                    $this->restoreDirectory($entry, $name, $dir, $i);
                }
            }
        }
    }

    /**
     * Восстанавливает поддиректорию или файл внутри директории
     * @param EntryInterface $entry Инстанс восстанавливаемого файла или директории
     * @param string $name Имя файла или директоии внутри архива
     * @param string $dir Имя директории в которой происходит работа
     * @param int $index Индекст текущего файла или директории в zip архиве
     */
    private function restoreDirectory(EntryInterface $entry, $name, $dir, $index)
    {
        $path = substr($name, strlen($dir));
        $fileName = $entry->getAbsolutePath() . DIRECTORY_SEPARATOR . $path;
        if (substr($name, -1) === '/') {
            $this->prepareDirectory($fileName);
        } else {
            $tmp = $this->zip->getFromIndex($index);
            $out = fopen($fileName, 'wb');
            if (!$out) {
                throw new RuntimeException('Failed to create or open file "' . $fileName . '"');
            }
            fwrite($out, $tmp);
            fclose($out);
        }
    }

    /**
     * Восстанавливает файл из архива
     * @param string $rootDirectory Путь к корневой директории проекта внутри архива
     * @param FileEntryInterface $entry Восстанавливаемый файл
     */
    private function restoreFile($rootDirectory, FileEntryInterface $entry)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $rootDirectory . $entry->getProjectPath());
        } else {
            $path = $rootDirectory . $entry->getProjectPath();
        }
        $tmp = $this->zip->getFromName($path);
        if ($tmp === false) {
            throw new RuntimeException('File "' . $entry->getProjectPath() . '" not exists in archive');
        }
        $filePath = $entry->getAbsolutePath();
        if (file_exists($filePath)) {
            if (!is_file($filePath)) {
                throw new RuntimeException('Invalid file "' . $filePath . '"');
            } elseif (!is_writable($filePath)) {
                throw new RuntimeException('File "' . $filePath . '" not writable');
            }
        } else {
            $this->prepareDirectory(dirname($filePath));
        }
        $out = fopen($filePath, 'wb');
        if (!$out) {
            throw new RuntimeException('Failed to open file "' . $filePath . '"');
        }
        if (strlen($tmp) > 0) {
            fwrite($out, $tmp);
        }
        fclose($out);
    }
}
