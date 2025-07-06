# OrderDesk NameCheap Deployment Checklist

## Pre-Deployment Preparation
- [ ] All OrderDesk files downloaded and organized
- [ ] ZIP archive created for upload
- [ ] NameCheap hosting account active
- [ ] Domain configured and DNS propagated
- [ ] cPanel login credentials available

## Database Setup
- [ ] MySQL database created in cPanel
- [ ] Database user created with strong password
- [ ] User granted ALL PRIVILEGES to database
- [ ] Database schema imported via phpMyAdmin
- [ ] Sample data verification (users, orders, stores)

## File Configuration
- [ ] All files uploaded to public_html root
- [ ] config.php updated with correct database credentials
- [ ] uploads folder permissions set to 755
- [ ] File structure verified (no nested directories)

## Security Verification
- [ ] SSL certificate installed and active
- [ ] HTTPS redirect configured
- [ ] Super admin password changed from default
- [ ] Demo account passwords updated/disabled
- [ ] File permissions properly set

## Functional Testing
- [ ] Homepage loads correctly
- [ ] Login system works with demo credentials
- [ ] All three dashboards (Super Admin, Admin, Agent) accessible
- [ ] Order management functions working
- [ ] File upload (screenshots) working
- [ ] User management system operational

## Production Optimization
- [ ] PHP version updated (7.4+ recommended)
- [ ] Caching enabled in cPanel
- [ ] Cloudflare/CDN configured (if available)
- [ ] Backup system configured
- [ ] Error logging enabled

## Post-Deployment Tasks
- [ ] Branding customization completed
- [ ] Real store data imported
- [ ] Team member accounts created
- [ ] System documentation provided to team
- [ ] Training completed for end users

## Monitoring & Maintenance
- [ ] Performance monitoring set up
- [ ] Backup schedule verified
- [ ] Security updates planned
- [ ] Support contact information documented

---

## Emergency Contacts
- **NameCheap Support**: Live chat available 24/7
- **Technical Issues**: Check cPanel error logs
- **Database Problems**: Use phpMyAdmin for direct access

## Quick Reference
- **Database File**: `database_mysql_namecheap.sql`
- **Config Template**: `config_shared_hosting.php`
- **Installation Guide**: `EASY_INSTALL_GUIDE.html`
- **Full Documentation**: `README_NAMECHEAP.md`

## Default Credentials (Change After Installation!)
- Super Admin: `superadmin` / `password`
- Admin: `demoadmin` / `password`  
- Agent: `demoagent` / `password`