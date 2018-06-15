<?php

namespace YandexMoney\Updater\ProjectStructure;

/**
 * Интерфейс файла проекта
 */
interface FileEntryInterface extends EntryInterface
{
    /**
     * Возвращает размер файла в байтах
     * @return int Размер файла в байтах
     */
    function getSize();
}
