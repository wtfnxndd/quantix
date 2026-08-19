# Quantix

Quantix is a stock management system for monitoring inventory movement across products, warehouses, customers, vendors, and orders. It uses PHP, MySQL, XAMPP, HTML, CSS, Bootstrap, JavaScript, and Chart.js.

## Run With XAMPP

1. Install XAMPP with Apache, MySQL, and PHP.
2. Copy the `Quantix` folder to `C:\xampp\htdocs\Quantix`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`.
5. Import `C:\xampp\htdocs\Quantix\schema.sql`.
6. Open `http://localhost/Quantix/login.php`.

For the repository version, clone it directly into the XAMPP document root:

```powershell
git clone https://github.com/wtfnxndd/quantix.git C:\xampp\htdocs\Quantix
```

After pulling updates, refresh the browser and restart Apache only if PHP configuration changes.

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
- **Dashboard**: stock totals, low-stock alerts, quick actions, category chart, and selectable 7-, 30-, or 90-day movement charts.
- **Stock registration**: add daily-use products such as groceries, household items, stationery, and personal-care products.
- **Stock records**: inbound, outbound, adjustment, and warehouse transfer history with search and type filters.
- **Product management**: product classification, SKU, unit, reorder level, and stock status. Category, stock type, and unit fields provide 20 curated choices.
- **Warehouse management**: warehouse locations and current stock distribution.
- **Customer management**: add customers and edit or remove customer records.
- **Partner management**: customers and vendors used by order workflows.
- **Orders**: sales and purchase order summaries with links to prefilled inbound or outbound stock movements.
- **Release management**: record versions, notes, release dates, and release status.
- **Search**: search products, customers, and movement references.
- **SQL Console**: admin-only read-only SQL queries with interactive examples for stock, orders, vendors, releases, and audit history.
- **Query History**: audit trail for administrator SQL queries.
- **User management**: admin-only creation of staff and admin accounts.
- **Input protection**: CSRF tokens, server-side validation, duplicate handling, and safe allowlists for controlled fields.
- **Interactive UI**: loading states, dismissible notifications, reveal animations, hover feedback, and reduced-motion support.

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

The example buttons execute read-only queries immediately. Data-changing statements such as `INSERT`, `UPDATE`, `DELETE`, `DROP`, and `ALTER` are blocked.

## Roles

Staff can view dashboards, manage inventory, record movements, manage products, warehouses, customers, partners, orders, releases, and reports. Administrators also have access to user management, the SQL Console, and Query History.

## Security Notes

- Change the demo passwords before using the application outside a local demonstration.
- Keep database credentials in environment variables rather than committing secrets.
- Run the application behind HTTPS and disable detailed error output in production.
- Do not expose the SQL Console to non-administrator users.

## Project Files

- `index.php`: authenticated dashboard
- `login.php`, `logout.php`, `auth.php`: authentication
- `db.php`, `config.php`: database connection and helpers
- `schema.sql`: database tables and demo data
- `assets/app.css`: shared UI styling
- `assets/app.js`: shared browser interactions
