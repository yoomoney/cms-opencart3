<?php

namespace YooMoney\Updater\ProjectStructure;

/**
 * Класс вхождения файла в проект
 */
class FileEntry extends AbstractEntry implements FileEntryInterface
{
    /**
     * @var int Размер файла в байтах
     */
    private $fileSize;

    /**
     * Проверяет является ли текущий элемент директорией
     * @return bool Возвращает false
     */
    public function isDir()
    {
        return false;
    }

    /**
     * Возвращает размер файла в байтах
     * @return int Размер файла в байтах
     */
    public function getSize()
    {
        if ($this->fileSize === null) {
            $this->fileSize = filesize($this->getAbsolutePath());
        }
        return $this->fileSize;
    }
}
