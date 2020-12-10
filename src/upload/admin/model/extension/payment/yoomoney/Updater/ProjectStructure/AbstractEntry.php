<?php

namespace YooMoney\Updater\ProjectStructure;

/**
 * Абстрактный класс файла или дериктории в проекте
 */
abstract class AbstractEntry implements EntryInterface
{
    /**
     * @var DirectoryEntryInterface|null Базовая директория проекта
     */
    private $base;

    /**
     * @var string Относительный путь до файла или дериктории внутри CMS
     */
    private $relativePath;

    /**
     * @var string Относительный путь до файла или директории внутри проекта
     */
    private $projectPath;

    /**
     * Конструктор устанавливает базовую директорию и относительные пути
     *
     * @param DirectoryEntryInterface $base Базовая директория
     * @param string $relativePath Относительный путь до файла или дериктории внутри CMS
     * @param string $projectPath Относительный путь до файла или директории внутри проекта
     */
    public function __construct(DirectoryEntryInterface $base, $relativePath, $projectPath)
    {
        $this->base = $base;
        $this->relativePath = $relativePath;
        $this->projectPath = $projectPath;
    }

    /**
     * Проверяет является ли текущий элемент файлом
     * @return bool True если текущий элемент файл, false если директория
     */
    public function isFile()
    {
        return !$this->isDir();
    }

    /**
     * Возвращает полный путь до текущего элемента
     * @return string Полный путь до файла или директории в структуре CMS
     */
    public function getAbsolutePath()
    {
        return $this->base->getAbsolutePath() . '/' . $this->relativePath;
    }

    /**
     * Возвращает относительный путь до текущего элемента относительно корня CMS
     * @return string Относительный путь до файла или директории
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * Возвращает относительный путь до файла относительно корня проекта в гитхабе
     * @return string Относительный путь в структуре проекта на гитхабе
     */
    public function getProjectPath()
    {
        return $this->projectPath;
    }
}
