 = "mysql -u root -e "USE tphl_erp; ALTER TABLE payslips ADD COLUMN is_excluded TINYINT(1) DEFAULT 0;""
Invoke-Expression 
