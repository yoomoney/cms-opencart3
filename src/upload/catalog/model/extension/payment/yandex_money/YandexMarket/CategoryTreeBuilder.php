<?php

namespace YandexMoneyModule\YandexMarket;

/**
 * Класс хэлпера для построения дерева категорий
 *
 * @package YandexMoneyModule\YandexMarket
 */
class CategoryTreeBuilder
{
    /**
     * @var ProductCategory[] Массив корневых категорий
     */
    private $categories;

    /**
     * @var array Список дочерних узлов вида {<parentId>: [<childId>, ...], <parentId>: [...]}
     */
    private $parentIds;

    /**
     * Конструктор, устанавливает пустые списки категорий и родителей
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @return CategoryTreeBuilder
     */
    public function reset()
    {
        $this->categories = array();
        $this->parentIds = array();
        return $this;
    }

    /**
     * @param int $categoryId
     * @param int $parentCategoryId
     * @param string $name
     * @return ProductCategory
     */
    public function addCategory($categoryId, $parentCategoryId, $name)
    {
        $this->categories[$categoryId] = new ProductCategory($categoryId, $name);
        $this->parentIds[$parentCategoryId][] = $categoryId;
        return $this->categories[$categoryId];
    }

    /**
     * @return CategoryTreeBuilder
     * @throws \Exception
     */
    public function build()
    {
        foreach ($this->parentIds as $parentId => $childIds) {
            if (array_key_exists($parentId, $this->categories)) {
                $parent = $this->categories[$parentId];
                foreach ($childIds as $childId) {
                    if (array_key_exists($childId, $this->categories)) {
                        $parent->appendChild($this->categories[$childId]);
                    }
                }
            }
        }
        $this->parentIds = array();
        foreach ($this->categories as $index => $category) {
            if ($category->hasParent()) {
                unset($this->categories[$index]);
            }
        }
        return $this;
    }

    /**
     * @return ProductCategory[]
     * @throws \Exception
     */
    public function getRootCategories()
    {
        if (!empty($this->parentIds)) {
            $this->build();
        }
        return array_values($this->categories);
    }
}
