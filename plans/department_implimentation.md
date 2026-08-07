\# Feature Implementation: Departments Module



\## Objective



Introduce a new \*\*Departments\*\* module to organize products at a higher level.



ignore front end for now, just focus on filament admin



Hierarchy:



```text

Department

&#x20;   ↓

Category

&#x20;   ↓

Product

```



Example:



```text

Men

&#x20;   Clothing

&#x20;   Shoes



Women

&#x20;   Clothing

&#x20;   Jewellery



Kids

&#x20;   Clothing

&#x20;   Toys



Gifts

&#x20;   Gift Hampers

&#x20;   Gift Cards

```



\---



\# 1. Create Department Model



Create a new `Department` model and migration.



Table structure:



```text

id

name

slug (unique)

image (nullable)

description (nullable)

timestamps

```



Do \*\*not\*\* add:



\* Active toggle

\* Sort order

\* Soft deletes



Keep the implementation simple.



\---



\# 2. Automatically Seed Default Departments



Create a seeder for the Departments table.



Default records:



\* Men

\* Women

\* Kids

\* Gifts



Generate slugs automatically:



```text

men

women

kids

gifts

```



The seeder should use `updateOrCreate()` (or equivalent) so running it multiple times does not create duplicates.



The migration should automatically execute this seeder after creating the table so that a fresh installation already contains the default departments.



\---



\# 3. Create Filament Department Resource



Create a standard Filament Resource.



Form fields:



\* Name

\* Slug (auto-generated from Name but editable)

\* Image

\* Description



Table columns:



\* Image

\* Name

\* Slug

\* Created At



Enable:



\* Search

\* Sorting



Navigation:



```text

Catalogue

&#x20;   Departments

```



\---



\# 4. Update Categories



Add a nullable `department\_id` foreign key to the Categories table.



Requirements:



\* Create the migration.

\* Add model relationships.



Department



```php

hasMany(Category::class)

```



Category



```php

belongsTo(Department::class)

```



Update the Category Filament Resource:



\* Add a searchable Department Select field.

\* Show Department in the table.

\* Add a Department filter.



Do not allow deleting a Department if it is assigned to one or more Categories. Display a friendly validation message instead of a database exception.



\---



\# 5. Update Products



Do \*\*not\*\* add `department\_id` to the Products table.



Products should continue storing only:



```text

category\_id

```



The Department should always be derived through the selected Category.



Example:



```text

Product

&#x20;   → Category

&#x20;       → Department

```



\---



\# 6. Update Product Resource



Update the Product Resource to improve usability.



Requirements:



\* Display the Department automatically based on the selected Category.

\* The Department field should be read-only.

\* When the Category changes, the displayed Department should update automatically.

\* Add a Department column to the Products table.

\* Allow filtering Products by Department using the Category relationship.



\---





\---



\# 8. Implementation Requirements



\* Laravel 12 best practices.

\* Filament v5 best practices.

\* Reuse existing project structure and components.

\* Keep the implementation clean, simple, and production-ready.

\* Do not introduce unnecessary features or complexity.

\* Ensure all relationships, validation, and CRUD operations work correctly.



