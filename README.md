# Transportation Management System

A comprehensive web-based system for managing transportation services including taxi and car rental routes.

## Features

- **Route Management**
  - Create, edit, and delete transportation routes
  - Support for both taxi and car rental services
  - Route validation and error handling

- **Booking System**
  - Book transportation services
  - Manage existing bookings
  - Automatic booking cleanup when routes are deleted

- **Database Integration**
  - Secure database operations
  - Transaction support for data consistency
  - Prepared statements for SQL injection prevention

## Technical Stack

- **Backend**: PHP
- **Database**: MySQL
- **Security**: Prepared statements, input validation
- **Error Handling**: Comprehensive error catching and reporting

## Installation

1. Clone the repository
2. Configure your database connection in `config/db.php`
3. Import the database schema
4. Set up your web server to point to the project directory

## Security Features

- Input validation for all user inputs
- SQL injection prevention using prepared statements
- Transaction support for data consistency
- Service type validation
- Error handling and logging

## API Endpoints

### Route Management
- `admin/delete-transportation-route.php`
  - Method: POST
  - Parameters:
    - `delete_route`: Boolean
    - `route_id`: Integer
    - `service_type`: String ('taxi' or 'rentacar')
  - Response: JSON with success status and affected bookings count

## Error Handling

The system implements comprehensive error handling:
- Input validation
- Database operation validation
- Transaction rollback on errors
- JSON response format for API endpoints

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team.






<!-- ========================================================= -->
    /* Custom colors from Tailwind config */
    --color-primary: oklch(60% 0.15 170); /* #047857 */
    --color-secondary: oklch(70% 0.14 160); /* #10B981 */
    --color-accent: oklch(80% 0.18 85); /* #F59E0B */
<!-- ========================================================= -->
/* These utility classes will let you use the custom colors */
.bg-primary {
  background-color: var(--color-primary);
}
.bg-secondary {
  background-color: var(--color-secondary);
}
.bg-accent {
  background-color: var(--color-accent);
}
.text-primary {
  color: var(--color-primary);
}
.text-secondary {
  color: var(--color-secondary);
}
.text-accent {
  color: var(--color-accent);
}
.border-primary {
  border-color: var(--color-primary);
}
.border-secondary {
  border-color: var(--color-secondary);
}
.border-accent {
  border-color: var(--color-accent);
}
.hover\:bg-primary:hover {
  background-color: var(--color-primary);
}
.hover\:bg-secondary:hover {
  background-color: var(--color-secondary);
}
.hover\:bg-accent:hover {
  background-color: var(--color-accent);
}
.hover\:text-primary:hover {
  color: var(--color-primary);
}
.hover\:text-secondary:hover {
  color: var(--color-secondary);
}
.hover\:text-accent:hover {
  color: var(--color-accent);
}
.focus\:border-primary:focus {
  border-color: var(--color-primary);
}
.focus\:border-secondary:focus {
  border-color: var(--color-secondary);
}
.focus\:border-accent:focus {
  border-color: var(--color-accent);
}
.focus\:ring-primary:focus {
  --tw-ring-color: var(--color-primary);
}
.focus\:ring-secondary:focus {
  --tw-ring-color: var(--color-secondary);
}
.focus\:ring-accent:focus {
  --tw-ring-color: var(--color-accent);
}
<!-- ========================================================= -->

<!-- ========================================================== -->


CREATE TABLE `umrah_packages` (
  `id` int(11) NOT NULL,
  `star_rating` enum('low_budget','3_star','4_star','5_star') NOT NULL,
  `title` varchar(35) NOT NULL,
  `description` text NOT NULL,
  `makkah_nights` int(11) NOT NULL CHECK (`makkah_nights` >= 0 AND `makkah_nights` <= 30),
  `madinah_nights` int(11) NOT NULL CHECK (`madinah_nights` >= 0 AND `madinah_nights` <= 30),
  `total_days` int(11) NOT NULL CHECK (`total_days` >= 1 AND `total_days` <= 60),
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`inclusions`)),
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0 AND `price` <= 500000),
  `package_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 


-- Alter package_bookings table to include fields from umrah_packages
ALTER TABLE package_bookings 
ADD COLUMN star_rating VARCHAR(20) AFTER booking_reference,
ADD COLUMN makkah_nights INT AFTER star_rating,
ADD COLUMN madinah_nights INT AFTER makkah_nights,
ADD COLUMN total_days INT AFTER madinah_nights;
<!-- ============================================================ -->
