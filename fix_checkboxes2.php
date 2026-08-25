<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// 1. Add TH checkbox
$content = str_replace(
    '<th>SL</th>',
    '<th class="text-center" style="width: 40px;"><input type="checkbox" id="selectAllEmployees" class="form-check-input" checked style="cursor:pointer;" title="Select All"></th>' . "\r\n                            " . '<th>SL</th>',
    $content
);

// 2. Add TD checkbox
$content = str_replace(
    '<td><?= $sl++ ?></td>',
    '<td class="text-center"><input type="checkbox" class="form-check-input emp-checkbox" checked style="cursor:pointer;"></td>' . "\r\n                                            " . '<td><?= $sl++ ?></td>',
    $content
);

// 3. Edit exportOptions for CSV
$exportSearch = "exportOptions: {
                            format: {
                                body: function (inner, rowidx, colidx, node) {";

$exportReplace = "exportOptions: {
                            rows: function ( idx, data, node ) {
                                return $(node).find('.emp-checkbox').is(':checked');
                            },
                            columns: ':not(:first-child)', // skip checkbox column
                            format: {
                                body: function (inner, rowidx, colidx, node) {";
$content = str_replace($exportSearch, $exportReplace, $content);

// 4. Edit exportDynamicPDF - Header
$pdfHdrSearch = "$(dt.table().header()).find('th').each(function () {
                    headerData.push($(this).text().trim());
                });";
$pdfHdrReplace = "$(dt.table().header()).find('th').each(function (index) {
                    if (index > 0) { // Skip checkbox
                        headerData.push($(this).text().trim());
                    }
                });";
$content = str_replace($pdfHdrSearch, $pdfHdrReplace, $content);

// 5. Edit exportDynamicPDF - Body
$pdfBdySearch = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    var rowArr = [];
                    tr.find('td').each(function () {
                        var cell = $(this);";
$pdfBdyReplace = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    if (!tr.find('.emp-checkbox').is(':checked')) return; // Skip unchecked
                    var rowArr = [];
                    tr.find('td').each(function (index) {
                        if (index === 0) return; // Skip checkbox cell
                        var cell = $(this);";
$content = str_replace($pdfBdySearch, $pdfBdyReplace, $content);

// 6. Edit Save function
$saveSearch = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    
                    var payslipIdStr = tr.attr('data-payslip-id');";
$saveReplace = "                $(dtNodes).each(function () {
                    var tr = $(this);
                    if (!tr.find('.emp-checkbox').is(':checked')) return; // Skip unchecked
                    
                    var payslipIdStr = tr.attr('data-payslip-id');";
$content = str_replace($saveSearch, $saveReplace, $content);

// 7. Add Checkbox JS Toggle Logic at the end of the script tag
$jsInsert = "
    $(document).on('change', '#selectAllEmployees', function() {
        var isChecked = $(this).is(':checked');
        $('.emp-checkbox').prop('checked', isChecked);
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#selectAllEmployees').prop('checked', false);
        } else {
            if ($('.emp-checkbox:checked').length === $('.emp-checkbox').length) {
                $('#selectAllEmployees').prop('checked', true);
            }
        }
    });

</script>";
$content = str_replace("</script>", $jsInsert, $content);

file_put_contents($file, $content);
echo "Checkboxes applied successfully.";
