# 🩸 LifeLine Connect

**Enterprise Dual-Database Blood Donation Management System**

![LifeLine Connect Banner](https://via.placeholder.com/1000x300.png?text=LifeLine+Connect+-+Donate+Blood,+Save+Lives) *(Note: Add a screenshot of your portal home here)*

## 📖 Overview
**LifeLine Connect** is a comprehensive, web-based platform engineered to bridge the critical gap between blood banks, hospitals, and voluntary donors. Developed as an academic project for the **National Institute of Business Management (NIBM)**, it addresses traditional blood inventory inefficiencies using a modern hybrid database architecture. 

By integrating strict relational data processing with flexible NoSQL high-velocity data handling, LifeLine Connect ensures rapid response to critical blood shortages and seamless digital transformation for healthcare workflows.

## ✨ Key Features
*   **Dual-Database Architecture:** Uses **Oracle 21c** (3NF) for strict transactional records (inventory, donor registry) and **MongoDB** for unstructured, high-velocity data (emergency broadcast appeals, camp reviews).
*   **Real-Time Geographic Mapping:** Integrates **Leaflet.js** to geographically plot upcoming blood donation drives on an interactive map.
*   **PL/SQL Analytical Engine:** Features autonomous database triggers to prevent negative inventory and PL/SQL packages for generating mission-critical business reports (e.g., Donor Eligibility, Critical Stock Alerts).
*   **Role-Based Access Control (RBAC):** Secure entry points for Donors, System Administrators, and Hospitals with cryptographic password hashing (bcrypt).
*   **Emergency Distribution Engine:** Real-time requisition processing and dynamic NoSQL alert rendering for urgent hospital needs.

## 🛠️ Technology Stack
**Frontend:**
*   HTML5, CSS3, JavaScript
*   Leaflet.js (Map Integration)
*   Bootstrap / Responsive UI Design

**Backend:**
*   PHP (PDO & Object-Oriented Programming)

**Databases:**
*   **Oracle Database 21c** (Primary Relational Database)
*   **MongoDB** (Secondary NoSQL Database for unstructured data)

## 🗄️ Database Schema & Logic
The system is built on a highly normalized **3NF Relational Schema** with 10 core Oracle tables (Donors, Camps, Blood_Stock, Hospitals, etc.). 
Advanced PL/SQL implementations include:
*   `trg_validate_stock_units`: Prevents negative blood stock anomalies.
*   `get_donor_eligibility_func`: Instantly verifies donor registration via NIC.
*   `check_critical_stock_proc`: Scans and logs critical blood group shortages.

## 🚀 Installation & Setup (Local Development)

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/your-username/lifeline-connect.git](https://github.com/your-username/lifeline-connect.git)
