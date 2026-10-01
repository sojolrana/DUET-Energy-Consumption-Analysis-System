# DUET Energy Consumption Analysis System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%7C%208.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

An IoT-driven, web-based Energy Management System (EMS) designed for university campuses and multi-building facilities. The system continuously ingests meter telemetry, detects consumption anomalies, visualizes campus load in real time, and produces comprehensive energy audit reports.

---

## Key Features

- **Live Campus Telemetry Dashboard**: Visualizes real-time power draw (kW), aggregate energy consumption (kWh), power factor, and operational alerts across faculties and halls.
- **IoT Data Ingestion Engine**: Lightweight RESTful ingest endpoint (`/api/ingest.php`) designed for edge microcontrollers (ESP32, Raspberry Pi, Arduino) and digital smart meters.
- **Hardware & Facility Hierarchy**: Complete administrative CRUD interfaces for campus infrastructure, mapping physical meters to specific buildings and departments.
- **Anomaly Detection & Threshold Alerts**: Automated alert generation for over-current, voltage sags/swells, and abnormal off-peak energy usage.
- **Auditing & Reporting Engine**: Dynamic consumption comparisons with printable, executive-ready energy audit reports (`print_report.php`).
- **Telemetry Simulation Suite**: Built-in testbed (`simulate.php`) to generate realistic campus energy load cycles without physical hardware attached.
- **Role-Based Access Control (RBAC)**: Secure multi-tier authentication restricting administration, meter configuration, and audit access.

---

## Project Structure

```text
duet_ems/
├── api/
│   ├── ingest.php          # REST endpoint for incoming meter telemetry
│   └── live_stats.php      # Polling endpoint for real-time dashboard visualizers
├── config/
│   └── db.php              # Database credentials & PDO connection setup
├── includes/
│   ├── auth.php            # Session guards & access-level validation
│   ├── header.php          # Reusable navigation & global asset links
│   └── footer.php          # Global script bundles & document terminators
├── alerts.php              # Real-time anomaly logs and resolution tracking
├── buildings.php           # Campus building & faculty management
├── dashboard.php           # Primary executive overview & analytics widgets
├── index.php               # Gateway & route redirector
├── login.php               # User authentication portal
├── logout.php              # Session invalidation & termination
├── messages.php            # Internal campus engineer communication hub
├── meters.php              # Smart meter inventory & assignment
├── print_report.php        # Print-optimized audit report generator
├── readings.php            # Granular time-series telemetry table
├── reports.php             # Consumption analytics & date-filtered reports
├── schema.sql              # Relational schema definition & default seed data
├── simulate.php            # Synthetic telemetry generator for testing
└── users.php               # System user management & credential controls
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