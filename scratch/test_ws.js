const { spawn } = require('child_process');
const http = require('http');

async function main() {
    const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const chromeProc = spawn(chromePath, [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-sandbox',
        'http://localhost:8080/TPHL-ERP/scratch/rendered_sidebar.html'
    ]);

    await new Promise(r => setTimeout(r, 2000));

    // Get list of targets
    const targets = await new Promise((resolve, reject) => {
        http.get('http://127.0.0.1:9222/json', (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });

    const pageTarget = targets.find(t => t.type === 'page');
    console.log("Page target:", pageTarget ? pageTarget.url : 'None');

    if (!pageTarget) {
        chromeProc.kill();
        return;
    }

    const wsUrl = pageTarget.webSocketDebuggerUrl;
    const WebSocket = require('ws'); // wait, is ws installed? Let's check or use CDP
}
