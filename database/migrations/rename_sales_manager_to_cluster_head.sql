-- Rename the "Sales Manager" role (excluding legacy Role ID 5 "Sales Team Leader") to "Cluster Head"
UPDATE roles
SET name = 'Cluster Head'
WHERE LOWER(TRIM(name)) = 'sales manager'
  AND id != 5;
