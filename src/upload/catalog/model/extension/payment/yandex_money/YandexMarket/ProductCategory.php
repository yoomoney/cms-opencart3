<?php

namespace YandexMoneyModule\YandexMarket;

class ProductCategory
{
    /**
     * @var int Идентификатор категории
     */
    private $id;

    /**
     * @var string Название категории
     */
    private $name;

    /**
     * @var ProductCategory|null Родительская категория или null если текущая категория корневая
     */
    private $parent;

    /**
     * @var ProductCategory[] Массив дочерних категорий
     */
    private $children;

    /**
     * ProductCategory constructor.
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id = (int)$id;
        $this->name = mb_substr($name, 0, 32, 'utf-8');
        $this->parent = null;
        $this->children = null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ProductCategory|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * @return ProductCategory[]
     */
    public function getChildren()
    {
        return $this->children === null ? array() : $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @param ProductCategory $childCategory
     * @return ProductCategory
     * @throws \Exception
     */
    public function appendChild(ProductCategory $childCategory)
    {
        $parent = $this;
        do {
            if ($parent === $childCategory) {
                throw new \Exception('Invalid hierarchy');
            }
            $parent = $parent->parent;
        } while ($parent !== null);
        if ($childCategory->parent !== null) {
            if ($childCategory->parent === $this) {
                return $this;
            }
            unset($childCategory->parent->children[$childCategory->id]);
        }
        $this->children[$childCategory->id] = $childCategory;
        $childCategory->parent = $this;
        return $this;
    }
}