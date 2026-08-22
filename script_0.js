
    const base_url = "<?= $GLOBALS['base_url'] ?>";
    // Disable right-click context menu
    // document.addEventListener('contextmenu', function(e) {
    //     e.preventDefault();
    // });

    // // Disable Ctrl+C, Ctrl+X, Ctrl+V
    // document.addEventListener('keydown', function(e) {
    //     if ((e.ctrlKey && e.key === 'c') ||
    //         (e.ctrlKey && e.key === 'x') ||
    //         (e.ctrlKey && e.key === 'v')) {
    //         e.preventDefault();
    //     }
    // });

    // // Optional: Disable drag
    // document.addEventListener('dragstart', function(e) {
    //     e.preventDefault();
    // });


    const form = document.querySelector('form');
    const resetBtn = form ? form.querySelector('button[type="reset"]') : null;

    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault(); // avoid default reset
            // Clear all inputs
            form.querySelectorAll('input, select').forEach(function(el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0; // first option select
                else el.value = ''; // input clear
            });
        });
    }


    // campaign full show
