# OrderDesk - Complete Installation Guide for NameCheap Shared Hosting

## 🚀 Quick Start Installation

### Prerequisites
- NameCheap shared hosting account (Stellar Plus or higher)
- cPanel access
- Domain configured and pointing to your hosting

### Installation Files You'll Need
1. **All OrderDesk PHP files** (from this project)
2. **database_mysql_namecheap.sql** - MySQL database schema
3. **config_shared_hosting.php** - Configuration template

---

## 📋 Step-by-Step Installation

### Step 1: Prepare Your Files
1. Download all OrderDesk files from this project
2. Rename `config_shared_hosting.php` to `config.php`
3. Create a ZIP file containing all files

### Step 2: Access cPanel
1. Log into your NameCheap account
2. Navigate to **Hosting List** → **Manage**
3. Click **cPanel** to open control panel
4. Click **File Manager** icon

### Step 3: Upload Files
1. Navigate to `public_html` folder
2. Click **Upload** button
3. Select your OrderDesk ZIP file
4. After upload, right-click ZIP file → **Extract**
5. Move all extracted files to `public_html` root directory
6. Delete the ZIP file

### Step 4: Create MySQL Database
1. In cPanel, click **MySQL Databases**
2. **Create Database:**
   - Database Name: `yourusername_orderdesk`
   - Click "Create Database"
3. **Create Database User:**
   - Username: `yourusername_admin`
   - Password: [Create strong password - save it!]
   - Click "Create User"
4. **Grant Privileges:**
   - Select your database and user
   - Grant **ALL PRIVILEGES**
   - Click "Add"

### Step 5: Import Database Schema
1. In cPanel, click **phpMyAdmin**
2. Select your OrderDesk database from left sidebar
3. Click **Import** tab
4. Choose file: `database_mysql_namecheap.sql`
5. Click **Go**
6. Wait for "Import has been successfully finished"

### Step 6: Configure Database Connection
1. Edit `config.php` file in File Manager
2. Update these values:
```php
$db_host = 'localhost';
$db_name = 'yourusername_orderdesk';    // Your actual database name
$db_user = 'yourusername_admin';        // Your actual database user
$db_pass = 'your_strong_password';      // Your actual database password
```

### Step 7: Set File Permissions
1. Right-click `uploads` folder → **Permissions**
2. Set permissions to **755**
3. Check **Recurse into subdirectories**
4. Click **Change Permissions**

### Step 8: Test Installation
1. Visit your domain: `https://yourdomain.com`
2. You should see the OrderDesk login page
3. Test with demo credentials:
   - **Super Admin:** `superadmin` / `password`
   - **Admin:** `demoadmin` / `password`
   - **Agent:** `demoagent` / `password`

---

## 🔧 Post-Installation Configuration

### Security Setup (IMPORTANT!)
1. **Change Super Admin Password**
   - Login as Super Admin
   - Go to profile settings
   - Set a strong password

2. **Create Your Admin Account**
   - Go to User Management
   - Create your own admin account
   - Use strong credentials

3. **Remove Demo Accounts**
   - Delete or disable demo users
   - Keep only your accounts

4. **Enable SSL Certificate**
   - In cPanel, go to **SSL/TLS**
   - Install Let's Encrypt (free)
   - Force HTTPS redirect

### Customization
1. **Update Branding**
   - Login as Super Admin
   - Go to Branding Settings
   - Upload your logo
   - Customize colors and text

2. **Add Your Stores**
   - Login as Admin
   - Go to Store Management
   - Add your Shopify/WooCommerce stores

3. **Create Team Accounts**
   - Add your team members
   - Assign appropriate roles
   - Set permissions

---

## 🛠️ Troubleshooting

### Database Connection Issues
**Error:** "Database connection failed"
**Solution:**
- Verify database credentials in `config.php`
- Check database name matches exactly
- Ensure user has proper privileges

### File Upload Problems
**Error:** "File upload failed"
**Solution:**
- Check `uploads` folder permissions (755)
- Verify PHP upload limits in cPanel
- Ensure sufficient disk space

### Login Issues
**Error:** "Invalid credentials"
**Solution:**
- Clear browser cache
- Check database imported correctly
- Verify demo accounts exist

### Page Not Loading
**Error:** "Page not found"
**Solution:**
- Check all files uploaded to correct location
- Verify file permissions
- Check PHP error logs in cPanel

---

## 📊 Performance Optimization

### Enable Caching
1. In cPanel, enable **Cache Manager**
2. Set cache levels to "Aggressive"
3. Enable **Cloudflare** if available

### Optimize Images
- Upload compressed images
- Use WebP format when possible
- Set appropriate image sizes (max 1MB)

### Database Maintenance
- Regularly clean old audit logs
- Archive completed orders
- Monitor database size

---

## 🔄 Backup Strategy

### Automatic Backups
1. Enable **Backup Wizard** in cPanel
2. Set daily/weekly backups
3. Include both files and database

### Manual Backups
1. **Files:** Download via File Manager
2. **Database:** Export via phpMyAdmin
3. Store backups in secure location

---

## 🎯 Production Checklist

### Before Going Live
- [ ] Super Admin password changed
- [ ] Demo accounts removed
- [ ] SSL certificate installed
- [ ] Branding customized
- [ ] Team accounts created
- [ ] Stores configured
- [ ] Backup system set up
- [ ] Error logging enabled

### Ongoing Maintenance
- [ ] Monthly database cleanup
- [ ] Regular security updates
- [ ] Performance monitoring
- [ ] Backup verification
- [ ] User account auditing

---

## 📞 Support Resources

### NameCheap Support
- Knowledge Base: https://www.namecheap.com/support/knowledgebase/
- Live Chat: Available 24/7
- Ticket System: For complex issues

### OrderDesk Documentation
- User Manual: `USER_GUIDE.html`
- Deployment Guide: `DEPLOY_GUIDE.html`
- Demo Credentials: `DEMO_CREDENTIALS.md`

### Technical Requirements
- **PHP:** 7.4+ (8.0+ recommended)
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Memory:** 128MB+ (256MB recommended)
- **Disk Space:** 100MB+ (500MB recommended)

---

## 🎉 You're Ready!

Your OrderDesk installation is complete! The system includes:

✅ **Advanced Order Management** - Track orders from creation to delivery
✅ **Multi-Role Access Control** - Super Admin, Admin, and Agent portals
✅ **Courier Integration** - Built-in support for Pakistani couriers
✅ **Beautiful Analytics** - Real-time charts and performance metrics
✅ **Professional Branding** - Fully customizable appearance
✅ **Mobile Responsive** - Works on all devices
✅ **Dark/Light Mode** - Modern theme switching
✅ **Audit Logging** - Complete activity tracking

**Default Login Credentials:**
- Super Admin: `superadmin` / `password`
- Admin: `demoadmin` / `password`
- Agent: `demoagent` / `password`

**⚠️ Important:** Change these passwords immediately after installation!

---

## 📈 Next Steps

1. **Customize Your Brand** - Upload logo, set colors
2. **Add Your Stores** - Connect Shopify/WooCommerce
3. **Create Team Accounts** - Add your staff
4. **Import Orders** - Start managing orders
5. **Set Up Integrations** - Connect courier services

**Need Help?** Check the included documentation or contact support.

**Ready to scale your business?** OrderDesk is built for growth!