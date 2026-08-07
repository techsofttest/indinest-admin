\# Feature Implementation: Collections Module (MVP)



\## Objective



Implement a simple \*\*Collections\*\* feature that allows products to be grouped for merchandising and marketing.



Examples:



\* New Arrivals

\* Best Sellers

\* Sale

\* Gift Ideas

\* Summer Collection



A product can belong to multiple collections.



\---



\# 1. Database



\## Create `collections` table



Fields:



```text

id

name

slug (unique)

image (nullable)

timestamps

```



\---



\## Create pivot table



```text

collection\_product



collection\_id

product\_id

```



Use Laravel's standard many-to-many relationship.



\---



\# 2. Model Relationships



\## Collection



```php

public function products()

{

&#x20;   return $this->belongsToMany(Product::class);

}

```



\## Product



```php

public function collections()

{

&#x20;   return $this->belongsToMany(Collection::class);

}

```



\---



\# 3. Filament Collection Resource



\## Form



Fields:



\* Name

\* Slug (auto-generated from Name, editable)

\* Image (optional)



\---



\## Table



Columns:



\* Image

\* Name

\* Slug

\* Products Count

\* Created At



Enable:



\* Search

\* Sorting

\* Delete



No additional features are required.



\---



\# 4. Product Resource



Add a \*\*Collections\*\* field to the Product form.



Use a searchable MultiSelect with the relationship.



Requirements:



\* Searchable

\* Preloaded

\* Save using the many-to-many relationship



Example:



```php

MultiSelect::make('collections')

&#x20;   ->relationship('collections', 'name')

&#x20;   ->searchable()

&#x20;   ->preload();

```



\---



\# 5. Seeder



Create a `CollectionSeeder` using `updateOrCreate()` with the following default collections:



\* New Arrivals

\* Best Sellers

\* Featured

\* Sale

\* Gift Ideas



The seeder should be executed automatically during a fresh installation so these collections are available immediately.



\---



\# 6. Navigation



Place the resource under:



```text

Catalogue

&#x20;   Collections

```



\---



\# 7. Implementation Requirements



\* Laravel 12 best practices.

\* Filament v5 best practices.

\* Reuse existing project structure and components.

\* Keep the implementation minimal and production-ready.

\* Do not add unnecessary fields or functionality.



\---



\# Out of Scope (Do Not Implement)



\* Description

\* Active/Inactive toggle

\* Sort order

\* SEO fields

\* Collection banners

\* Automatic/rule-based collections

\* Relation Managers

\* Collection landing pages

\* Scheduling

\* Featured collection toggle

\* Additional metadata



