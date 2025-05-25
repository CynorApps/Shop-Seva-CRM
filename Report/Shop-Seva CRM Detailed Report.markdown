# Shop-Seva CRM Detailed Report

## Introduction
Shop-Seva CRM is a lightweight, user-friendly, and efficient Customer Relationship Management (CRM) system designed specifically for small to medium-sized retail shops, such as kirana stores, general stores, and small businesses in India. The primary goal of Shop-Seva is to empower shop owners with a simple, affordable, and powerful tool to manage their daily operations, track sales, analyze business performance, and ensure data security through regular backups. 

This CRM system addresses the challenges faced by small shop owners who often lack the budget or technical expertise to adopt complex software solutions. With features like sales tracking, performance analytics, inventory management, and a backup system, Shop-Seva provides actionable insights and operational efficiency, enabling shop owners to make data-driven decisions and grow their businesses.

**Project Name**: Shop-Seva CRM  
**Developed By**: [Your Name/Your Company Name]  
**Purpose**: To provide small shop owners with an affordable, easy-to-use CRM system for managing sales, inventory, and business analytics.  
**Target Audience**: Small shop owners in Tier-2, Tier-3 cities, and rural areas of India.  
**Date**: May 25, 2025

**Image Insert**: Shop-Seva CRM Logo or Homepage Screenshot

---

## System Overview
Shop-Seva CRM is a web-based application built to streamline the daily operations of small retail shops. The system is designed with simplicity in mind, ensuring that even shop owners with minimal technical knowledge can use it effectively. It runs on a PHP and MySQL backend, with a responsive HTML/CSS/JavaScript frontend, making it accessible on both desktop and mobile devices (though a dedicated mobile app is planned for the future).

### Key Objectives
- **Operational Efficiency**: Automate tasks like billing, inventory tracking, and sales recording.
- **Business Insights**: Provide actionable insights through performance analytics (e.g., sales trends, top products).
- **Data Security**: Ensure data safety with a robust backup system.
- **Affordability**: Offer a cost-effective solution tailored for small businesses.

### Target Users
- **Primary User**: Shop owner (Admin role) who manages the entire system.
- **Secondary Users**: Up to 2 workers (optional) who assist with sales and inventory tasks.
- **User Capacity**: Each instance supports 1 admin and a maximum of 2 workers, with minimal server resource usage.

**Image Insert**: System Workflow Diagram (e.g., Admin -> Features -> Data Flow)

---

## Feature Details
Below is a comprehensive breakdown of all features in Shop-Seva CRM, including their purpose, functionality, benefits, and technical implementation details.

### 1. Admin Dashboard
- **Purpose**: Serve as the central hub for the admin to access all features and get a quick overview of the business.
- **Functionality**:
  - Upon login, the admin is redirected to the dashboard (`admin_dashboard.php`).
  - Displays a summary of key metrics, such as total sales for the day, recent sales activity, and low stock alerts.
  - A sidebar provides navigation links to all features: Staff, Products, Inventory, Billing, Billing History, Backup, Performance, and Messages.
  - Includes a profile section showing the admin’s name and a logout option.
- **Benefits**:
  - Centralized access to all features, saving time.
  - Quick overview of business health (e.g., daily sales, stock status).
  - User-friendly interface with a clean design for easy navigation.
- **Technical Notes**:
  - File: `admin_dashboard.php`.
  - Uses PHP sessions to ensure secure access (only users with role "Admin" can access).
  - Styled with `style.css` for consistency across pages.
  - Responsive design using CSS media queries for mobile compatibility.

**Image Insert**: Screenshot of Admin Dashboard

### 2. Staff Management
- **Purpose**: Allow the admin to manage worker details (though in this version, only the admin is mandatory, with up to 2 optional workers).
- **Functionality**:
  - **Add Staff**: A form to add new workers (fields: name, email, password, role).
  - **View Staff**: Displays a table of all workers (columns: name, email, role, actions).
  - **Edit Staff**: Admins can update worker details (e.g., name, email, password, role).
  - **Delete Staff**: Admins can remove workers from the system.
- **Benefits**:
  - Provides control over staff management (useful if workers are added in the future).
  - Role-based access ensures security (e.g., workers cannot access admin features).
  - Easy to update or remove staff as needed.
- **Technical Notes**:
  - File: `staff.php`.
  - Database table: `users` (columns: `id`, `name`, `email`, `password`, `role`).
  - Uses prepared statements to prevent SQL injection.
  - Form validation ensures required fields are filled.

**Image Insert**: Screenshot of Staff Management Page (Showing Add Staff Form and Staff List Table)

### 3. Product Management
- **Purpose**: Manage the shop’s product catalog, including adding, editing, and deleting products.
- **Functionality**:
  - **Add Product**: A form to add new products (fields: name, price, quantity).
  - **View Products**: A table listing all products (columns: name, price, quantity, actions).
  - **Edit Product**: Admins can update product details (e.g., price, quantity).
  - **Delete Product**: Admins can remove products from the catalog.
- **Benefits**:
  - Keeps the product catalog up-to-date.
  - Simplifies inventory tracking by maintaining accurate product data.
  - Helps prevent errors during billing by ensuring correct product details.
- **Technical Notes**:
  - File: `products.php`.
  - Database table: `products` (columns: `id`, `name`, `price`, `quantity`).
  - Input validation ensures price and quantity are numeric values.
  - Uses MySQL queries to fetch and update product data.

**Image Insert**: Screenshot of Product Management Page (Showing Add Product Form and Product List Table)

### 4. Inventory Management
- **Purpose**: Track stock levels and provide alerts for low stock.
- **Functionality**:
  - Displays a table of all products with their current stock levels (quantity).
  - Highlights products with low stock (e.g., quantity < 5) in red for easy identification.
  - Allows admins to update stock levels directly from the product management section.
- **Benefits**:
  - Prevents stock-outs by alerting admins to low stock levels.
  - Helps plan restocking activities in advance.
  - Reduces manual effort in tracking inventory.
- **Technical Notes**:
  - File: `inventory.php`.
  - Database table: `products`.
  - Uses conditional CSS styling to highlight low stock (e.g., `background-color: red` for quantity < 5).

**Image Insert**: Screenshot of Inventory Management Page (Showing Low Stock Alerts)

### 5. Billing System
- **Purpose**: Facilitate sales transactions by creating bills for customers.
- **Functionality**:
  - **Create Bill**: A form where admins can select products, specify quantities, and generate a bill.
  - Calculates the total amount automatically (quantity × price per product).
  - Saves the bill to the database (`sales` and `sale_items` tables).
  - Generates a downloadable PDF of the bill for printing or sharing.
- **Benefits**:
  - Speeds up the billing process, reducing customer wait time.
  - Automatically records sales data for future reference.
  - PDF bills provide a professional way to share receipts with customers.
- **Technical Notes**:
  - File: `billing.php`.
  - Database tables: `sales` (columns: `id`, `user_id`, `total`, `created_at`), `sale_items` (columns: `id`, `sale_id`, `product_id`, `quantity`, `price`).
  - Uses PHP to generate PDF (via a library like FPDF or DOMPDF, if integrated).
  - Updates product quantities in the `products` table after a sale.

**Image Insert**: Screenshot of Billing Page (Showing Bill Creation Form and Generated Bill)

### 6. Billing History
- **Purpose**: Provide a record of all past sales transactions.
- **Functionality**:
  - Displays a table of all sales (columns: sale ID, date, total amount, user).
  - Admins can click on a sale to view detailed information (products sold, quantities, total).
  - Supports pagination for large datasets (if implemented).
- **Benefits**:
  - Allows admins to track sales history for auditing or analysis.
  - Useful for handling returns or resolving customer disputes.
  - Provides a clear record of all transactions.
- **Technical Notes**:
  - File: `billing_history.php`.
  - Database tables: `sales`, `sale_items`.
  - Uses JOIN queries to fetch sale details with associated products.

**Image Insert**: Screenshot of Billing History Page (Showing Sales List and Sale Details)

### 7. Performance Analytics
- **Purpose**: Provide actionable insights into business performance through visual charts.
- **Functionality**:
  - **Monthly Sales Trend**: A line chart showing total sales for the last 6 months.
  - **Top Selling Products**: A bar chart displaying the top 5 products by quantity sold.
  - **Worker Performance**: A bar chart showing total sales generated by each worker (if workers are added).
  - **Sales by Day of Week**: A bar chart showing average sales for each day of the week (Monday to Sunday).
  - Charts are animated using Chart.js (fade-in effect, delayed animations for each element, hover effects for interactivity).
- **Benefits**:
  - Helps shop owners identify sales trends (e.g., increasing or decreasing sales).
  - Highlights top-performing products, allowing better stock planning.
  - Shows which days are busiest, helping with staffing decisions.
  - Animated charts make the data visually appealing and easy to understand.
- **Technical Notes**:
  - File: `performance.php`.
  - Uses Chart.js library (CDN: `https://cdn.jsdelivr.net/npm/chart.js`).
  - Data fetched using MySQL queries:
    - Monthly Sales: `SELECT SUM(total) FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?`.
    - Top Products: `SELECT p.name, SUM(si.quantity) FROM sale_items si JOIN products p ON si.product_id = p.id GROUP BY si.product_id ORDER BY SUM(si.quantity) DESC LIMIT 5`.
    - Worker Performance: `SELECT u.name, SUM(s.total) FROM sales s JOIN users u ON s.user_id = u.id GROUP BY s.user_id`.
    - Sales by Day: `SELECT AVG(total) FROM sales WHERE DAYNAME(created_at) = ?`.
  - Animations: Configured with `duration: 1500ms`, `easing: easeOutQuart`, and `delay` for each chart element.

**Image Insert**: Screenshot of Performance Analytics Page (Showing All Charts)

### 8. Backup System
- **Purpose**: Ensure data security by allowing admins to create and download backups of the database.
- **Functionality**:
  - A "Download Backup" button that generates a ZIP file of the entire database.
  - Backup includes all tables: `users`, `products`, `sales`, `sale_items`.
  - ZIP file is automatically downloaded to the admin’s device.
- **Benefits**:
  - Protects against data loss due to server crashes, accidental deletions, or other issues.
  - Allows admins to restore data if needed.
  - Monthly backups ensure data is always safe.
- **Technical Notes**:
  - File: `backup.php`.
  - Uses PHP’s `mysqldump` command to export the database (or a custom PHP script to export tables as SQL).
  - ZIP file generation using PHP’s `ZipArchive` class.

**Image Insert**: Screenshot of Backup Page (Showing Download Backup Button)

### 9. Messages
- **Purpose**: Facilitate communication between the admin and workers (though currently limited to admin-only usage).
- **Functionality**:
  - Admins can send messages to workers (if workers are added).
  - Workers can view messages and reply (feature not fully implemented in this version).
  - Displays a list of messages with sender, timestamp, and content.
- **Benefits**:
  - Improves coordination between the admin and workers.
  - Useful for sending important announcements or instructions.
- **Technical Notes**:
  - File: `messages.php`.
  - Database table: Not implemented (placeholder for future enhancement).
  - Currently uses a static mockup for demonstration purposes.

**Image Insert**: Screenshot of Messages Page (Showing Message List or Form)

---

## Technical Architecture

### Tech Stack
- **Frontend**:
  - **HTML**: For page structure.
  - **CSS**: Custom `style.css` for styling (responsive design with media queries).
  - **JavaScript**: For interactivity (e.g., Chart.js for performance charts).
  - **Libraries**:
    - Chart.js (for animated charts).
    - Boxicons (for icons in the sidebar and UI: `https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css`).
- **Backend**:
  - **PHP**: Version 7.4+ (handles server-side logic, database queries, session management).
  - **MySQL**: Database management system for storing all data.
- **Server**:
  - Local Development: XAMPP (Apache + MySQL).
  - Hosting: Hostinger Business Plan (75 MySQL connections, 300 databases, 100 domains, 200GB SSD storage).

### Database Structure
- **Database Name**: `shop_seva_db`.
- **Tables**:
  - **`users`**:
    - `id` (INT, Primary Key, Auto Increment): Unique identifier for users.
    - `name` (VARCHAR(255)): User’s name.
    - `email` (VARCHAR(255), Unique): User’s email for login.
    - `password` (VARCHAR(255)): Hashed password for security.
    - `role` (ENUM('Admin', 'Worker')): User role for access control.
  - **`products`**:
    - `id` (INT, Primary Key, Auto Increment): Unique identifier for products.
    - `name` (VARCHAR(255)): Product name.
    - `price` (DECIMAL(10,2)): Product price.
    - `quantity` (INT): Current stock level.
  - **`sales`**:
    - `id` (INT, Primary Key, Auto Increment): Unique identifier for sales.
    - `user_id` (INT, Foreign Key): ID of the user who created the sale.
    - `total` (DECIMAL(10,2)): Total amount of the sale.
    - `created_at` (DATETIME): Date and time of the sale.
  - **`sale_items`**:
    - `id` (INT, Primary Key, Auto Increment): Unique identifier for sale items.
    - `sale_id` (INT, Foreign Key): ID of the associated sale.
    - `product_id` (INT, Foreign Key): ID of the product sold.
    - `quantity` (INT): Quantity sold.
    - `price` (DECIMAL(10,2)): Price per unit at the time of sale.

**Image Insert**: Database Schema Diagram (ER Diagram Showing Tables and Relationships)

### Deployment Setup
- **Local Development**:
  - Path: `C:\xampp\htdocs\Shop-Seva-CRM`.
  - Server: XAMPP (Apache + MySQL).
  - URL: `http://localhost/Shop-Seva-CRM/index.php`.
- **Live Hosting**:
  - Hosting Provider: Hostinger Business Plan.
  - Resources: 75 MySQL max user connections, 300 databases, 100 domains, 200GB SSD storage.
  - Path: `/public_html/Shop-Seva-CRM` (or a subdomain like `crm.yourdomain.com`).
  - Capacity: Supports 12 CRM instances (1 admin + max 2 workers per instance, 6 MySQL connections per instance).
  - Cost per Instance (Hosting + Maintenance): ₹6,700/year (₹558/month).

**Image Insert**: Screenshot of Hostinger hPanel Showing Resource Usage

---

## Usage Instructions (Admin Perspective)

### 1. Login to the System
- **Step 1**: Open the URL in a browser (e.g., `http://yourdomain/Shop-Seva-CRM/index.php`).
- **Step 2**: Enter the admin’s email and password.
- **Step 3**: Click "Login" to access the dashboard.
- **Note**: Only users with the "Admin" role can access the dashboard. Incorrect credentials will show an error message.

**Image Insert**: Screenshot of Login Page

### 2. Navigate Features via the Sidebar
- **Admin Dashboard**: View a quick overview of daily sales, recent activities, and low stock alerts.
- **Staff Management**:
  - Click "Staff" in the sidebar.
  - Add a new worker (optional, up to 2 workers).
  - View, edit, or delete existing workers.
- **Product Management**:
  - Click "Product" in the sidebar.
  - Add a new product by filling in the name, price, and quantity.
  - View, edit, or delete existing products.
- **Inventory Management**:
  - Click "Inventory" in the sidebar.
  - Check stock levels and note any low stock alerts (highlighted in red).
- **Billing**:
  - Click "Billing" in the sidebar.
  - Select products, specify quantities, and generate a bill.
  - Download the bill as a PDF.
- **Billing History**:
  - Click "Billing History" in the sidebar.
  - View a list of past sales and click to see detailed information.
- **Performance Analytics**:
  - Click "Performance" in the sidebar.
  - Analyze charts (monthly sales, top products, worker performance, sales by day).
- **Backup**:
  - Click "Backup" in the sidebar.
  - Click "Download Backup" to download a ZIP file of the database.
- **Messages**:
  - Click "Messages" in the sidebar.
  - Send messages to workers (if implemented).
- **Logout**:
  - Click "Logout" in the sidebar to sign out securely.

### 3. Best Practices
- **Regular Backups**: Download a backup at least once a month to ensure data safety.
- **Secure Credentials**: Use a strong password for the admin account and avoid sharing it.
- **Analyze Performance**: Regularly check the Performance Analytics page to make informed business decisions (e.g., stock popular products, schedule staff for busy days).
- **Monitor Stock**: Act on low stock alerts to avoid stock-outs.

**Image Insert**: Screenshot of Sidebar Navigation

---

## Future Enhancements
To make Shop-Seva CRM even more powerful and appealing, the following enhancements can be considered:

1. **Multi-Language Support**:
   - Add support for Hindi and English UI to cater to a broader audience.
   - Use PHP’s internationalization libraries (e.g., `gettext`) for language switching.
2. **SMS Notifications**:
   - Integrate an SMS gateway (e.g., Twilio, MSG91) to send daily sales summaries or low stock alerts via SMS.
   - Example: "Your daily sales: ₹5,000. Top product: Rice (50 units sold)."
3. **Advanced Analytics**:
   - Add charts for customer purchase patterns, seasonal sales trends, or profit margins.
   - Example: Identify which products sell more during festivals.
4. **Payment Gateway Integration**:
   - Integrate Razorpay or Stripe to accept online payments directly through the CRM.
   - Useful for shops that offer home delivery or online orders.
5. **Mobile App**:
   - Develop a mobile app (using Flutter or React Native) for admins to manage the shop on the go.
   - Features like push notifications for low stock or daily sales reports.
6. **Role-Based Permissions**:
   - Enhance the staff management system with detailed permissions (e.g., allow workers to only create bills, not view analytics).
   - Improves security and access control.
7. **Customer Management**:
   - Add a feature to track customer details (e.g., name, phone number, purchase history).
   - Useful for loyalty programs or targeted promotions.

**Image Insert**: Mockup of Future Mobile App Interface

---

## Conclusion
Shop-Seva CRM is a robust, affordable, and user-friendly solution designed to meet the needs of small shop owners in India. With features like sales tracking, inventory management, performance analytics, and data backups, it empowers shop owners to manage their businesses efficiently and make data-driven decisions. The system’s lightweight architecture ensures it can run on minimal server resources, making it cost-effective for hosting multiple instances. Future enhancements like SMS notifications, multi-language support, and a mobile app can further increase its value and appeal.

We believe Shop-Seva CRM can significantly benefit small businesses by simplifying operations and providing actionable insights. For inquiries, partnerships, or demos, please contact us.

**Company Name**: [Your Company Name]  
**Developed By**: [Your Name]  
**Contact**: [Your Email/Phone Number]  
**Website**: [Your Company Website URL]  
**Date**: May 25, 2025

**Image Insert**: Company Logo or Team Photo

---