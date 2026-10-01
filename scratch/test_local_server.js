const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const rootDir = 'c:/xampp/htdocs/TPHL-ERP';

const server = http.createServer((req, res) => {
    let reqPath = req.url.split('?')[0];
    if (reqPath.startsWith('/TPHL-ERP/assets/')) {
        reqPath = reqPath.replace('/TPHL-ERP/assets/', '/public/assets/');
    } else if (reqPath.startsWith('/assets/')) {
        reqPath = reqPath.replace('/assets/', '/public/assets/');
    } else if (reqPath.startsWith('/TPHL-ERP/public/')) {
        reqPath = reqPath.replace('/TPHL-ERP/public/', '/public/');
    } else if (reqPath === '/' || reqPath === '/test') {
        reqPath = '/scratch/rendered_sidebar.html';
    }

    const filePath = path.join(rootDir, reqPath);
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
        const ext = path.extname(filePath);
        let contentType = 'text/html';
        if (ext === '.css') contentType = 'text/css';
        if (ext === '.js') contentType = 'application/javascript';
        if (ext === '.png') contentType = 'image/png';
        if (ext === '.jpg') contentType = 'image/jpeg';
        if (ext === '.svg') contentType = 'image/svg+xml';
        res.writeHead(200, { 'Content-Type': contentType });
        fs.createReadStream(filePath).pipe(res);
    } else {
        console.log("404:", req.url, "->", filePath);
        res.writeHead(404);
        res.end('Not found: ' + reqPath);
    }
});

server.listen(3333, async () => {
    console.log("Local test server listening on 3333");
    
    // Replace base_url in rendered_sidebar.html to http://localhost:3333
    let html = fs.readFileSync(path.join(rootDir, 'scratch/rendered_sidebar.html'), 'utf8');
    html = html.replaceAll('http://localhost:8080/TPHL-ERP', 'http://localhost:3333');
    html = html.replaceAll('/TPHL-ERP/assets/', 'http://localhost:3333/assets/');
    fs.writeFileSync(path.join(rootDir, 'scratch/rendered_sidebar.html'), html);

    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9444',
        '--disable-gpu',
        '--no-sandbox',
        '--window-size=1280,800',
        'http://localhost:3333/test'
    ]);

    await new Promise(r => setTimeout(r, 2500));

    const targets = await new Promise((resolve, reject) => {
        http.get('http://127.0.0.1:9444/json', res => {
            let data = '';
            res.on('data', d => data += d);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });

    const page = targets.find(t => t.type === 'page');
    if (!page) {
        console.log("No page target found");
        chrome.kill();
        server.close();
        return;
    }

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
        ws.send(JSON.stringify({ id: 10, method: 'Console.enable' }));
        ws.send(JSON.stringify({ id: 11, method: 'Runtime.enable' }));

        setTimeout(() => {


            const expr = `(() => {
                try {
                    // Apply CSS Fix
                    const style = document.createElement('style');
                    style.innerHTML = \`
                        .app-sidebar {
                            overflow: hidden !important;
                            height: 100vh !important;
                        }
                        .main-sidebar, #sidebar-scroll {
                            height: calc(100vh - 70px) !important;
                            margin-top: 70px !important;
                            position: relative !important;
                            overflow-x: hidden !important;
                            max-width: 100%;
                        }
                        #sidebar-scroll:not([data-simplebar="init"]) {
                            overflow-y: auto !important;
                        }
                        #sidebar-scroll .simplebar-mask {
                            top: 0 !important;
                        }
                        #sidebar-scroll .simplebar-track.simplebar-vertical {
                            top: 0 !important;
                            height: 100% !important;
                        }
                        .main-menu-container, .main-menu {
                            overflow-x: hidden !important;
                            max-width: 100%;
                            margin-bottom: 0 !important;
                            padding-bottom: 0 !important;
                        }
                    \`;
                    document.head.appendChild(style);

                    const scrollEl = document.getElementById('sidebar-scroll');
                    // Do NOT initialize SimpleBar to test fallback
                    const sidebar = document.getElementById('sidebar');
                    const logout = document.querySelector('a[href*="logout"]')?.closest('li') || document.querySelector('.sidebar__menu-label.ms-2')?.closest('li');

                    // Scroll scrollEl to bottom
                    if (scrollEl) scrollEl.scrollTop = scrollEl.scrollHeight;

                    return JSON.stringify({
                        scrollElScrollTop: scrollEl ? scrollEl.scrollTop : null,
                        logoutRect: logout ? logout.getBoundingClientRect() : null,
                        emptySpaceBelowLogout: logout ? (window.innerHeight - logout.getBoundingClientRect().bottom) : null
                    }, null, 2);

                    const initial = {
                        sidebarScrollHeight: sidebar ? sidebar.scrollHeight : null,
                        sidebarClientHeight: sidebar ? sidebar.clientHeight : null,
                        scrollElScrollHeight: scrollEl ? scrollEl.scrollHeight : null,
                        scrollElClientHeight: scrollEl ? scrollEl.clientHeight : null,
                        wrapperScrollHeight: wrapper ? wrapper.scrollHeight : null,
                        wrapperClientHeight: wrapper ? wrapper.clientHeight : null,
                        logoutRectBefore: logout ? logout.getBoundingClientRect() : null
                    };

                    // Try scrolling wrapper to bottom
                    if (wrapper) wrapper.scrollTop = wrapper.scrollHeight;
                    const afterWrapperScroll = {
                        wrapperScrollTop: wrapper ? wrapper.scrollTop : null,
                        logoutRect: logout ? logout.getBoundingClientRect() : null,
                        emptySpaceBelowLogout: logout ? (window.innerHeight - logout.getBoundingClientRect().bottom) : null
                    };

                    // Reset wrapper and try scrolling sidebar to bottom
                    if (wrapper) wrapper.scrollTop = 0;
                    if (sidebar) sidebar.scrollTop = sidebar.scrollHeight;
                    const afterSidebarScroll = {
                        sidebarScrollTop: sidebar ? sidebar.scrollTop : null,
                        logoutRect: logout ? logout.getBoundingClientRect() : null,
                        emptySpaceBelowLogout: logout ? (window.innerHeight - logout.getBoundingClientRect().bottom) : null
                    };

                    // Test opening/closing submenus
                    const firstSub = document.querySelector('.slide.has-sub > a');
                    if (firstSub) {
                        firstSub.click();
                    }
                    if (wrapper) wrapper.scrollTop = wrapper.scrollHeight;
                    const afterSubmenuToggle = {
                        wrapperScrollTop: wrapper ? wrapper.scrollTop : null,
                        logoutRect: logout ? logout.getBoundingClientRect() : null,
                        emptySpaceBelowLogout: logout ? (window.innerHeight - logout.getBoundingClientRect().bottom) : null
                    };

                    return JSON.stringify({
                        initial,
                        afterWrapperScroll,
                        afterSubmenuToggle
                    }, null, 2);
                } catch(e) {
                    return JSON.stringify({ error: e.message, stack: e.stack });
                }
            })()`;

            ws.send(JSON.stringify({
                id: 1,
                method: 'Runtime.evaluate',
                params: { expression: expr }
            }));
        }, 2000);
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
            server.close();
            process.exit(0);
        }
    };
});
