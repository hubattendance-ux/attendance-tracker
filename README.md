# 📚 Attendance Tracker System

A simple and efficient web-based attendance management system built with PHP, MySQL, and vanilla JavaScript.

## ✨ Features

- 🔐 **User Authentication** - Secure signup and login with password encryption
- 👥 **Student Management** - Add, edit, and manage student lists
- ✅ **Attendance Marking** - Quick and easy attendance tracking with Present/Absent toggle
- 📊 **Attendance Reports** - Search and filter attendance records
- 🎓 **Student Portal** - Students can check their own attendance status
- 🔄 **Multi-Account Support** - Manage multiple subjects with one email
- 🔒 **Password Reset** - Secure token-based password recovery
- 🌓 **Dark Mode** - Toggle between light and dark themes
- 📱 **Responsive Design** - Works on desktop and mobile devices

## 🚀 Demo

[Live Demo](https://yourname.rf.gd) *(Update with your actual URL)*

## 📸 Screenshots

*(Add screenshots of your application here)*

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Styling:** Custom CSS (No frameworks!)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP (for local development)

## 🔧 Installation

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/attendance-tracker.git
   cd attendance-tracker
   ```

2. **Setup Database**
   - Start XAMPP/WAMP
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `attendance_tracker`
   - Import `database.sql` file (or run the SQL commands in `database-schema.sql`)

3. **Configure Database Connection**
   ```bash
   cp config.example.php config.php
   ```
   
   Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'attendance_tracker');
   ```

4. **Run the Application**
   - Copy files to `C:\xampp\htdocs\attendance\` (Windows)
   - Or `/var/www/html/attendance/` (Linux)
   - Open browser: `http://localhost/attendance/`

## 🗄️ Database Schema

The application uses 6 tables:

1. **accounts** - User accounts with encrypted passwords
2. **subjects** - Subject/course information per account
3. **students** - Student enrollment and names
4. **attendance** - Attendance records (date, status, student)
5. **reset_tokens** - Password reset tokens
6. **sessions** - User session management

See `database-schema.sql` for complete schema.

## 📖 Usage

### For Teachers/Admins:

1. **Sign Up**
   - Enter email, department, semester, subject
   - Create secure password

2. **Add Students**
   - Login and click "Edit Students"
   - Add enrollment numbers and names
   - Save the list

3. **Mark Attendance**
   - Select date (defaults to today)
   - Click "Start Marking"
   - Toggle Present/Absent for each student
   - Save attendance

4. **View Reports**
   - Search by enrollment number
   - Filter by status (Present/Absent/All)
   - View historical records

### For Students:

1. Click **"Student Status"** button
2. Enter Department, Semester, and Enrollment Number
3. View attendance percentage and statistics for all subjects

## 🌐 Deployment

### Deploy to InfinityFree (Free Hosting)

1. Sign up at [infinityfree.net](https://infinityfree.net)
2. Create hosting account
3. Create MySQL database
4. Upload files via FTP or File Manager
5. Import database schema
6. Update `config.php` with hosting credentials

### Deploy to Paid Hosting

Works with any PHP/MySQL hosting provider:
- Hostinger
- NameCheap
- DigitalOcean
- AWS/Google Cloud

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ CSRF protection via same-origin policy
- ✅ Secure token-based password reset
- ✅ Session management
- ✅ Input validation on client and server side

## 🐛 Known Issues

- Email sending for password reset requires SMTP configuration (currently shows token in alert for testing)
- No file upload for bulk student import (planned for future)

## 🗺️ Roadmap

- [ ] Email integration for password reset
- [ ] CSV import/export for students
- [ ] Attendance statistics dashboard
- [ ] QR code attendance marking
- [ ] SMS notifications
- [ ] Multi-language support
- [ ] PDF report generation

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature-name`
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Built with ❤️ for educational purposes
- Inspired by modern attendance systems
- Thanks to all contributors

## 📞 Support

If you have any questions or issues:
- Open an [Issue](https://github.com/yourusername/attendance-tracker/issues)
- Email: your.email@example.com

---

⭐ **Star this repo if you find it helpful!**
