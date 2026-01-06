# Laravel E-commerce Shopping Cart System

A production-ready Laravel 12 e-commerce shopping cart system built with Livewire, following SOLID principles and using Service-Repository pattern.

## Features

-   **Product Management**: Browse products with name, price, and stock quantity
-   **Shopping Cart**: Database-backed cart system (not session-based)
-   **Stock Validation**: Prevents adding more items than available stock
-   **Low Stock Notifications**: Automatic email alerts when product stock ≤ 5
-   **Daily Sales Reports**: Scheduled daily reports via email
-   **Authentication**: Laravel Breeze with Livewire (login, register, password reset, email verification)
-   **Real-time Updates**: Livewire-powered real-time cart updates

## Architecture

This project follows **SOLID principles** and uses:

-   **Repository Pattern**: Data access layer abstraction
-   **Service Pattern**: Business logic encapsulation
-   **Dependency Injection**: All dependencies injected via constructor
-   **Interface Segregation**: Separate interfaces for each repository and service
-   **Dependency Inversion**: High-level modules depend on abstractions

### Architecture Overview

The application uses a layered architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                      Presentation Layer                      │
│  (Livewire Components - ProductList, CartShow, CartCounter) │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                      Service Layer                           │
│  (CartService, ProductService, NotificationService, etc.)   │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Repository Layer                          │
│  (CartRepository, ProductRepository, CartItemRepository)    │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                      Model Layer                             │
│  (Cart, Product, CartItem, User - Eloquent Models)          │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│  (MySQL/PostgreSQL/SQLite)                                   │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

**Cart Operations Flow:**

1. User interacts with Livewire component (e.g., `ProductList::addToCart()`)
2. Livewire component calls Service method (e.g., `CartService::addItemToCart()`)
3. Service validates business logic and calls Repository methods
4. Repository performs database operations via Eloquent Models
5. Stock is reserved/decreased immediately when items are added to cart
6. Stock is restored when items are removed or carts expire

**Notification Flow:**

1. Product stock changes trigger `ProductObserver` OR scheduled command runs
2. Low stock check command (`CheckLowStock`) batches products and dispatches notification
3. Notification is queued (`LowStockNotification` job)
4. Queue worker processes the job
5. `NotificationService` sends email via Mailtrap
6. Admin receives email notification

### Stock Reservation System

The system implements a **stock reservation** mechanism:

-   When items are added to cart → Stock decreases immediately
-   When cart item quantity increases → Additional stock is reserved
-   When cart item quantity decreases → Stock is restored
-   When items are removed from cart → Stock is restored
-   When carts expire (24 hours) → Stock is automatically restored

This ensures accurate stock tracking and prevents overselling.

### Project Structure

```
app/
├── Console/
│   └── Commands/
│       ├── CheckLowStock.php          # Scheduled command to check low stock
│       ├── DailySalesReport.php       # Scheduled command for daily reports
│       └── ExpireCarts.php            # Scheduled command to expire old carts
├── Contracts/
│   ├── Repositories/                  # Repository interfaces
│   │   ├── CartRepositoryInterface.php
│   │   ├── CartItemRepositoryInterface.php
│   │   └── ProductRepositoryInterface.php
│   └── Services/                      # Service interfaces
│       ├── CartServiceInterface.php
│       ├── ProductServiceInterface.php
│       ├── NotificationServiceInterface.php
│       └── ReportServiceInterface.php
├── Repositories/                       # Repository implementations
│   ├── CartRepository.php
│   ├── CartItemRepository.php
│   └── ProductRepository.php
├── Services/                           # Service implementations
│   ├── CartService.php                 # Cart business logic
│   ├── ProductService.php              # Product business logic
│   ├── NotificationService.php         # Email notification logic
│   └── ReportService.php               # Sales report generation
├── Models/                             # Eloquent models (data only)
│   ├── User.php                        # User model with carts relationship
│   ├── Product.php                     # Product model with cartItems relationship
│   ├── Cart.php                        # Cart model with user and cartItems relationships
│   └── CartItem.php                    # CartItem model with cart and product relationships
├── Livewire/                           # Livewire components
│   ├── ProductList.php                 # Product listing and add to cart
│   ├── CartShow.php                    # Cart display and management
│   ├── CartCounter.php                 # Cart item count badge
│   ├── Actions/
│   │   └── Logout.php                  # Logout action
│   └── Forms/
│       └── LoginForm.php               # Login form component
├── Jobs/                               # Queue jobs
│   └── LowStockNotification.php        # Queued job for low stock alerts
├── Notifications/                      # Email notifications
│   ├── LowStockAlert.php               # Single product low stock notification
│   ├── LowStockAlertBatch.php          # Batched low stock notification
│   └── DailySalesReport.php            # Daily sales report notification
├── Observers/                          # Model observers
│   └── ProductObserver.php             # Observes product updates for low stock
├── Exceptions/                         # Custom exceptions
│   ├── InsufficientStockException.php  # Thrown when stock is insufficient
│   └── CartNotFoundException.php       # Thrown when cart is not found
├── Http/
│   └── Controllers/
│       └── Auth/
│           └── VerifyEmailController.php
├── Providers/
│   ├── AppServiceProvider.php          # Service container bindings
│   └── VoltServiceProvider.php         # Livewire Volt provider
└── View/
    └── Components/
        ├── AppLayout.php               # Main application layout
        └── GuestLayout.php             # Guest layout for auth pages

routes/
├── web.php                             # Web routes (home, cart, auth)
├── auth.php                            # Authentication routes (Breeze)
└── console.php                         # Scheduled commands configuration

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2026_01_04_221506_create_products_table.php
│   ├── 2026_01_04_221518_create_carts_table.php
│   └── 2026_01_04_221531_create_cart_items_table.php
├── factories/
│   ├── ProductFactory.php              # Factory for generating test products
│   └── UserFactory.php                 # Factory for generating test users
└── seeders/
    ├── DatabaseSeeder.php               # Main seeder
    └── ProductSeeder.php                # Product seeder (creates 25 products)

resources/
├── views/
│   ├── livewire/
│   │   ├── product-list.blade.php      # Product listing view
│   │   ├── cart-show.blade.php         # Cart display view
│   │   └── cart-counter.blade.php      # Cart counter badge view
│   ├── layouts/
│   │   └── app.blade.php               # Main application layout
│   └── components/                     # Blade components (Breeze)
└── css/
    └── app.css                         # Tailwind CSS styles
```

## Requirements

-   PHP 8.2 or higher
-   Composer
-   Node.js and npm
-   Database (MySQL, PostgreSQL, or SQLite)

## Installation

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd E-commerce
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Install Node dependencies**

    ```bash
    npm install
    ```

4. **Environment setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Configure database**
   Edit `.env` file and set your database credentials:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=ecommerce
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

6. **Configure mail settings (Mailtrap)**

    **Setting up Mailtrap:**

    1. Create a free account at [mailtrap.io](https://mailtrap.io)
    2. Go to your inbox settings
    3. Select "SMTP Settings" → "Integrations" → "Laravel"
    4. Copy your credentials (Username and Password)

    **Configure `.env` file:**

    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=your_mailtrap_username        # From Mailtrap inbox settings
    MAIL_PASSWORD=your_mailtrap_password        # From Mailtrap inbox settings
    MAIL_FROM_ADDRESS=noreply@example.com       # Sender email address
    MAIL_FROM_NAME="E-commerce Store"           # Sender display name
    ADMIN_EMAIL=admin@example.com               # Where notifications are sent
    ```

    **Important Notes:**

    - `ADMIN_EMAIL`: This is where low stock alerts and daily sales reports will be sent
    - `MAIL_FROM_ADDRESS`: The "from" address shown in email headers
    - `MAIL_FROM_NAME`: The display name for the sender
    - **Rate Limiting**: Mailtrap's free plan limits to 2 emails per second. The system uses batched notifications to avoid rate limits.

7. **Configure queue**

    **Why Queue is Required:**

    All email notifications (`LowStockAlert`, `LowStockAlertBatch`, `DailySalesReport`) implement `ShouldQueue`, meaning they are processed asynchronously. The queue worker **must be running** for emails to be sent.

    **Configure `.env` file:**

    ```env
    QUEUE_CONNECTION=database
    ```

    **Queue Tables:**

    The queue system uses three database tables (created automatically by migrations):

    - `jobs`: Stores pending queue jobs
    - `job_batches`: Stores batch job information
    - `failed_jobs`: Stores failed jobs for debugging

    **Important:**

    - The `jobs` table is created automatically when you run `php artisan migrate`
    - You **must** run `php artisan queue:work` for emails to be sent
    - Without the queue worker, notifications will be queued but never processed

8. **Run migrations**

    ```bash
    php artisan migrate
    ```

    This creates all necessary tables including:

    - User authentication tables (`users`, `password_reset_tokens`, `sessions`)
    - E-commerce tables (`products`, `carts`, `cart_items`)
    - Queue tables (`jobs`, `job_batches`, `failed_jobs`)
    - Cache table

9. **Seed database**

    ```bash
    php artisan db:seed
    ```

10. **Build assets**
    ```bash
    npm run build
    ```

**Test Low Stock Notifications:**

```bash
php artisan cache:clear  # Reset notification flags
php artisan stock:check-low
```

**Test Daily Sales Report:**

```bash
php artisan report:daily-sales
```

Check your Mailtrap inbox for the emails. The system uses batched notifications to avoid Mailtrap's rate limit (2 emails/second).

## Running the Application

### Development Setup

You need to run **three separate processes** for the application to work fully:

1. **Start the development server** (Terminal 1)

    ```bash
    php artisan serve
    ```

2. **Start the queue worker** (Terminal 2) - **REQUIRED for email notifications**

    ```bash
    php artisan queue:work
    ```

    **Important:** Without the queue worker running, email notifications will be queued but never sent. The queue worker processes jobs asynchronously.

3. **Run the scheduler** (Terminal 3) - Optional for development, required for scheduled tasks

    ```bash
    php artisan schedule:run
    ```

    Or run it manually when needed:

    ```bash
    php artisan schedule:run
    ```

### Access the Application

-   Open your browser and navigate to `http://localhost:8000`
-   Register a new user at `/register` or login at `/login`
-   Browse products and add them to cart
-   View your cart at `/cart` (requires authentication)

### Process Overview

-   **Web Server**: Handles HTTP requests and serves the application
-   **Queue Worker**: Processes queued jobs (email notifications)
-   **Scheduler**: Runs scheduled commands (daily reports, low stock checks, cart expiration)

## Usage

### Products

-   Browse all products on the home page
-   Each product shows name, price, and stock quantity
-   Products with stock ≤ 5 are highlighted in red
-   Click "Add to Cart" to add products (requires authentication)

### Shopping Cart

-   View cart by clicking the cart icon in the navigation
-   Update item quantities (validated against stock)
-   Remove items from cart
-   Cart total is calculated automatically

### Low Stock Notifications

-   When a product's stock drops to ≤ 5, an email notification is automatically sent to the admin
-   Notifications are queued and processed asynchronously

### Daily Sales Report

-   Runs daily at 6:00 PM (configurable in `routes/console.php`)
-   Summarizes cart activity for the day
-   Sends email report to admin

## Testing

Run the test suite:

```bash
php artisan test
```

## Commands

### Essential Commands

-   `php artisan queue:work` - Process queued jobs (required for email notifications)
-   `php artisan schedule:run` - Run scheduled tasks
-   `php artisan stock:check-low` - Check for low stock and send notifications (scheduled every 10 minutes)
-   `php artisan report:daily-sales` - Generate daily sales report (scheduled daily at 6 PM)
-   `php artisan carts:expire` - Expire old carts and restore stock (scheduled hourly)
-   `php artisan queue:failed` - List failed jobs for debugging

### Main Tables

-   **users**: User accounts (id, name, email, password, timestamps)
-   **products**: Product catalog (id, name, price, stock_quantity, timestamps)
-   **carts**: User shopping carts (id, user_id, timestamps)
-   **cart_items**: Items in carts (id, cart_id, product_id, quantity, timestamps)
-   **jobs**: Queue jobs for email notifications
-   **failed_jobs**: Failed queue jobs for debugging

## Troubleshooting

**Emails not being sent:**

-   Ensure queue worker is running: `php artisan queue:work`
-   Check failed jobs: `php artisan queue:failed`
-   Verify Mailtrap credentials in `.env`

**Rate limiting errors:**

-   Wait a few seconds between test commands
-   System uses batched notifications to prevent this
-   Clear cache: `php artisan cache:clear`

**Queue worker issues:**

-   Check Laravel logs: `tail -f storage/logs/laravel.log`
-   Restart queue worker
-   Verify database connection
