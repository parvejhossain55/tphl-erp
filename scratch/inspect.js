const fs = require('fs');
const html = fs.readFileSync('scratch/rendered_sidebar.html', 'utf8');
const logoutIdx = html.indexOf('Log Out');
console.log("=== Around Log Out ===");
console.log(html.substring(logoutIdx - 100, logoutIdx + 400));
