## Nexhire / JobPortal (HTML + MySQL + PHP)

This project now uses a PHP JSON API (`api.php`) backed by your MySQL schema (`schema.sql`).

### 1) Create DB + import schema

- Create a database named `job_portal`
- Import `schema.sql` into it (phpMyAdmin / MySQL Workbench / CLI)

### 2) Configure database credentials

Edit `config.php`:

- `DB_HOST`, `DB_PORT`
- `DB_NAME` (default: `job_portal`)
- `DB_USER`, `DB_PASS`

### 3) Run the project

You must serve this folder through **PHP** (opening the HTML file directly won't work because it needs `api.php`).

Options:

- **XAMPP/MAMP/WAMP**: put this folder inside the web root, then open `Nexhire.html` in the browser.
- **PHP built-in server** (if PHP is installed):

```bash
cd "/Users/hamzi/Documents/DB PROject/phas"
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/Nexhire.html`

### 4) Quick health check

Open this in the browser:

- `api.php?action=init`

You should see JSON like:

```json
{ "ok": true, "data": { "companies": [...], ... } }
```

