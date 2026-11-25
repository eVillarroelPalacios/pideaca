import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const LOGS = path.join(__dirname, 'logs');

function readJson(p, fallback) {
  try {
    return JSON.parse(fs.readFileSync(p, 'utf-8'));
  } catch {
    return fallback;
  }
}

// Backend: cuenta tests/fallas desde el junit + lista de fallas.
let beTotal = 0;
let beFailed = 0;
for (const jname of ['junit.xml', 'baseline-junit.xml']) {
  try {
    const junit = fs.readFileSync(path.join(LOGS, jname), 'utf-8');
    const m = junit.match(/<testsuite[^>]*tests="(\d+)"[^>]*errors="(\d+)"[^>]*failures="(\d+)"/)
      ?? junit.match(/tests="(\d+)"[\s\S]{0,200}?errors="(\d+)"[\s\S]{0,200}?failures="(\d+)"/);
    if (m) {
      beTotal = parseInt(m[1], 10);
      beFailed = parseInt(m[2], 10) + parseInt(m[3], 10);
      break;
    }
  } catch { /* prueba con el siguiente */ }
}

let beFailList = [];
try {
  beFailList = fs.readFileSync(path.join(LOGS, 'baseline-failures.txt'), 'utf-8')
    .split('\n').map((s) => s.trim()).filter(Boolean);
  if (beTotal === 0) beTotal = beFailList.length;
  if (beFailed === 0) beFailed = beFailList.length;
} catch { /* ok */ }

const smoke = readJson(path.join(LOGS, 'smoke-results.json'), null);
const smokeRows = smoke?.results ?? [];
const smokeOk = smokeRows.filter((r) => r.ok).length;
const smokeFail = smokeRows.filter((r) => !r.ok);

const dot = (ok) => ok
  ? '<span style="color:#16a34a">●</span>'
  : '<span style="color:#dc2626">●</span>';

const html = `<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Reporte QA PideAca</title>
<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:24px auto;padding:0 16px;font-size:14px}
h1{font-size:22px}h2{font-size:17px;border-bottom:2px solid #0c2a4d;padding-bottom:4px}
table{border-collapse:collapse;width:100%}td,th{border:1px solid #e5e7eb;padding:6px 10px;text-align:left;font-size:13px}
pre{background:#f8fafc;border:1px solid #e5e7eb;padding:8px;white-space:pre-wrap;font-size:12px}</style>
</head><body>
<h1>Reporte QA — PideAca (${new Date().toLocaleString('es-AR')})</h1>

<h2>Backend (PHPUnit): ${beTotal - beFailed}/${beTotal} OK</h2>
<table><tr><th>Test</th><th>Estado</th></tr>
${beFailList.map((t) => `<tr><td>${t}</td><td>ROJO</td></tr>`).join('') || '<tr><td colspan="2">Sin fallas registradas</td></tr>'}
</table>

<h2>Navegador (barrido humo): ${smokeOk}/${smokeRows.length} ventanas OK</h2>
<table><tr><th></th><th>Ventana</th><th>Detalle</th></tr>
${smokeRows.map((r) => `<tr><td>${dot(r.ok)}</td><td>dash-${r.section}</td><td>${[
  !r.visible ? 'NO VISIBLE' : '',
  ...r.consoleErrors.map((e) => 'JS: ' + e),
  ...r.pageErrors.map((e) => 'PAGE: ' + e),
  ...r.badRequests,
  ...(r.badBodies ?? []),
  ...((r.warnings ?? []).map((w) => '<span style="color:#b45309">AVISO: ' + w + '</span>')),
].join('<br>') || 'OK'}</td></tr>`).join('') || '<tr><td colspan="3">Barrido no ejecutado (faltan QA_EMAIL/QA_PASSWORD o Chromium)</td></tr>'}
</table>

<h2>Ventanas con falla (navegador)</h2>
<pre>${smokeFail.map((r) => `dash-${r.section}`).join('\n') || '(ninguna)'}</pre>
</body></html>`;

fs.writeFileSync(path.join(LOGS, 'report.html'), html);
console.log('report.html generado');
