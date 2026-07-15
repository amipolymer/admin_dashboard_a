# 🛠️ Laravel 12 - APPL E-Digital Application
<!-- # 🛠️ Laravel 12 - APPL Quick Links Application -->

A Laravel based web application with authentication, role-based access (SuperAdmin, Admin, User), and mobile-friendly UI using Tailwind CSS.

**Documentation:** [docs/PROJECT.md](docs/PROJECT.md) · **OnGrid initiate API (pending your approval):** [docs/ONGRID_INITIATE_VERIFICATION.md](docs/ONGRID_INITIATE_VERIFICATION.md)

---

## 📦 Features

- Laravel 12
- Laravel Breeze (Auth scaffolding)
- Role-based access control (`superadmin`, `admin`, `user`)
- Custom `RoleMiddleware`
- Tailwind CSS layout
- Admin/SuperAdmin protected dashboards

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/your-username/project-name.git
cd project-name
composer install
npm install && npm run dev
cp .env.example .env
php artisan key:generate
```
### 2. Configure .env database settings
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3366
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=your_password
```
### 3.  Run 

```bash
php artisan migrate
php artisan serve
php artisan route:store
```