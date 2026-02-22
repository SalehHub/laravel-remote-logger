<?php

namespace RemoteLogger;

class RemoteLogger
{
    protected static ?string $category = null;
    protected static ?string $subcategory = null;

    /**
     * Set the global category.
     */
    public static function setCategory(?string $category): void
    {
        static::$category = $category;
    }

    /**
     * Set the global category and subcategory.
     */
    public static function setContext(?string $category, ?string $subcategory = null): void
    {
        static::$category = $category;
        static::$subcategory = $subcategory;
    }

    /**
     * Set the global subcategory.
     */
    public static function setSubcategory(?string $subcategory): void
    {
        static::$subcategory = $subcategory;
    }

    /**
     * Get the global category.
     */
    public static function getCategory(): ?string
    {
        return static::$category;
    }

    /**
     * Get the global subcategory.
     */
    public static function getSubcategory(): ?string
    {
        return static::$subcategory;
    }

    /**
     * Clear the global category and subcategory.
     */
    public static function flush(): void
    {
        static::$category = null;
        static::$subcategory = null;
    }
}
