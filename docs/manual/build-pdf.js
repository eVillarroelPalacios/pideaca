// Arma docs/manual/manual.html desde los .md + capturas y lo imprime a PDF.
// Uso: node docs/manual/build-pdf.js [--pdf]
import { execFileSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function inline(s) {
  return esc(s)
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img alt="$1" src="$2">')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
}

function mdToHtml(md) {
  const lines = md.split('\n');
  let html = '';
  let i = 0;
  let inList = false;
  const closeList = () => { if (inList) { html += '</ul>'; inList = false; } };

  const isTableRow = (l) => /^\s*\|.*\|\s*$/.test(l);
  const isTableSep = (l) => /^\s*\|?[\s:|-]+\|?[\s:|-]*$/.test(l) && l.includes('-');

  while (i < lines.length) {
    const line = lines[i];
    if (isTableRow(line) && i + 1 < lines.length && isTableSep(lines[i + 1])) {
      const cells = (l) => l.trim().replace(/^\||\|$/g, '').split('|').map((c) => inline(c.trim()));
      closeList();
      html += '<table><thead><tr>' + cells(line).map((c) => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
      i += 2;
      while (i < lines.length && isTableRow(lines[i])) {
        html += '<tr>' + cells(lines[i]).map((c) => `<td>${c}</td>`).join('') + '</tr>';
        i++;
      }
      html += '</tbody></table>';
      continue;
    }
    const h = line.match(/^(#{1,3})\s+(.*)/);
    if (h) {
      closeList();
      const lvl = h[1].length;
      html += `<h${lvl}>${inline(h[2])}</h${lvl}>`;
    } else if (/^\s*>\s?/.test(line)) {
      closeList();
      html += `<blockquote>${inline(line.replace(/^\s*>\s?/, ''))}</blockquote>`;
    } else if (/^\s*[-*]\s+/.test(line)) {
      if (!inList) { html += '<ul>'; inList = true; }
      html += `<li>${inline(line.replace(/^\s*[-*]\s+/, ''))}</li>`;
    } else if (/^\s*$/.test(line)) {
      closeList();
    } else {
      closeList();
      html += `<p>${inline(line)}</p>`;
    }
    i++;
  }
  closeList();
  return html;
}

const chapters = [
  '01-mapa-general.md',
  'web/10-consultas-sugerencias.md',
  'web/11-usuarios.md',
  'web/12-pedidos.md',
  'web/13-mis-pedidos.md',
  'web/14-repartidor.md',
  'web/15-dinero.md',
  'web/16-comercios.md',
  'web/17-mi-catalogo.md',
  'web/18-inventario.md',
  'web/19-ordenes-servicio.md',
  'web/20-ordenes-gestion.md',
  'flujos/20-registro-activacion.md',
  'flujos/21-compra-mercado.md',
  'flujos/22-despacho-reparto.md',
  'movil/30-consultas.md',
];

const sections = chapters.map((f) => {
  const md = fs.readFileSync(path.join(__dirname, f), 'utf-8');
  return `<section class="chapter">${mdToHtml(md)}</section>`;
}).join('\n');

const doc = `<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Manual funcional de PideAca</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.5; color: #111; max-width: 720px; margin: 0 auto; padding: 24px; }
  .cover { text-align: center; padding: 120px 0 60px 0; page-break-after: always; }
  .cover h1 { font-size: 28pt; color: #0c2a4d; margin-bottom: 8px; }
  .cover p { color: #555; }
  .chapter { page-break-before: always; }
  h1 { font-size: 20pt; color: #0c2a4d; border-bottom: 3px solid #ea580c; padding-bottom: 6px; }
  h2 { font-size: 14pt; color: #0c2a4d; }
  h3 { font-size: 12pt; }
  table { border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 10pt; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
  th { background: #0c2a4d; color: #fff; }
  img { max-width: 100%; border: 1px solid #ddd; margin: 10px 0; }
  code { background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-size: 10pt; }
  blockquote { border-left: 4px solid #ea580c; margin: 10px 0; padding: 6px 12px; background: #fff7ed; }
</style></head><body>
<div class="cover"><h1>Manual funcional de PideAca</h1><p>Guía ilustrada del funcionamiento del sistema (web + móvil) — español</p></div>
${sections}
</body></html>`;

fs.writeFileSync(path.join(__dirname, 'manual.html'), doc);
console.log('manual.html generado');

if (process.argv.includes('--pdf')) {
  const out = path.join(__dirname, 'Manual-PideAca.pdf');
  execFileSync(
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    ['--headless', '--disable-gpu', '--no-sandbox', `--print-to-pdf=${out}`, '--no-pdf-header-footer', path.join(__dirname, 'manual.html')],
    { stdio: 'inherit' },
  );
  console.log('PDF generado:', out);
}
