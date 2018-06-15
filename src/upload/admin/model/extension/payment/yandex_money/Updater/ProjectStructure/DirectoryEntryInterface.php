<?php

namespace YandexMoney\Updater\ProjectStructure;

/**
 * Интерфейс директории проекта
 */
interface DirectoryEntryInterface extends EntryInterface
{
    /**
     * Возвращает список директорий внутри текущией директории
     * @return DirectoryEntryInterface[] Список поддиректорий
     */
    function getDirectoryEntries();

    /**
     * Возвращает список файлов внутри текущей директории
     * @return FileEntryInterface[] Список файлов внутри директории
     */
    function getFileEntries();
}
