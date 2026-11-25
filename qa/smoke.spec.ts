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

type BadResponse = { status: number; url: string; expected: boolean };

type SectionResult = {
  section: string;
  ok: boolean;
  visible: boolean;
  consoleErrors: string[];
  pageErrors: string[];
  badRequests: string[];
  badBodies: string[];
  warnings: string[];
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
  const badBodies: string[] = [];
  const badList: BadResponse[] = [];
  const pendingBodies: Promise<void>[] = [];
  const results: SectionResult[] = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 300));
  });
  page.on('pageerror', (err) => pageErrors.push(String(err).slice(0, 300)));
  page.on('response', (res) => {
    if (res.status() >= 400 && !res.url().includes('favicon.ico')) {
      const short = `${res.status()} ${res.url().slice(0, 160)}`;
      badRequests.push(short);
      // Resuelve el cuerpo para clasificar: 403 con mensaje JSON es
      // denegación esperada (aviso), no falla.
      pendingBodies.push(
        res.text()
          .then((t) => {
            const entry: BadResponse = {
              status: res.status(),
              url: res.url().slice(0, 160),
              expected: false,
            };
            if (res.status() === 403 && t && !t.includes('<html')) {
              try {
                const j = JSON.parse(t);
                if (j && j.success === false && typeof j.message === 'string') {
                  entry.expected = true;
                }
              } catch { /* no JSON */ }
            } else if (t && !t.includes('<html')) {
              badBodies.push(`${res.status()} ${res.url().slice(0, 120)} :: ${t.slice(0, 300)}`);
            }
            badList.push(entry);
          })
          .catch(() => {}),
      );
    }
  });

  await login(page);

  async function checkSection(key: string) {
    const started = Date.now();
    const from = { c: consoleErrors.length, p: pageErrors.length, b: badRequests.length, t: badBodies.length, r: badList.length };

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
    // Espera a que se resuelvan los cuerpos de respuestas fallidas.
    await Promise.allSettled(pendingBodies.splice(0));

    const fresh = {
      c: consoleErrors.slice(from.c),
      p: pageErrors.slice(from.p),
      b: badRequests.slice(from.b),
      t: badBodies.slice(from.t),
      r: badList.slice(from.r),
    };
    const unexpected = fresh.r.filter((e) => !e.expected);
    const warnings = fresh.r
      .filter((e) => e.expected)
      .map((e) => `403 esperado: ${e.url}`);
    // Los "Failed to load resource ... status of 403" de consola que
    // corresponden a denegaciones esperadas no cuentan como falla.
    const expectedStatuses = new Set(
      fresh.r.filter((e) => e.expected).map((e) => String(e.status)),
    );
    const consoleUnexpected = fresh.c.filter((msg) => {
      const m = msg.match(/status of (\d+)/);
      return !(m && expectedStatuses.has(m[1]));
    });
    const ok = shown === 'visible' && consoleUnexpected.length === 0
      && fresh.p.length === 0 && unexpected.length === 0;

    return {
      section: key,
      ok,
      visible: shown === 'visible',
      consoleErrors: [...new Set(consoleUnexpected)].slice(0, 10),
      pageErrors: [...new Set(fresh.p)].slice(0, 10),
      badRequests: [...new Set(unexpected.map((e) => `${e.status} ${e.url}`))].slice(0, 20),
      badBodies: [...new Set(fresh.t)].slice(0, 5),
      warnings: [...new Set(warnings)].slice(0, 10),
      ms: Date.now() - started,
    };
  }

  for (const key of SECTIONS) {
    let result = await checkSection(key);
    // Reintento ante transitorios del entorno local (ej. 500 esporádicos):
    // si pasa al segundo intento queda OK con aviso, sin ocultar evidencia.
    if (!result.ok) {
      const first = result.badRequests.join(' | ');
      result = await checkSection(key);
      if (result.ok) {
        result.warnings.push(`transitorio: falló 1er intento (${first})`);
      }
    }

    if (!result.ok) {
      await page.screenshot({
        path: path.join(__dirname, 'logs', 'shots', `${key}.png`),
      });
    }

    results.push(result);
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
