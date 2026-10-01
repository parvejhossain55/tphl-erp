const { spawn } = require('child_process');
const http = require('http');

async function run() {
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9333',
        '--disable-gpu',
        '--no-sandbox',
        '--window-size=1280,800',
        'file:///c:/xampp/htdocs/TPHL-ERP/scratch/rendered_sidebar.html'
    ]);

    await new Promise(r => setTimeout(r, 2500));

    const targets = await new Promise((resolve, reject) => {
        http.get('http://127.0.0.1:9333/json', res => {
            let data = '';
            res.on('data', d => data += d);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });

    const page = targets.find(t => t.type === 'page');
    if (!page) {
        console.log("No page target found");
        chrome.kill();
        return;
    }

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
        ws.send(JSON.stringify({ id: 10, method: 'Console.enable' }));
        ws.send(JSON.stringify({ id: 11, method: 'Runtime.enable' }));
        // Wait 2 seconds for JS to run, then evaluate
        setTimeout(() => {
            const expr = `(() => {
                const sidebar = document.getElementById('sidebar');
                const scrollEl = document.getElementById('sidebar-scroll');
                const wrapper = document.querySelector('.simplebar-content-wrapper');
                const content = document.querySelector('.simplebar-content');
                const menu = document.querySelector('.main-menu');
                const logout = document.querySelector('a[href$="/logout"]') || document.querySelector('.sidebar__menu-label.ms-2')?.closest('li');
                const placeholder = document.querySelector('.simplebar-placeholder');
                const mask = document.querySelector('.simplebar-mask');
                
                // Scroll to bottom
                if (wrapper) {
                    wrapper.scrollTop = wrapper.scrollHeight;
                }
                
                return JSON.stringify({
                    windowHeight: window.innerHeight,
                    sidebarRect: sidebar ? sidebar.getBoundingClientRect() : null,
                    sidebarScrollRect: scrollEl ? scrollEl.getBoundingClientRect() : null,
                    wrapperScrollHeight: wrapper ? wrapper.scrollHeight : null,
                    wrapperClientHeight: wrapper ? wrapper.clientHeight : null,
                    wrapperScrollTop: wrapper ? wrapper.scrollTop : null,
                    contentRect: content ? content.getBoundingClientRect() : null,
                    menuRect: menu ? menu.getBoundingClientRect() : null,
                    logoutRect: logout ? logout.getBoundingClientRect() : null,
                    placeholderStyle: placeholder ? placeholder.getAttribute('style') : null,
                    placeholderRect: placeholder ? placeholder.getBoundingClientRect() : null,
                    maskRect: mask ? mask.getBoundingClientRect() : null,
                    maskComputedTop: mask ? window.getComputedStyle(mask).top : null,
                    maskComputedBottom: mask ? window.getComputedStyle(mask).bottom : null,
                    emptySpaceBelowLogout: logout ? (window.innerHeight - logout.getBoundingClientRect().bottom) : null,
                    sidebarOverflow: sidebar ? window.getComputedStyle(sidebar).overflowY : null,
                    scrollElOverflow: scrollEl ? window.getComputedStyle(scrollEl).overflowY : null,
                    lastMenuItems: Array.from(menu ? menu.children : []).slice(-5).map(li => ({
                        text: li.innerText.trim().replace(/\\s+/g, ' '),
                        top: li.getBoundingClientRect().top,
                        bottom: li.getBoundingClientRect().bottom,
                        height: li.getBoundingClientRect().height
                    }))
                }, null, 2);
            })()`;

            ws.send(JSON.stringify({
                id: 1,
                method: 'Runtime.evaluate',
                params: { expression: expr }
            }));
        }, 1500);
    };

    ws.onmessage = (event) => {
        const res = JSON.parse(event.data);
        if (res.method === 'Runtime.consoleAPICalled') {
            console.log("[CONSOLE]", res.params.type, res.params.args.map(a => a.value || a.description).join(' '));
        }
        if (res.method === 'Runtime.exceptionThrown') {
            console.log("[EXCEPTION]", res.params.exceptionDetails.text, res.params.exceptionDetails.exception?.description);
        }
        if (res.id === 1) {
            console.log("EVAL RESULT:");
            console.log(res.result.result.value);
            ws.close();
            chrome.kill();
            process.exit(0);
        }
    };
}

run().catch(err => {
    console.error(err);
    process.exit(1);
});
