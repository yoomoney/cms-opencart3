<?php

namespace YandexMoney\Updater\Archive;

use YandexMoney\Updater\ProjectStructure\DirectoryEntryInterface;
use YandexMoney\Updater\ProjectStructure\FileEntryInterface;
use YandexMoney\Updater\ProjectStructure\ProjectStructureWriter;
use ZipArchive;
use RuntimeException;

/**
 * Класс для сохранения бэкапа проекта в zip архив
 *
 * @package YandexMoney\Updater\Archive
 */
class BackupZip
{
    /**
     * @var ZipArchive Инстанс используемого зип архива
     */
    private $zip;

    /**
     * @var string Корневая директория внутри зип архива
     */
    private $rootDirectory;

    /**
     * BackupZip constructor.
     *
     * @param string $fileName Имя файла zip архива
     * @param string $rootDirectory Корневая директория внутри зип архива
     */
    public function __construct($fileName, $rootDirectory = '')
    {
        $this->rootDirectory = $rootDirectory;
        $this->zip = new ZipArchive();
        $result = $this->zip->open($fileName, ZipArchive::CREATE);
        if ($result !== true) {
            throw new RuntimeException('Failed to open zip archive', $result);
        }
        if (!empty($this->rootDirectory)) {
            $this->zip->addEmptyDir($this->rootDirectory);
        }
    }

    /**
     * Деструктор, если зип архив ещё не закрыт - закрывает его
     */
    public function __destruct()
    {
        if ($this->zip !== null) {
            $this->close();
        }
    }

    /**
     * Закрывает зип арзив, если он не закрыт
     */
    public function close()
    {
        $this->zip->close();
        $this->zip = null;
    }

    /**
     * Записывает в зип архив данные проекта
     * @param DirectoryEntryInterface $root Корневая директория проекта с настройками файлов и директорий
     */
    public function backup(DirectoryEntryInterface $root)
    {
        foreach ($root->getDirectoryEntries() as $entry) {
            $this->addDirectory($entry);
        }
        foreach ($root->getFileEntries() as $entry) {
            $this->addFile($entry);
        }

        $writer = new ProjectStructureWriter();
        $local = (empty($this->rootDirectory) ? '' : $this->rootDirectory . '/') . 'file_map.map';
        $this->zip->addFromString($local, $writer->writeToString($root));
    }

    /**
     * Добавляет в зип архив файл
     * @param FileEntryInterface $file Инстанс добавляемого в архив файла
     * @return BackupZip Инстанс текущего объекта
     * @throws RuntimeException Генерируется если добавить файл в архив не удалось
     */
    private function addFile(FileEntryInterface $file)
    {
        $path = $file->getAbsolutePath();
        if (!file_exists($path)) {
            throw new RuntimeException('File "' . $path . '" not exists');
        } elseif (!is_file($path)) {
            throw new RuntimeException('Invalid file "' . $path . '" (not a file)');
        } elseif (!is_readable($path)) {
            throw new RuntimeException('File "' . $path . '" not readable');
        }
        $local = (empty($this->rootDirectory) ? '' : $this->rootDirectory . '/') . $file->getProjectPath();
        if (!$this->zip->addFile($path, $local)) {
            throw new RuntimeException('Failed to add file "' . $path . '" -> "' . $local . '" to archive');
        }
        return $this;
    }

    /**
     * Добавляет в zip архив директорию
     * @param DirectoryEntryInterface $dir Инстанс добавляемой директории
     * @return BackupZip Инстанс текущего объекта
     * @throws RuntimeException Генерируется если добавить папку в архив не удалось
     */
    private function addDirectory(DirectoryEntryInterface $dir)
    {
        $path = $dir->getAbsolutePath();
        if (!file_exists($path)) {
            throw new RuntimeException('Directory "' . $path . '" not exists');
        } elseif (!is_dir($path)) {
            throw new RuntimeException('Invalid directory "' . $path . '" (not a directory)');
        } elseif (!is_readable($path)) {
            throw new RuntimeException('Directory "' . $path . '" not readable');
        }

        $local = (empty($this->rootDirectory) ? '' : $this->rootDirectory . '/') . $dir->getProjectPath();
        if (!$this->zip->addEmptyDir($local)) {
            throw new RuntimeException('Failed to add directory "' . $path . '" -> "' . $local . '" to archive');
        }
        foreach ($dir->getDirectoryEntries() as $directory) {
            $path = $directory->getAbsolutePath();
            $local = (empty($this->rootDirectory) ? '' : $this->rootDirectory . '/') . $directory->getProjectPath();
            if (!$this->zip->addEmptyDir($local)) {
                throw new RuntimeException('Failed to add directory "' . $path . '" -> "' . $local . '" to archive');
            }
        }
        try {
            foreach ($dir->getFileEntries() as $file) {
                $this->addFile($file);
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException('Failed to add directory "' . $dir->getAbsolutePath() . '"', 0, $e);
        }
        return $this;
    }
}
