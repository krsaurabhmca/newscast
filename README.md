# Newscast - Professional News Portal CMS

A modern, high-performance, and mobile-responsive News Portal CMS built with PHP and MySQL. Designed with premium aesthetics inspired by top-tier digital news platforms like Dainik Bhaskar.

![Dashboard Preview](https://via.placeholder.com/800x450?text=Newscast+Dashboard)

## 🚀 Core Features

### 📰 Content Management
- **Smart Editorial Workflow**: Create, draft, and publish articles with ease.
- **Dynamic Categories**: Organize news into unlimited sections with custom icons and colors.
- **Multimedia Support**: Integrated YouTube video support and high-performance image handling.
- **Top 10 Stories**: Automated ranking system for most popular news.

### 📺 Live Broadcasting
- **One-Click Live Stream**: Broadcast YouTube live streams directly on the homepage.
- **Hero Replacement**: Live streams automatically replace the "Lead Story" when active.
- **Control Blocking**: Secure transparent layer to prevent users from interacting with player controls.

### 💰 Monetization
- **Strategic Ad Slots**: Header, Sidebar, and post-content advertisement placement.
- **Performance Tracking**: Built-in analytics for ad impressions, clicks, and CTR.
- **Direct Actions**: WhatsApp and Phone Call integration for ad links.

### 🛡️ Admin & Security
- **Professional Dashboard**: Clean, modern UI with real-time stats and metrics.
- **Role-Based Access**: Manage both Administrators and Editors.
- **Profile Management**: Personalized contributor profiles with image uploads.
- **Secure Auth**: Robust login system with password hashing and session management.

## 🛠️ Technology Stack
- **Backend**: PHP 7.4+ (PDO for Database)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System)
- **Icons**: Feather Icons

## ⚙️ Installation & Setup

1. **Upload Files**: Transfer the project files to your web server (e.g., XAMPP htdocs or live public_html).
2. **Launch Setup Wizard**: Visit your website URL in any browser.
3. **Automatic Configuration**:
   - The **Setup Wizard** will launch automatically.
   - Enter your Database credentials (Host, User, Pass). The wizard will create the database for you if it doesn't exist.
   - Create your Admin account and enter your Site Name.
   - Click "Finish" and you are ready to go!

4. **Post-Setup**:
   - For security, delete the `install.php` file from your server.
   - Ensure `assets/images/posts/` and `assets/images/ads/` folders have write permissions.

4. **Directory Permissions**
   - Ensure `assets/images/posts/`, `assets/images/ads/`, and site root are writable for uploads.

## 📁 Project Structure
- `/admin`: Backend management portal.
- `/includes`: Core configurations and helper functions.
- `/assets`: CSS, JS, and uploaded media.
- `/category`: Dynamic category handling.
- `/article`: Single post view logic.

## 📝 License
Distributed under the MIT License. See `LICENSE` for more information.

---
*Developed with ❤️ by the Newscast Team.*
