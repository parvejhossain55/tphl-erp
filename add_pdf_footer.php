<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$searchPDF = "                });

                var form = document.createElement(\"form\");";

$replacePDF = "                });

                // Get footer data
                var footerData = [];
                \$('#workspaceTable tfoot tr:first td').each(function (index) {
                    if (index === 0) return; // Skip checkbox column align (Wait, tfoot doesn't have a checkbox column, it has Totals at index 0 which spans first 7 columns visually but NOT in DOM, we injected 7 empty TDs!)
                    
                    // The first 7 TDs are empty, the 8th is value.
                    // Wait! In header iteration:
                    // \$(dt.table().header()).find('th').each(function (index) { if (index > 0) ... });
                    // So we must skip index 0 here too!
                    if (index > 0) {
                        footerData.push(\$(this).text().trim());
                    }
                });

                var form = document.createElement(\"form\");
                var inputFooter = document.createElement(\"input\");
                inputFooter.type = \"hidden\";
                inputFooter.name = \"table_footer\";
                inputFooter.value = JSON.stringify(footerData);
                form.appendChild(inputFooter);";

$content = str_replace($searchPDF, $replacePDF, $content);
file_put_contents($file, $content);
echo "PDF footer payload added.";
