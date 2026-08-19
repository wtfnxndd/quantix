# Quantix

Quantix is a simple stock management system for monitoring daily inventory movement. It uses PHP, MySQL, XAMPP, HTML, CSS, Bootstrap, JavaScript, and Chart.js.

## Run With XAMPP

1. Install XAMPP with Apache, MySQL, and PHP.
2. Copy the `Quantix` folder to `C:\xampp\htdocs\Quantix`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`.
5. Import `C:\xampp\htdocs\Quantix\schema.sql`.
6. Open `http://localhost/Quantix/login.php`.

The default database connection is:

```text
Host: 127.0.0.1
Port: 3306
Database: quantix
User: root
Password: empty
```

Connection values can be overridden with `QUANTIX_DB_HOST`, `QUANTIX_DB_PORT`, `QUANTIX_DB_NAME`, `QUANTIX_DB_USER`, and `QUANTIX_DB_PASSWORD` environment variables.

## Local Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@quantix.local` | `admin123` |
| Staff | `staff@quantix.local` | `staff123` |

Change these passwords before using Quantix outside a local demo.

## Modules

- **Login and authentication**: session login, logout, password hashing, and role checks.
- **Dashboard**: stock totals, low-stock alerts, quick actions, category chart, and seven-day movement chart.
- **Stock registration**: add daily-use products such as groceries, household items, stationery, and personal-care products.
- **Stock records**: inbound, outbound, adjustment, and warehouse transfer history.
- **Product management**: product classification, SKU, unit, reorder level, and stock status.
- **Warehouse management**: warehouse locations and current stock distribution.
- **Customer management**: add customers and edit or remove customer records.
- **Partner management**: customers and vendors used by order workflows.
- **Orders**: sales and purchase order summaries.
- **Release management**: record versions, notes, release dates, and release status.
- **Search**: search products, customers, and movement references.
- **SQL Console**: admin-only read-only SQL queries.
- **Query History**: audit trail for administrator SQL queries.
- **User management**: admin-only creation of staff and admin accounts.

## Useful URLs

- Login: `http://localhost/Quantix/login.php`
- Dashboard: `http://localhost/Quantix/`
- Stock registration: `http://localhost/Quantix/stock-registration.php`
- Stock records: `http://localhost/Quantix/stock-records.php`
- Products: `http://localhost/Quantix/products.php`
- Warehouses: `http://localhost/Quantix/warehouses.php`
- Customers: `http://localhost/Quantix/customers.php`
- Search: `http://localhost/Quantix/search.php`
- Releases: `http://localhost/Quantix/releases.php`
- SQL Console: `http://localhost/Quantix/sql-console.php`
- Query History: `http://localhost/Quantix/query-history.php`
- Users: `http://localhost/Quantix/users.php`

## SQL Console Safety

The SQL Console is restricted to administrators and accepts only read-only statements beginning with `SELECT`, `SHOW`, `DESCRIBE`, `DESC`, or `EXPLAIN`. Every attempt is recorded in Query History with its status and result count.

## Project Files

- `index.php`: authenticated dashboard
- `login.php`, `logout.php`, `auth.php`: authentication
- `db.php`, `config.php`: database connection and helpers
- `schema.sql`: database tables and demo data
- `assets/app.css`: shared UI styling
- `assets/app.js`: shared browser interactions
