# Solaris / Solaria 40

A financial trading pool application built with **Plain PHP 8** and **MySQL / MariaDB**.

Structured according to the **Solaria 40: 40-Day Accelerated Trading Pool & Daily Liquidity Schedule ($0.50 Investor Benefit)** specification.

## Core Parameters
- **Base Reference Capital:** $10.00 per unit
- **Daily Investor Benefit:** $0.50 per unit daily
- **Daily Referral Income:** $0.25 per unit daily
- **Cycle Duration:** 40 Days
- **Liquidity Status:** Withdrawable daily ($20.00 total yield per $10 unit = 200% ROI)

---

## Features
1. **Interactive Day-by-Day Progression Schedule (Days 1–40)**
   - Executive Summary with core parameters.
   - Filterable schedule table (Days 1–20, Days 21–40, Full 40 Days) with real-time liquidity status badges.
2. **User Dashboard & Trading Pool Subscription**
   - Instant registration with a $20 welcome testing balance.
   - Subscribe to custom quantities of Solaria 40 units ($10.00 base capital per unit).
   - Real-time cycle tracking: Day progress bar, cumulative yield, and projected returns.
3. **Daily Liquidity Withdrawal System**
   - Full daily liquidity access for all unlocked investor benefits and referral earnings.
   - Crypto address payouts (USDT TRC-20, ERC-20, USDC, BTC).
4. **Referral Income Network**
   - Automatic referral code generation and shareable invitation links.
   - Daily $0.25 sponsor rewards credited on every active referral contract day.
5. **Admin Pool Orchestrator**
   - Monitor total pool capital, yield disbursed, and contracts.
   - Execute daily cycle progressions (batch distribution to all active contracts).
   - Review, approve, or reject and refund pending daily withdrawal requests.
6. **Automated Cron / CLI Task**
   - CLI script `cron_daily.php` for server cron scheduling.

---

## Setup & Running Locally

### 1. Database Setup
```bash
# Start MySQL / MariaDB service
sudo service mariadb start

# Initialize database schema and demo accounts
php seed.php
```

### 2. Run Built-in PHP Web Server
```bash
php -S 0.0.0.0:8000
```
Open your browser to `http://localhost:8000`.

### 3. Demo Credentials
- **Admin:** `admin` / `Admin123!`
- **Investor:** `sarah_trader` / `User123!`
- **Affiliate:** `alex_investor` / `User123!`

### 4. Running Daily Pool Progression via CLI
```bash
php cron_daily.php
```
