<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$searchCSV = "title: 'Payroll_Workplace_Export',
                          exportOptions: {";
$replaceCSV = "title: 'Payroll_Workplace_Export',
                          footer: true,
                          exportOptions: {";
                          
$content = str_replace($searchCSV, $replaceCSV, $content);

$searchFormat = "                                      }
                                  }
                              }
                          }";
$replaceFormat = "                                      }
                                  },
                                  footer: function (inner, colidx, node) {
                                      // Strip HTML just in case, but keep commas
                                      return inner ? inner.toString().replace(/(<([^>]+)>)/ig, \"\").trim() : \"\";
                                  }
                              }
                          }";

// Safer regex replacement for the format block
$content = preg_replace(
    "/format\s*:\s*\{\s*body\s*:\s*function\s*\([^\)]*\)\s*\{(?:[^{}]*|\{[^{}]*\})*\}\s*\}/",
    "format: {
                                  body: function (inner, rowidx, colidx, node) {
                                      if (\$(node).find('input').length > 0) {
                                          return \$(node).find('input').val();
                                      } else {
                                          return inner.replace(/(<([^>]+)>)/ig, \"\").trim();
                                      }
                                  },
                                  footer: function (inner, colidx, node) {
                                      return inner ? inner.toString().replace(/(<([^>]+)>)/ig, \"\").trim() : \"\";
                                  }
                              }",
    $content
);

file_put_contents($file, $content);
echo "CSV export updated.";
