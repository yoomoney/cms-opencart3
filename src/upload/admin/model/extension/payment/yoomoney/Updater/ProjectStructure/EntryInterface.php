<?php

namespace YooMoney\Updater\ProjectStructure;

/**
 * Интерфейс файла или каталога проекта
 */
interface EntryInterface
{
    /**
     * Проверяет является ли текущий элемент директорией
     * @return bool True если текущий элемент директория, false если файл
     */
    function isDir();

    /**
     * Проверяет является ли текущий элемент файлом
     * @return bool True если текущий элемент файл, false если директория
     */
    function isFile();

    /**
     * Возвращает полный путь до текущего элемента
     * @return string Полный путь до файла или директории в структуре CMS
     */
    function getAbsolutePath();

    /**
     * Возвращает относительный путь до текущего элемента относительно корня CMS
     * @return string Относительный путь до файла или директории
     */
    function getRelativePath();

    /**
     * Возвращает относительный путь до файла относительно корня проекта в гитхабе
     * @return string Относительный путь в структуре проекта на гитхабе
     */
    function getProjectPath();
}
