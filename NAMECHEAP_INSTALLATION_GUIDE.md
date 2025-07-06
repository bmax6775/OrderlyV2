# OrderDesk - NameCheap Shared Hosting Installation Guide

## Overview
This guide will walk you through installing OrderDesk on NameCheap shared hosting step by step. The process takes about 15-20 minutes and requires no technical expertise.

## Prerequisites
- NameCheap shared hosting account (Stellar Plus or higher recommended)
- FTP access credentials
- cPanel access
- Domain name configured

## Step 1: Download OrderDesk Files
1. Download all files from your OrderDesk project
2. Create a ZIP file containing all project files
3. Keep the ZIP file ready for upload

## Step 2: Access cPanel
1. Log into your NameCheap account
2. Go to "Hosting List" → "Manage" for your domain
3. Click "cPanel" to access your hosting control panel
4. Look for the "File Manager" icon and click it

## Step 3: Upload Files
1. In File Manager, navigate to `public_html` folder
2. Click "Upload" button at the top
3. Select your OrderDesk ZIP file and upload
4. After upload, right-click the ZIP file and select "Extract"
5. Move all extracted files to the root of `public_html`
6. Delete the ZIP file to keep things clean

## Step 4: Create MySQL Database
1. In cPanel, find "MySQL Databases" icon
2. Create a new database:
   - Database Name: `your_username_orderdesk`
   - Click "Create Database"
3. Create a database user:
   - Username: `your_username_admin`
   - Password: Create a strong password (save this!)
   - Click "Create User"
4. Add user to database:
   - Select your database and user
   - Grant "ALL PRIVILEGES"
   - Click "Add"

## Step 5: Import Database Schema
1. In cPanel, click "phpMyAdmin"
2. Select your OrderDesk database from the left sidebar
3. Click "Import" tab
4. Choose file: Select `database_mysql.sql` from your project
5. Click "Go" to import the database structure
6. Wait for "Import has been successfully finished" message

## Step 6: Configure Database Connection
1. In File Manager, edit the `config.php` file
2. Update the database settings:
```php
$db_host = 'localhost';
$db_name = 'your_username_orderdesk';  // Replace with your database name
$db_user = 'your_username_admin';      // Replace with your database user
$db_pass = 'your_password_here';       // Replace with your database password
```
3. Save the file

## Step 7: Set File Permissions
1. In File Manager, select the `uploads` folder
2. Right-click and select "Permissions"
3. Set permissions to 755 (read, write, execute for owner)
4. Check "Recurse into subdirectories"
5. Click "Change Permissions"

## Step 8: Test Installation
1. Visit your domain in a web browser
2. You should see the OrderDesk login page
3. Use these demo credentials to test:
   - **Super Admin**: Username: `superadmin`, Password: `password`
   - **Admin**: Username: `demoadmin`, Password: `password`
   - **Agent**: Username: `demoagent`, Password: `password`

## Step 9: Create Your First Admin Account
1. Log in as Super Admin
2. Go to "User Management" → "Approve Users"
3. Click "Add New User" to create your admin account
4. Set a strong password and assign "admin" role
5. Log out and log in with your new admin account

## Step 10: Security Configuration
1. Change the Super Admin password immediately
2. Delete or disable demo accounts
3. Configure SSL certificate in cPanel (free Let's Encrypt available)
4. Update any default settings in the branding section

## Troubleshooting Common Issues

### Database Connection Error
- Double-check database credentials in `config.php`
- Ensure database user has proper permissions
- Verify database name matches exactly

### File Upload Issues
- Check `uploads` folder permissions (should be 755)
- Verify PHP file upload limits in cPanel
- Ensure sufficient disk space

### Page Not Loading
- Check if all files uploaded correctly
- Verify file permissions
- Check PHP error logs in cPanel

### Login Problems
- Clear browser cache
- Check database connection
- Verify user exists in database

## Performance Optimization

### Enable Caching
1. In cPanel, enable "Cache Manager" if available
2. Set cache levels to "Aggressive"
3. Enable "Cloudflare" for additional speed

### Optimize Images
1. Compress uploaded images
2. Use WebP format when possible
3. Set appropriate image sizes

### Database Optimization
1. Regularly clean old audit logs
2. Archive completed orders
3. Monitor database size

## Backup Strategy
1. Set up automatic backups in cPanel
2. Download full site backups monthly
3. Export database regularly
4. Keep backups in separate location

## Support Resources
- NameCheap Knowledge Base
- OrderDesk Documentation
- cPanel Official Guides
- PHP/MySQL Tutorials

## Next Steps
1. Customize branding and colors
2. Add your stores and products
3. Set up user accounts for your team
4. Configure integrations if needed
5. Test all functionality thoroughly

## Important Notes
- Always keep backups before making changes
- Update PHP version to latest stable (7.4+ recommended)
- Monitor disk space and database size
- Keep your hosting plan updated for better performance

Your OrderDesk installation is now complete and ready for production use!