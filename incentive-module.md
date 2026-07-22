Project Incentive Module Implementation Plan
This document details the architecture, database schema changes, workflow logic, UI components, and implementation strategy for the Incentive Module in the ERP.

📌 Executive Summary & Key Features
The Incentive Module enables automated calculation, claiming, approval, and disbursement of project sales incentives based on Project Category Incentive Percentages (project_categories.incentive_percentage).

Core Requirements Covered:
Category-Wise Incentive %:
Incentive is calculated from agreed_value * incentive_percentage / 100 based on the project's category.
Eligibility Condition:
A sale is eligible for incentive claiming when both Booking Money and Registration are completed and verified for a lead's booking (lead_bookings).
Sales Person View ("My Incentives"):
Sales Executives, Managers, and Sales Team Heads can view their project bookings, verify booking + registration status, see calculated incentive amounts, and click "Claim Incentive".
Accounts & Management View ("Incentive Management"):
Accounts and Management roles can view company-wide incentive metrics: Total Earned, Claimed, Approved, Paid/Withdrawn, and Remaining Balance per sales person.
Accounts can Approve/Reject claims and record Disbursements / Payouts (via Cash, Bank, Cheque, etc.).
External / Non-Sales Employee Incentive Assignment:
Management can manually assign a project sale incentive to non-sales employees (e.g. Engineers, HR, external staff) by selecting the Employee, Project/Booking, Sale Amount, and custom Incentive % / Amount.
Manually assigned incentives enter the Accounts disbursement queue alongside regular sales claims.
🗄️ Database Changes Required
We will introduce two new database tables:

1. project_incentive_claims
Stores all incentive claims (both auto-eligible sales claims and manually assigned external employee incentives).

sql

CREATE TABLE IF NOT EXISTS `project_incentive_claims` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT(10) UNSIGNED NOT NULL,
  `project_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL COMMENT 'Sales Person or Non-sales Employee receiving incentive',
  `is_external` TINYINT(1) DEFAULT 0 COMMENT '0 = Sales Person, 1 = Non-sales / External Employee manual assignment',
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'User ID of Management member who assigned external incentive',
  `sale_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `incentive_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `booking_completed` TINYINT(1) DEFAULT 0,
  `registration_completed` TINYINT(1) DEFAULT 0,
  `claim_status` ENUM('eligible', 'claimed', 'approved', 'rejected', 'partially_paid', 'paid') NOT NULL DEFAULT 'eligible',
  `claimed_at` DATETIME DEFAULT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`booking_id`),
  INDEX (`project_id`),
  INDEX (`claim_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2. project_incentive_payments
Tracks partial or full payout disbursements made by Accounts to employees.

sql

CREATE TABLE IF NOT EXISTS `project_incentive_payments` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `claim_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `payment_amount` DECIMAL(15,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Mobile Banking', 'Cheque') NOT NULL DEFAULT 'Bank Transfer',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATE NOT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `disbursed_by` INT(11) NOT NULL COMMENT 'Accounts User ID',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`claim_id`),
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 Technical Architecture & Proposed Changes
Yes
Click Claim
Select Non-Sales Employee & Project Sale
Review & Approve
Make Payout
Lead Booking Completed
Booking & Registration Paid?
System Flags Booking as Eligible for Incentive
Sales Person Portal: My Incentives
Claim Status -> Claimed
Management Manual Assignment
Claim Created: is_external = 1
Accounts & Management Portal: Incentive Management
Claim Status -> Approved
Record Incentive Payment
Update Claim Status: Paid / Partially Paid



. New Controller: app/controllers/IncentiveController.php
myIncentives(): Renders sales person view showing eligible sales, incentive amount, and claim buttons.
manageIncentives(): Renders Accounts & Management dashboard with summary statistics, claim review queue, payment modal, and external assignment form.
claimIncentive(): AJAX endpoint to handle sales person's claim request.
approveClaim() / rejectClaim(): AJAX endpoint for Accounts/Management to approve or reject a claim.
disbursePayment(): AJAX endpoint for Accounts to log a payment/disbursement.
assignExternalIncentive(): AJAX endpoint for Management to assign incentives to non-sales employees.
New Model: app/models/IncentiveModel.php

Encapsulates database queries for fetching eligible bookings, calculating incentive amounts based on project categories, managing claims status, and tracking payments.
Views:

app/views/incentives/my-incentives.php: Clean, modern UI for Sales Persons with stats cards (Total Incentive, Claimed, Approved, Paid, Available Balance) and interactive claims table.
app/views/incentives/manage-incentives.php: Premium Accounts/Management dashboard with metrics, filter tabs (Sales Claims, External Assignments, Payment History), Approve/Disburse modals, and Manual Assignment modal.
Routes (index.php):

/my-incentives -> IncentiveController@myIncentives
/incentive-management -> IncentiveController@manageIncentives
/incentives/claim -> IncentiveController@claimIncentive
/incentives/approve -> IncentiveController@approveClaim
/incentives/reject -> IncentiveController@rejectClaim
/incentives/disburse -> IncentiveController@disbursePayment
/incentives/assign-external -> IncentiveController@assignExternalIncentive
Sidebar Links (app/views/layouts/sidebar.php):

Under Sales Module: "My Incentives" for Sales Person / Executive / Manager roles.
Under Accounts / Management Module: "Incentive Management" for Accounts & Management roles.
🎨 UI / UX Design Highlights
Stat Cards:
💳 Total Eligible Incentive: Total amount earned across completed bookings.
⏳ Pending Claims: Total value waiting for Accounts approval.
✅ Approved & Disbursed: Total amount paid out.
💰 Available Balance: Remaining amount ready for withdrawal/claim.
Badges & Progress Indicators:
Booking Status Badge (Booking Verified)
Registration Status Badge (Registration Completed)
Claim Status Badges (Eligible [Blue], Claimed [Orange], Approved [Teal], Paid [Green], Rejected [Red])
Modals:
Claim Modal: Input optional notes/bank account preference.
Payment/Disbursement Modal: Input payout amount, payment method (Bank/Cash/Mobile Banking), transaction reference, and notes.
External Incentive Assignment Modal: Employee dropdown, Project/Booking selector, Sale Value, Incentive %, Calculated Amount, and Reason/Remarks.
🧪 Verification Plan
Automated / CLI Testing:
Create and execute a test script (scratch/test_incentives_flow.php) to test:
Creation of database tables.
Incentive calculation given a project category incentive % and booking agreement value.
Sales claim creation and status transition.
Manual external incentive assignment.
Disbursement recording and balance calculation.
Manual / Browser Verification:
Navigate to /my-incentives as a Sales Person and test claiming an eligible booking.
Navigate to /incentive-management as Accounts / Management and test approving a claim, disbursing payment, and assigning an external employee incentive.