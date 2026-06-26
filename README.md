
## Setup

**1. Clone & place in XAMPP**
```
C:\xampp\htdocs\incident-system\
```

**2. Install dependencies**
```bash
composer install
```

**3. Import database**
- Open phpMyAdmin
- Create database: `incident_system`
- Import `incident_system.sql`

**4. Update base URL in `config/app.php`**
```php
define('BASE_URL', 'http://localhost/incident-system');
```

**5. Create uploads folder**
```
incident-system/uploads/
```

**6. Open in browser**
```
http://localhost/incident-system
```

---

##  Demo Credentials

| Role        | Email                 | Password   |
|-------------|-----------------------|------------|
| Super Admin | superadmin@system.com | Admin@1234 |
| Admin       | admin@system.com      | Admin@1234 |
| User        | user@system.com       | Admin@1234 |

---
