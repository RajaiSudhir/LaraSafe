<!-- Animated Typing Header -->
<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Fira+Code&size=28&pause=1000&color=00C4FF&center=true&vCenter=true&width=700&lines=Hey%2C+I'm+Sudhir+Rajai!+👋;Full+Stack+Developer+%7C+Laravel+%7C+PHP;Building+Clean+%26+Scalable+Web+Apps+🚀;DevOps+Learner" alt="Typing SVG" />
</div>

---

<p align="center">
  <a href="https://www.linkedin.com/in/sudhir-rajai-52796a214/">
    <img src="https://img.shields.io/badge/LinkedIn-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white" />
  </a>
  <a href="https://github.com/sudhirrajai">
    <img src="https://img.shields.io/badge/GitHub-171515?style=for-the-badge&logo=github&logoColor=white" />
  </a>
  <a href="https://clotheeo.com">
    <img src="https://img.shields.io/badge/Clotheeo.com-E34F26?style=for-the-badge&logo=wordpress&logoColor=white" />
  </a>
  <a href="https://sudhirinsights.com">
    <img src="https://sudhirinsights.com/wp-content/uploads/2025/08/logo-dark-2.png.webp" alt="Sudhir Insights" height="32" style="vertical-align:middle; border-radius:6px;" />
  </a>
</p>

---

### 👨‍💻 About Me

- 💡 Passionate about **Web Development**, **Cloud**, and **DevOps**  
- 🧠 Currently learning **Docker**, **AWS**, and **CI/CD Pipelines**  
- 💾 Creator of **[LaraSafe](https://github.com/sudhirrajai/LaraSafe)** — Laravel Backup Manager  
- 🧩 Blogger at **[Sudhir Insights](https://sudhirinsights.com)** — sharing tech, code, and life insights  
- ⚙️ Focused on **clean code**, **security**, and **automation-driven** workflows  

---

### 🧩 Tech Stack

<p align="center">
  <img src="https://skillicons.dev/icons?i=php,laravel,mysql,html,css,js,bootstrap,vue,git,linux,github,cloudflare,nginx,docker,aws&theme=light" />
</p>

<p align="center">
  <img src="https://raw.githubusercontent.com/PKief/vscode-material-icon-theme/main/icons/file_type_composer.svg" width="40" alt="Composer" />
</p>

---

### 🚀 Featured Projects & Content

#### 🧠 [LaraSafe](https://github.com/sudhirrajai/LaraSafe)  
> A **Laravel Backup Manager** built exclusively for Laravel projects.  
> Manage backups, schedule automations, and restore securely — all within one dashboard.

#### 💡 [Sudhir Insights](https://sudhirinsights.com)

> My personal **tech blog**, where I write about **Laravel**, **PHP**, **DevOps**, **AI**, **AWS**, and **industry insights** — tutorials, thoughts, and trends.

#### 👕 [Clotheeo.com](https://clotheeo.com)

> A full-fledged **fashion brand website**, featuring collections like **Anime Paradise**, **Acid Wash**, and **Solid Colors**, powered by WooCommerce & custom styling.

#### 🌍 [Village Info Project](https://villageonweb.in)

> Displays village-level data of India with **multi-database architecture**.
> Includes **super admin control**, **village-specific databases**, and smooth data synchronization.

#### 🧩 [TILD Project](#)

> A data-driven platform for analyzing **dysgraphia cases** using **PHP**, **AJAX**, and **ML API integration**, featuring an admin panel and live data visualization.

---

### ⚙️ Setup & Configuration

#### 🧰 **Installation Steps**

1. Clone the repository  
   ```bash
   git clone https://github.com/sudhirrajai/LaraSafe.git
   cd LaraSafe
   ````

2. Install dependencies

   ```bash
   composer install
   npm install && npm run dev
   ```

3. Configure your `.env` file

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Run migrations and seeders

   ```bash
   php artisan migrate --seed
   ```

5. Start the development server

   ```bash
   php artisan serve
   ```

---

### 🔐 Permissions Configuration

Ensure your Laravel backup system can access the necessary storage paths and perform backup operations correctly.

#### **Set proper folder permissions**

Run the following commands:

```bash
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
```

#### **Optional: Backup Directory Permissions**

If you’re storing backups in a custom path:

```bash
sudo chmod -R 775 /path/to/larasafe/backups
sudo chown -R $USER:www-data /path/to/larasafe/backups
```

✅ These permissions ensure your **scheduler**, **artisan commands**, and **Laravel queue workers** can run without permission issues.

---

### ⚙️ Laravel Work & Server Permissions Setup

> Steps to manage **Laravel queue workers** and give proper **permissions and ownership** to allow editing and directory creation inside `/var/www`.

#### 🔍 1. Find the Laravel Queue Worker

```bash
ps aux | grep 'queue:work'
```

This command lists running queue workers.
In my case, the process runs under the user **predator**.

#### 🧑‍💻 2. Change Ownership (Server)

```bash
# Change ownership recursively to user predator (if not already owned)
sudo chown -R predator:predator /var/www
```

#### 🔐 3. Grant Proper Permissions

```bash
# Ensure read/write/execute permissions for user predator on all files/folders inside
sudo chmod -R u+rwX /var/www
```

#### 💻 4. Local Development Path

```bash
/home/predator/Documents
```

✅ These steps ensure Laravel has proper permissions to edit files, create directories, and run queue workers smoothly both locally and on the server.

---

### ⚙️ Tools & Environments I Use

<p align="center">
  <img src="https://skillicons.dev/icons?i=vscode,postman,git,linux,figma,notion,vercel,php,laravel,docker,aws&theme=light" />
</p>

---

### 📊 GitHub Stats

<p align="center">
  <img src="https://github-readme-stats.vercel.app/api?username=sudhirrajai&show_icons=true&theme=tokyonight&hide_border=true" height="150px" />
  <img src="https://github-readme-streak-stats.herokuapp.com?user=sudhirrajai&theme=tokyonight&hide_border=true" height="150px" />
</p>

<p align="center">
  <img src="https://github-readme-activity-graph.vercel.app/graph?username=sudhirrajai&theme=react-dark&hide_border=true&area=true" />
</p>

---

### 🧠 Currently Exploring

* 🐳 Docker & Containerization
* ☁️ DevOps: **CI/CD**, **AWS EC2**, **Nginx**
* 🔐 Advanced Laravel Security, APIs & Backup Automation

---

### 🌐 Connect With Me

<p align="center">
  <a href="https://www.linkedin.com/in/sudhir-rajai-52796a214/">
    <img src="https://img.shields.io/badge/-Sudhir%20Rajai-blue?style=for-the-badge&logo=Linkedin&logoColor=white" />
  </a>
  <a href="mailto:rajaisudhir11@gmail.com">
    <img src="https://img.shields.io/badge/-rajaisudhir11@gmail.com-D14836?style=for-the-badge&logo=gmail&logoColor=white" />
  </a>
</p>

---

<h3 align="center">✨ “Keep building, keep learning — one commit at a time.” ✨</h3>

<p align="center">
  <img src="https://raw.githubusercontent.com/Platane/snk/output/github-contribution-grid-snake-dark.svg" alt="snake animation" />
</p>