# OrderDesk - Order Management System

## Overview

OrderDesk is a comprehensive eCommerce order management system designed specifically for Shopify and dropshipping businesses. It provides a complete solution for managing orders, tracking shipments, handling customer calls, and coordinating between different team members (agents, admins, and super admins).

The system is built with simplicity and shared hosting compatibility in mind, using traditional web technologies that work well on budget hosting providers like Namecheap, HostGator, and Bluehost.

## System Architecture

### Technology Stack
- **Frontend**: HTML5, CSS3 (Bootstrap 5), Vanilla JavaScript
- **Backend**: Pure PHP (no frameworks)
- **Database**: MySQL (accessed via cPanel/phpMyAdmin)
- **Styling**: Bootstrap 5 with custom CSS variables for theming
- **File Handling**: PHP-based CSV parser and image upload system
- **Authentication**: Manual approval system with role-based access control

### Design Philosophy
The system follows a traditional multi-page application (MPA) architecture designed for shared hosting environments. This approach was chosen to:
- Maximize compatibility with budget hosting providers
- Minimize server requirements (no Node.js or complex build processes)
- Ensure easy deployment and maintenance
- Provide reliable performance on shared hosting

## Key Components

### Frontend Architecture
- **Responsive Design**: Bootstrap 5 for mobile-first responsive layouts
- **Dark Mode Support**: CSS custom properties with JavaScript toggle functionality
- **Interactive Elements**: Vanilla JavaScript for dynamic behavior
- **File Structure**: Organized with separate CSS and JS directories

### Backend Structure
- **Pure PHP**: No framework dependencies for maximum hosting compatibility
- **Database Layer**: Direct MySQL connections using PHP's native functions
- **File Upload System**: PHP-based image upload to `/uploads/screenshots/`
- **CSV Processing**: Built-in Excel/CSV parser for bulk order imports

### User Management System
- **Role-Based Access Control**: Three-tier system (Super Admin, Admin, Agent)
- **Manual Approval Process**: All signups require Super Admin approval
- **Pending Users Table**: Separate table for managing signup requests

### Order Management
- **Bulk Import**: CSV/Excel file processing for order imports
- **Status Tracking**: Multiple order states with update capabilities
- **Screenshot Uploads**: Evidence tracking for order fulfillment
- **Call Logging**: Built-in system for customer communication tracking

## Data Flow

### User Registration Flow
1. User submits signup form on public homepage
2. Data stored in `pending_users` table
3. Super Admin reviews and approves/rejects requests
4. Approved users gain system access based on assigned role

### Order Processing Flow
1. Orders imported via CSV upload or manual entry
2. Admin assigns orders to agents
3. Agents update order status and upload screenshots
4. System tracks progress and maintains audit trail

### Authentication Flow
- Session-based authentication using PHP sessions
- Role-based page access control
- Manual approval gate for new user access

## External Dependencies

### Third-Party Libraries
- **Bootstrap 5**: Frontend styling and components
- **Font Awesome 6**: Icon library for UI elements
- **Chart.js**: Data visualization for dashboards (referenced in tech stack)

### Hosting Requirements
- **PHP 7.4+**: Modern PHP version for core functionality
- **MySQL**: Database storage and management
- **cPanel Access**: For database management via phpMyAdmin
- **File Upload Support**: For screenshot and CSV handling

## Deployment Strategy

### Hosting Target
The system is specifically designed for shared hosting environments, particularly:
- Namecheap shared hosting
- HostGator
- Bluehost
- Other cPanel-based hosting providers

### Deployment Process
1. **File Upload**: Standard FTP/cPanel file manager upload
2. **Database Setup**: Create MySQL database via cPanel
3. **Configuration**: Update database connection settings
4. **Permissions**: Set proper file permissions for upload directories
5. **SSL Setup**: Configure SSL certificate for secure operations

### File Structure Optimization
- All assets (CSS, JS, images) use relative paths
- No build process required
- Direct deployment of source files
- Compatible with shared hosting file structures

## Recent Changes

- July 06, 2025: Complete NameCheap Shared Hosting Installation Guides
  - **Installation Documentation**: Created comprehensive installation guides for NameCheap shared hosting
  - **MySQL Database Schema**: Built MySQL-compatible database file with sample data
  - **Shared Hosting Configuration**: Created optimized config file for shared hosting environments
  - **Visual Installation Guide**: HTML-based step-by-step guide with Bootstrap styling
  - **Troubleshooting Resources**: Common issues and solutions for shared hosting deployment
  - **Production Checklist**: Security and performance optimization guidelines
  - **File Structure**: Organized installation files specifically for cPanel/shared hosting
- July 06, 2025: Complete Advanced Theme System and Portal Enhancement
  - **Advanced CSS Framework**: Created comprehensive theme system with CSS custom properties, gradients, and animations
  - **Interactive JavaScript System**: Built advanced OrderDeskApp with real-time animations, form enhancements, and notification system
  - **Super Admin Dashboard**: Complete redesign with floating crown animations, chart.js integration, and system health monitoring
  - **Admin Dashboard**: Professional admin portal with performance metrics, agent tracking, and store management
  - **Agent Dashboard**: Modern agent interface with task management and real-time updates
  - **Animation System**: Implemented fade-in, slide-in, scale-in, pulse, float, shimmer, and bounce-in animations
  - **Dark Mode Enhancement**: Advanced theme switching with smooth transitions and localStorage persistence
  - **Chart Integration**: Dynamic data visualization with Chart.js for orders, status tracking, and performance metrics
  - **Responsive Design**: Mobile-first approach with advanced grid systems and flexible layouts
  - **Performance Optimization**: Intersection Observer for scroll animations, debounced functions, and optimized rendering
- July 06, 2025: Login System Fixed and Complete Migration to Replit
  - **Login Issue Resolution**: Fixed JavaScript conflicts preventing form submission
  - **Simplified Login Script**: Replaced complex validation script with focused dark mode toggle
  - **Backend Validation**: Confirmed login functionality works with proper password hashing
  - **Demo Credentials**: Created easy-to-use test accounts (superadmin/demoadmin/demoagent with password "password")
  - **Theme Integration**: Enhanced CSS with proper dark/light mode support across all components
  - **Footer Enhancement**: Fixed footer styling and navigation consistency
  - **Complete Audit**: Comprehensive code review and theme improvements completed
- July 06, 2025: Migration to Replit and Enhanced Branding System
  - **Replit Migration**: Successfully migrated from Replit Agent to Replit environment
  - **Database Setup**: PostgreSQL database with complete schema and sample data
  - **Homepage Redesign**: Modern, professional homepage with Orderlyy branding
  - **Branding System**: Complete branding management for super admins
    - Logo upload functionality
    - Custom color scheme editor with live preview
    - Dynamic branding across all pages
    - Professional Orderlyy theme by default
  - **Static Assets**: Created missing CSS and JavaScript files
  - **Navigation Enhancement**: Fixed responsive navigation with dark mode toggle
- July 06, 2025: Pakistani Courier Integration System
  - **Courier APIs**: Built-in integrations for 6 major Pakistani couriers:
    - PostEx, Leopards Courier, M&P (CallCourier)
    - Trax Logistics, BlueEx, Swyft Logistics
  - **Admin Integration Panel**: Comprehensive management interface for:
    - API token/secret configuration per courier
    - Manual and auto-sync scheduling with cron support
    - Connection status monitoring and sync timestamps
    - Real-time sync logs with performance metrics
  - **Agent Courier Dashboard**: Dedicated interface for agents to:
    - View assigned courier orders in card-based layout
    - Update order statuses (unbooked, in_transit, delivered, returned)
    - Filter by courier and status with pagination
    - Add remarks and contact customers directly
  - **Database Schema**: Extended with courier-specific tables:
    - courier_integrations, courier_orders, courier_sync_logs
    - Order assignment mapping with agent tracking
    - Comprehensive audit trail for all courier operations
  - **Live Status Updates**: Real-time courier status reflecting agent assignments
- July 06, 2025: Enhanced Design and Branding System
  - **Homepage Redesign**: Modern, professional homepage with hero section, features grid, and pricing
  - **Branding System**: Complete customization system for super admins including:
    - Logo upload functionality with image management
    - Full color scheme customization (primary, secondary, accent, status colors)
    - App name, tagline, and footer text customization
    - Live preview of branding changes
    - Dynamic CSS variable integration for real-time updates
  - **Database Enhancement**: Added branding_settings table for persistent customization
  - **User Experience**: Improved navigation with fixed header and smooth scrolling
  - **Rebranding Ready**: Following Orderlyy concept with professional positioning
- July 06, 2025: Complete system redesign and enhancement
  - **Database Migration**: Full PostgreSQL migration with optimized schema
  - **Premium Design**: Professional yellow (#ffc500) and black color scheme
  - **Analytics Fix**: Working PostgreSQL queries with Chart.js data visualization
  - **Agent Management**: Complete agent system with order assignment functionality
  - **Security Enhancement**: Removed demo credentials for production readiness
  - **Screenshot System**: Fixed upload issues and added automatic image compression
  - **Performance**: Image compression reduces file sizes for faster website loading
  - **UI Improvements**: Enhanced styling across all portals with premium appearance
- July 05, 2025: Initial setup

## Changelog

- July 05, 2025: Initial setup

## User Preferences

Preferred communication style: Simple, everyday language.