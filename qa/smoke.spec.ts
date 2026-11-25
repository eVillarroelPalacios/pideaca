import { test, type Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SECTIONS: string[] = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'sections.json'), 'utf-8'),
).sections as string[];

const EMAIL = process.env.QA_EMAIL ?? '';
const PASSWORD = process.env.QA_PASSWORD ?? '';

type SectionResult = {
  section: string;
  ok: boolean;
  visible: boolean;
  consoleErrors: string[];
  pageErrors: string[];
  badRequests: string[];
  ms: number;
};

async function login(page: Page) {
  // Login por API con la misma sesión del navegador (robusto, sin overlay).
  await page.goto('');
  const csrf = await page
    .locator('meta[name="csrf-token"]')
    .getAttribute('content');
  const res = await page.request.post('login', {
    headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
    data: { email: EMAIL, password: PASSWORD },
  });
  if (!res.ok()) {
    throw new Error(`Login API falló: ${res.status()} ${(await res.text()).slice(0, 200)}`);
  }
  await page.goto('');
  await page.waitForFunction(() => !!document.querySelector('nav'), null, {
    timeout: 20000,
  });
}

test('barrido humo: login + todas las ventanas', async ({ page }) => {
  if (!EMAIL || !PASSWORD) test.skip(true, 'Faltan QA_EMAIL/QA_PASSWORD');

  const consoleErrors: string[] = [];
  const pageErrors: string[] = [];
  const badRequests: string[] = [];
  const results: SectionResult[] = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 300));
  });
  page.on('pageerror', (err) => pageErrors.push(String(err).slice(0, 300)));
  page.on('response', (res) => {
    if (res.status() >= 400 && !res.url().includes('favicon.ico')) {
      badRequests.push(`${res.status()} ${res.url().slice(0, 160)}`);
    }
  });

  await login(page);

  for (const key of SECTIONS) {
    const started = Date.now();
    const from = { c: consoleErrors.length, p: pageErrors.length, b: badRequests.length };

    const shown = await page.evaluate((k) => {
      const fn = (window as unknown as { showDashSection?: (x: string) => void })
        .showDashSection;
      if (typeof fn !== 'function') return 'no-fn';
      fn(k);
      const el = document.getElementById(`dash-${k}`);
      if (!el) return 'no-el';
      return el.style.display !== 'none' ? 'visible' : 'hidden';
    }, key);

    await page.waitForTimeout(2500);

    const fresh = {
      c: consoleErrors.slice(from.c),
      p: pageErrors.slice(from.p),
      b: badRequests.slice(from.b),
    };
    const ok = shown === 'visible' && fresh.c.length === 0
      && fresh.p.length === 0 && fresh.b.length === 0;

    if (!ok) {
      await page.screenshot({
        path: path.join(__dirname, 'logs', 'shots', `${key}.png`),
      });
    }

    results.push({
      section: key,
      ok,
      visible: shown === 'visible',
      consoleErrors: [...new Set(fresh.c)].slice(0, 10),
      pageErrors: [...new Set(fresh.p)].slice(0, 10),
      badRequests: [...new Set(fresh.b)].slice(0, 20),
      ms: Date.now() - started,
    });
  }

  fs.mkdirSync(path.join(__dirname, 'logs', 'shots'), { recursive: true });
  fs.writeFileSync(
    path.join(__dirname, 'logs', 'smoke-results.json'),
    JSON.stringify({ at: new Date().toISOString(), results }, null, 2),
  );

  const failed = results.filter((r) => !r.ok).map((r) => r.section);
  if (failed.length > 0) {
    throw new Error(`Ventanas con fallas: ${failed.join(', ')}`);
  }
});
