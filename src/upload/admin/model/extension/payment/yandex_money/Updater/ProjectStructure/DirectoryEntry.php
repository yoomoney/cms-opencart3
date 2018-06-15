<?php

namespace YandexMoney\Updater\ProjectStructure;

/**
 * Класс вхождения директории в проект
 */
class DirectoryEntry extends AbstractEntry implements DirectoryEntryInterface
{
    /**
     * @var DirectoryEntryInterface[] Список поддиректории внутри текущей директории
     */
    private $directories;

    /**
     * @var FileEntryInterface[] Список файлов внутри текущей директории
     */
    private $files;

    /**
     * Проверяет является ли текущий элемент директорией
     * @return bool Возвращает true
     */
    public function isDir()
    {
        return true;
    }

    /**
     * Возвращает список директорий внутри текущией директории
     * @return DirectoryEntryInterface[] Список поддиректорий
     */
    public function getDirectoryEntries()
    {
        if ($this->directories === null) {
            $this->directories = array();
            $this->walk($this->getAbsolutePath());
        }
        return $this->directories;
    }

    /**
     * Возвращает список файлов внутри текущей директории
     * @return FileEntryInterface[] Список файлов внутри директории
     */
    public function getFileEntries()
    {
        if ($this->files === null) {
            $this->files = array();
            $this->walk($this->getAbsolutePath());
        }
        return $this->files;
    }

    /**
     * Осуществляет рекурсивный обход всех поддиректорий и заполняет списки директорий и файлов
     * @param string $directory Имя директории которую сканим
     * @param string $relativePath Относительный путь до директории
     * @param DirectoryEntry|null $parentDirectory Родительская директория в которой происходит парсин списка файлов
     */
    private function walk($directory, $relativePath = '', DirectoryEntry $parentDirectory = null)
    {
        if (!file_exists($directory)) {
            throw new RuntimeException('Directory not exists: ' . $directory);
        }
        $dirHandle = opendir($directory);
        while (($entry = readdir($dirHandle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_file($path)) {
                $file = new FileEntry($this, $relativePath . $entry, $this->getProjectPath() . '/' . $relativePath . $entry);
                if ($parentDirectory !== null) {
                    $parentDirectory->files[] = $file;
                }
                $this->files[] = $file;
            } elseif (is_dir($path)) {
                $dir = new DirectoryEntry($this, $relativePath . $entry, $this->getProjectPath() . '/' . $relativePath . $entry);
                if ($parentDirectory !== null) {
                    $parentDirectory->directories[] = $dir;
                }
                $this->directories[] = $dir;
                $this->walk($path, $relativePath . $entry . '/', $dir);
            }
        }
        closedir($dirHandle);
    }
}