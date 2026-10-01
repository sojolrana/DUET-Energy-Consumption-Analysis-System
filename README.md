# DUET Energy Consumption Analysis System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%7C%208.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A web-based Energy Management System (EMS) designed to monitor, analyze, and visualize electrical power consumption across university facilities. Developed with PHP, MySQL, and Bootstrap, this application models a modern campus utility platform featuring real-time data visualization, built-in load simulation, automated anomaly alerts, and printable audit reports.

---

## Key Features

- **Interactive Analytics Dashboard**: Live monitoring interface with Chart.js displaying real-time power draw (kW), aggregate consumption (kWh), power factor metrics, and facility loads.
- **Facility & Meter Management**: Full CRUD modules to manage campus infrastructure, map virtual or physical meters to specific buildings, and organize departmental hierarchies.
- **Built-in Telemetry Simulator**: Native load generator (`simulate.php`) to test dynamic daytime/nighttime consumption patterns and test application response under fluctuating loads.
- **REST Data Ingestion API**: Structured JSON endpoint (`/api/ingest.php`) capable of receiving automated or simulated meter readings programmatically.
- **Threshold Alerts & Anomaly Tracking**: Automatic rule-based detection for electrical spikes, abnormal off-peak loads, and over-consumption events.
- **Reporting & Print-Ready Audits**: Consumption breakdown filters by date and building, complete with a dedicated print layout (`print_report.php`) for administrative audits.
- **Internal Maintenance Messaging**: Operator hub to dispatch maintenance alerts, flag meter faults, and log operational notes.
- **Role-Based Access Control (RBAC)**: Secure multi-tier session authentication separating administrator privileges from viewing roles.

---

## Project Structure

```text
duet_ems/
├── api/
│   ├── ingest.php          # REST endpoint for JSON reading payloads
│   └── live_stats.php      # Polling endpoint for real-time dashboard updates
├── config/
│   └── db.php              # Database connection configuration (PDO)
├── includes/
│   ├── auth.php            # Session guards & access-level validation
│   ├── header.php          # Reusable navigation & global asset links
│   └── footer.php          # Global script bundles & document terminators
├── alerts.php              # Anomaly detection logs and resolution status
├── buildings.php           # Campus building & faculty management (CRUD)
├── dashboard.php           # Primary executive overview & dynamic chart widgets
├── index.php               # Gateway router and login redirector
├── login.php               # User authentication portal
├── logout.php              # Session termination handler
├── messages.php            # Internal campus engineer message center
├── meters.php              # Meter inventory, status, and building assignment
├── print_report.php        # Dedicated print-optimized audit report view
├── readings.php            # Time-series consumption logs & filtering
├── reports.php             # Consumption analytics & date-range aggregations
├── schema.sql              # Relational database schema & initial seed data
├── simulate.php            # Automated synthetic consumption generator
└── users.php               # Administrative user management & credentials
```

---

## Tech Stack

- **Backend**: PHP (8.0+)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, Modern CSS, Bootstrap, Chart.js, FontAwesome
- **Data Exchange**: JSON via HTTP POST/GET

---

## Getting Started

### Prerequisites

- PHP 8.0 or newer
- MySQL Server 5.7+ or MariaDB 10.4+
- Web server (Apache with `mod_rewrite` enabled, NGINX, or PHP Built-in Server)

### Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone [https://github.com/](https://github.com/)<your-username>/duet-energy-consumption-analysis-system.git
   cd duet-energy-consumption-analysis-system
   ```

2. **Import the Database**:
   Create a database and import `schema.sql`:
   ```bash
   mysql -u root -p -e "CREATE DATABASE duet_ems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p duet_ems < schema.sql
   ```

3. **Configure Database Connection**:
   Update `config/db.php` with your local database credentials:
   ```php
   $host = 'localhost';
   $db   = 'duet_ems';
   $user = 'your_database_user';
   $pass = 'your_database_password';
   $charset = 'utf8mb4';
   ```

4. **Serve the Application**:
   Using PHP's built-in development server:
   ```bash
   php -S localhost:8000
   ```
   Or move the project directory to your web root (`/var/www/html/` for Apache or `htdocs/` for XAMPP).

5. **Access the Application**:
   Navigate to `http://localhost:8000/login.php` in your web browser.

---

## IoT Telemetry Ingestion API

Smart meters transmit payload data to `/api/ingest.php` via standard HTTP POST requests.

### Endpoint
`POST /api/ingest.php`

### Payload Format (JSON)
```json
{
  "meter_id": "MTR-ACAD-01",
  "voltage": 229.4,
  "current": 14.8,
  "power_kw": 3.39,
  "energy_kwh": 1284.50,
  "power_factor": 0.98,
  "timestamp": "2026-10-01 12:00:00"
}
```

### Response
```json
{
  "status": "success",
  "message": "Telemetry reading ingested successfully",
  "reading_id": 49201
}
```

---

## Synthetic Telemetry Simulation

To evaluate system behavior without physical smart meters:

1. Open your browser and navigate to:
   ```text
   http://localhost:8000/simulate.php
   ```
2. Alternatively, trigger simulated feeds via CLI:
   ```bash
   php simulate.php
   ```
This generates standard cyclical daytime/nighttime power fluctuations across all registered campus facilities.

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).
