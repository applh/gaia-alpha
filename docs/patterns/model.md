# Model Pattern

Models represent the data structures of your application. In Gaia Alpha, models are typically simple PHP classes (POPOs) that may or may not map directly to a database table.

## Architectural Role

1.  **Data Structure**: Define the shape of your data (properties, types).
2.  **Data Logic**: Include methods that operate *only* on the data held by the object (e.g., `getFullName()`, `isValid()`).
3.  **Persistence**: Can include static methods for retrieval (`find()`) or instance methods for saving (`save()`), though complex persistence logic is often better handled by a Mapper or Repository service.

## Golden Sample

```php
<?php

namespace YourPlugin\Model;

class Product
{
    public $id;
    public $name;
    public $price;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->price = $data['price'] ?? 0.0;
    }

    /**
     * Logic relative to the entity itself
     */
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
```

## Checklist

- [ ] Resides in `YourPlugin/class/Model/`.
- [ ] Uses correct namespace `YourPlugin\Model`.
- [ ] Focuses on data structure, not business logic.
