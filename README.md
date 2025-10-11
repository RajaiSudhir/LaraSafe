<h1 align="center"># Laravel Backup Manager 🚀</h1>

<p align="center">
    <img src="https://raw.githubusercontent.com/RajaiSudhir/LaraSafe/main/public/assets/images/logos/logo.png" width="300" alt="LaraSafe Logo">
</p>

<p align="center">
    <a href="https://github.com/RajaiSudhir/LaraSafe/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/RajaiSudhir/LaraSafe"><img src="https://img.shields.io/packagist/v/RajaiSudhir/LaraSafe" alt="Latest Version"></a>
    <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/packagist/l/RajaiSudhir/LaraSafe" alt="License"></a>
</p>

A powerful and flexible backup management solution built with Laravel, Inertia, and Vue. Effortlessly manage, schedule, and monitor backups for your projects with a sleek, modern interface.

---

## ✨ Quick Start

Follow these steps to set up and run the Laravel Backup Management System (LaraSafe):

1. **Clone the Repository**

   ```
   git clone https://github.com/RajaiSudhir/LaraSafe.git
   cd LaraSafe
   ```

2. **Install Dependencies**

   ```
   composer install
   npm install && npm run build
   ```

3. **Configure Environment**

   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set Up Database and Queue**

   - Edit `.env` to configure `DB_*` settings and set `QUEUE_CONNECTION=database`.

5. **Run Migrations and Seeders**

   ```
   php artisan migrate --seed
   ```

---

### ⚙️ Laravel Work & Server Permissions Setup

> Steps to manage **Laravel queue workers** and give proper **permissions and ownership** to allow editing and directory creation inside `/var/www`.

#### 🔍 1. Find the Laravel Queue Worker

```
ps aux | grep 'queue:work'
```

This command lists running queue workers.  
In this case, the process runs under the user `predator`.

#### 🧑‍💻 2. Change Ownership (Server)

```
# Change ownership recursively to user predator (if not already owned)
sudo chown -R predator:predator /var/www
```

#### 🔐 3. Grant Proper Permissions

```
# Ensure read/write/execute permissions for user predator on all files/folders inside
sudo chmod -R u+rwX /var/www
```

#### 💻 4. Local Development Path

```
/home/predator/Documents
```

✅ These steps ensure Laravel has proper permissions to edit files, create directories, and run queue workers smoothly both locally and on the server.

---

6. **Serve the Application**

   ```
   php artisan serve
   ```

7. **Get Started**

   - Log in with the default seeded user.
   - Create projects under **Manage Projects**.
   - Configure backups for each project.
   - Monitor stats, schedules, and downloads via the **Dashboard**.

---

## 🔥 Highlights

- **Fully Native**: Built using Laravel core, Inertia, and Vue.
- **Private Storage**: Backups stored securely in `storage/app/private/backups/{project}`.
- **Scheduling**: Supports daily, weekly, or monthly backups at custom times.
- **Integrity Checks**: Uses SHA-256 checksums and auto-expiry cleanup.
- **Interactive Dashboard**: Real-time stats, trends, timelines, and quick actions.
- **Production-Ready**: Includes migrations and seeders for seamless setup.

---

## 📚 Core Features

| **Category**    | **Details**                                                                      |
| --------------- | -------------------------------------------------------------------------------- |
| **Projects**    | Define multiple projects with custom file paths.                                 |
| **Backups**     | Configure backup frequency, timing, and retry pending backups.                   |
| **Storage**     | Secure, non-public disk with organized folder structure.                         |
| **Metadata**    | Tracks file size, checksum, and expiry date for each backup.                     |
| **Jobs/Queues** | Asynchronous ZIP creation via `BackupProjectJob`.                                |
| **Dashboard**   | Inertia + Vue interface with stats cards, tables, timeline, charts, and loaders. |
| **Analytics**   | Charts for storage usage, success/failure trends, and upcoming schedules.        |

---

## 🎯 Dashboard Overview

### Stats Cards

- Total Projects
- Total Backups & Today’s Count
- Storage Used & This Week’s Count
- Success Rate & Successful Backups Count

### Upcoming Backups

- Timeline of the next 7 days' scheduled backups with countdown timers.

### Recent Backups

- Displays the latest 10 backup operations with download links and status badges.

### Project Statistics

- Top 5 projects by backup count and total size, including last backup timestamp.

### Storage Usage

- Bar chart showing storage consumption per project.

### Quick Actions

- Create New Backup
- Manage Backups
- Manage Projects
- Settings

---

## 🔧 Configuration

### Filesystem (`config/filesystems.php`)

```
'disks' => [
    'private' => [
        'driver' => 'local',
        'root'   => storage_path('app/private'),
    ],
],
```

### Queue (`.env`)

```
QUEUE_CONNECTION=database
```

### Scheduler (`routes/console.php`)

```
Schedule::command('backups:dispatch-due')->everyMinute();
```

---

## 🚀 Upcoming Features

- Two-factor authentication (2FA) with TOTP codes and recovery support to harden account security.
- Role-based access control with team-aware permissions for multi-tenant and collaborative workflows.
- ZIP integrity verification via per-archive checksums (e.g., SHA-256) to detect tampering or corruption.
- Additional storage providers out of the box: Amazon S3, Backblaze B2, and DigitalOcean Spaces.
- Real-time notifications for backup lifecycle events (queued, running, success, failed) via broadcasting.

---

## 🚧 Roadmap

- 🔐 Enhanced authentication and profile management.
- 🤖 Notifications via Telegram, Slack, and Email.
- 💾 Full backups combining files and SQL dumps in one archive.
- ⚡ Incremental snapshots and image-based backups.
- 🔄 Web UI for file and database restoration.

Contributions are welcome! See the **Contributing** section below.

---

## 🤝 Contributing

1. Fork and clone the repository.
2. Create a feature branch.
3. Commit and push your changes.
4. Open a Pull Request.

Please adhere to our [Code of Conduct](link-to-code-of-conduct).

---

## 📄 License

Feel free to use, modify, and distribute!
```