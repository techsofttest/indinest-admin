\# Project Instructions — Indinest Ecommerce



\## Workspace Structure



This workspace contains two ecommerce projects:



\### TriangleCart — Reference

\- `trianglecart/` — Laravel backend + Filament admin

\- `trianglecart-front/` — Next.js frontend



TriangleCart is the existing/reference ecommerce application.



\### Indinest — Target

\- `indinest/` — Laravel backend + Filament admin

\- `indinest-front/` — Next.js frontend



Indinest is the new application being developed.



\## Core Rule



Treat TriangleCart as the functional and integration reference for Indinest.



When implementing a feature in Indinest:



1\. First inspect the corresponding implementation in TriangleCart.

2\. Understand its database structure, business logic, APIs, integrations, validation, and frontend behavior.

3\. Implement the equivalent functionality in Indinest.

4\. Adapt the implementation to Indinest's existing architecture, models, naming conventions, database structure, and UI.

5\. Do NOT blindly copy TriangleCart code.

6\. Do NOT modify TriangleCart unless explicitly instructed.



\## Reference Priority



TriangleCart should be used to understand:



\- Existing ecommerce business logic

\- Product/catalogue functionality

\- Categories and departments

\- Collections

\- Product variants

\- Cart

\- Wishlist

\- Customer authentication

\- Customer addresses

\- Checkout

\- Orders

\- Coupons

\- Delivery logic

\- Stripe/payment integrations

\- API conventions

\- Image handling

\- SEO functionality

\- CMS/blog functionality

\- Frontend components and UX



\## Framework Rules



Backend:

\- Laravel 12.x

\- Filament 5.x



Frontend:

\- Latest stable Next.js

\- React

\- TypeScript



Use current official documentation and current recommended APIs when implementing features.



If TriangleCart uses an outdated or deprecated approach, do not reproduce the outdated implementation blindly. Preserve the intended functionality while implementing it using the current recommended approach.



\## Architecture



Indinest consists of:



\- Laravel backend providing APIs

\- Filament CMS/admin panel

\- Next.js frontend consuming Laravel APIs



Keep backend and frontend responsibilities separate.



Do not move business logic into Next.js that belongs in the Laravel backend.



\## Database



Before creating or modifying migrations, models, relationships, or API resources:



1\. Inspect the existing Indinest database structure.

2\. Inspect the corresponding TriangleCart implementation.

3\. Determine what functionality needs to be preserved.

4\. Adapt the structure rather than unnecessarily duplicating TriangleCart tables.



Do not overwrite existing Indinest functionality without first understanding it.



\## API



When implementing an API in Indinest:



\- Inspect the equivalent TriangleCart API first.

\- Preserve useful response behavior where appropriate.

\- Follow Indinest's existing API conventions.

\- Validate input in Laravel.

\- Keep authorization/security checks on the backend.



\## Frontend



When implementing frontend functionality:



1\. Inspect the equivalent TriangleCart frontend implementation.

2\. Understand the user flow and business rules.

3\. Recreate the functionality using Indinest's frontend architecture.

4\. Do not assume TriangleCart component names, paths, or APIs exist in Indinest.



\## Payments



Stripe is the payment gateway for Indinest.



When implementing Stripe functionality, use the current Stripe documentation and recommended Laravel integration patterns.



TriangleCart may be used as a reference for the intended checkout/payment flow, but current Stripe documentation takes precedence over outdated implementation details.



\## Code Quality



Prefer:



\- Existing Indinest patterns

\- Small focused changes

\- Reusable services/components

\- Proper Laravel relationships

\- Form/request validation

\- Type-safe frontend code

\- Existing project conventions



Avoid:



\- Unnecessary rewrites

\- Duplicating existing functionality

\- Blindly copying TriangleCart

\- Modifying unrelated files

\- Adding unnecessary packages

\- Creating duplicate APIs/components when an existing one can be extended



\## Important



Before implementing a requested feature, search both:



1\. Indinest — to understand what already exists

2\. TriangleCart — to understand the reference implementation



Then implement only the required changes in Indinest.

