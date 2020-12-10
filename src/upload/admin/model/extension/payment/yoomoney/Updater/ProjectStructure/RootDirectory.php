<?php

namespace YooMoney\Updater\ProjectStructure;

/**
 * Класс корневой директории с настройками файлов и директорий проекта
 *
 * @package YooMoney\Updater\ProjectStructure
 */
class RootDirectory implements DirectoryEntryInterface
{
    /**
     * @var string Абсолютный путь к директории CMS
     */
    private $absolutePath;

    /**
     * @var string Локальная папка в которой лежит репозиторий
     */
    private $projectPath;

    /**
     * @var DirectoryEntryInterface[] Массив директорий проекта
     */
    private $directories;

    /**
     * @var FileEntryInterface[] Массив файлов проекта
     */
    private $files;

    /**
     * @param string $absolutePath Полный путь до корневой директории CMS
     * @param string $projectPath Путь до проекта
     */
    public function __construct($absolutePath, $projectPath = '')
    {
        $this->absolutePath = $absolutePath;
        $this->projectPath = $projectPath;
        $this->directories = array();
        $this->files = array();
    }

    /**
     * Проверяет является ли текущий элемент директорией
     * @return bool Возвращает true
     */
    public function isDir()
    {
        return true;
    }

    /**
     * Проверяет является ли текущий элемент файлом
     * @return bool Возвращает false
     */
    public function isFile()
    {
        return false;
    }

    /**
     * Возвращает полный путь до текущего элемента
     * @return string Полный путь до файла или директории в структуре CMS
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    /**
     * Возвращает относительный путь до текущего элемента относительно корня CMS
     * @return string Относительный путь до файла или директории
     */
    public function getRelativePath()
    {
        return '';
    }

    /**
     * Возвращает относительный путь до файла относительно корня проекта в гитхабе
     * @return string Относительный путь в структуре проекта на гитхабе
     */
    public function getProjectPath()
    {
        return $this->projectPath;
    }

    /**
     * Возвращает список директорий внутри текущией директории
     * @return DirectoryEntryInterface[] Список поддиректорий
     */
    public function getDirectoryEntries()
    {
        return $this->directories;
    }

    /**
     * Фабрика директорий проекта
     * @param string $projectPath Путь до директории в проекте
     * @param string $relativePath Путь до директории относительно корня CMS
     * @return DirectoryEntryInterface Инстанс описания директории
     */
    public function factoryDirectory($projectPath, $relativePath)
    {
        $path = empty($this->projectPath) ? '' : ($this->projectPath . '/');
        $directory = new DirectoryEntry($this, $relativePath, $path . $projectPath);
        $this->directories[] = $directory;
        return $directory;
    }

    /**
     * Возвращает список файлов внутри текущей директории
     * @return FileEntryInterface[] Список файлов внутри директории
     */
    public function getFileEntries()
    {
        return $this->files;
    }

    /**
     * Фабрика файлов проекта
     * @param string $projectPath Путь до файла в проекте
     * @param string $relativePath Относительный путь до файла в CMS относительно её корня
     * @return FileEntryInterface Инстанс описания файла
     */
    public function factoryFile($projectPath, $relativePath)
    {
        $path = empty($this->projectPath) ? '' : ($this->projectPath . '/');
        $file = new FileEntry($this, $relativePath, $path . $projectPath);
        $this->files[] = $file;
        return $file;
    }

    /**
     * Устаналвивает текущий относительный путь внутри проекта
     * @param string $value Путь до файлов и директорий в проекте
     * @return RootDirectory Инстанс текущего объекта
     */
    public function setProjectPath($value)
    {
        $this->projectPath = $value;
        return $this;
    }
}
