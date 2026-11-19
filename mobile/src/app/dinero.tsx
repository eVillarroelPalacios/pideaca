import { Redirect } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  DineroCharge,
  DineroDriverWithdrawal,
  DineroFeeRule,
  DineroInvoice,
  DineroLedgerEntry,
  DineroPlan,
  DineroSettlement,
} from '@/types/api';

/**
 * Backoffice Dinero (Fases 1-3, solo Admin). Espeja la ventana web "Dinero":
 * mismas 8 pestañas, mismos campos, mismas validaciones del servidor y
 * mismos mensajes. Sin base de datos local: todo pasa por
 * /api/mobile/v1/admin/billing, que reutiliza el AdminBillingController.
 */
type DineroTab =
  | 'comisiones'
  | 'matriz'
  | 'liquidaciones'
  | 'cobros'
  | 'efectivo'
  | 'conciliacion'
  | 'planes'
  | 'facturas'
  | 'retiros';

const TABS: Array<{ key: DineroTab; label: string }> = [
  { key: 'comisiones', label: 'Comisiones' },
  { key: 'matriz', label: 'Matriz PideAca' },
  { key: 'liquidaciones', label: 'Liquidaciones' },
  { key: 'cobros', label: 'Cobros' },
  { key: 'efectivo', label: 'Efectivo' },
  { key: 'conciliacion', label: 'Conciliación' },
  { key: 'planes', label: 'Planes Hogar' },
  { key: 'facturas', label: 'Facturas' },
  { key: 'retiros', label: 'Retiros' },
];

const MODULE_OPTIONS: ComboOption<string>[] = [
  { value: 'mercado', label: 'Mercado (Módulo A)' },
  { value: 'hogar', label: 'Hogar (Módulo B)' },
];

const METHOD_OPTIONS: ComboOption<string>[] = [
  { value: 'transferencia', label: 'Transferencia' },
  { value: 'efectivo', label: 'Efectivo' },
];

const ORDER_TYPE_OPTIONS: ComboOption<string>[] = [
  { value: 'order', label: 'Pedido (order)' },
  { value: 'service_order', label: 'Servicio (service_order)' },
];

const LEDGER_TYPE_OPTIONS: ComboOption<string>[] = [
  { value: '', label: 'Todos' },
  { value: 'fee', label: 'Comisión' },
  { value: 'wallet_debit', label: 'Débito efectivo' },
  { value: 'payout', label: 'Pago' },
  { value: 'collection', label: 'Cobro deuda' },
  { value: 'subscription', label: 'Suscripción' },
];

const INVOICE_STATUS_OPTIONS: ComboOption<string>[] = [
  { value: '', label: 'Todas' },
  { value: 'pending', label: 'Pendiente' },
  { value: 'overdue', label: 'Vencida' },
  { value: 'paid', label: 'Pagada' },
];

const YES_NO_OPTIONS: ComboOption<string>[] = [
  { value: '1', label: 'Sí' },
  { value: '0', label: 'No' },
];

function firstError(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    if (caught.errors) {
      const first = Object.values(caught.errors)[0];
      if (first && first.length > 0) return first[0];
    }
    return caught.message;
  }
  return fallback;
}

function SectionCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>{title}</Text>
      {children}
    </View>
  );
}

export default function DineroScreen() {
  const { user, isLoading } = useAuth();
  const [tab, setTab] = useState<DineroTab>('comisiones');
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Comisiones
  const [rules, setRules] = useState<DineroFeeRule[]>([]);
  const [ruleModule, setRuleModule] = useState('mercado');
  const [ruleCountry, setRuleCountry] = useState('');
  const [ruleCurrency, setRuleCurrency] = useState('');
  const [rulePercent, setRulePercent] = useState('2');
  const [ruleMin, setRuleMin] = useState('150');
  const [ruleActive, setRuleActive] = useState('1');

  // Matriz
  const [matrix, setMatrix] = useState<Record<string, string>>({
    matrix_AR_CBU: '',
    matrix_AR_ALIAS: '',
    matrix_AR_HOLDER: '',
    matrix_AR_BANK: '',
    matrix_ES_IBAN: '',
    matrix_ES_HOLDER: '',
    matrix_ES_BANK: '',
  });

  // Liquidaciones
  const [settlements, setSettlements] = useState<DineroSettlement[]>([]);
  const [settleUser, setSettleUser] = useState('');
  const [settleCurrency, setSettleCurrency] = useState('ARS');
  const [settleCharges, setSettleCharges] = useState('');
  const [settleNotes, setSettleNotes] = useState('');
  const [payReference, setPayReference] = useState('');

  // Cobros
  const [charges, setCharges] = useState<DineroCharge[]>([]);
  const [chModule, setChModule] = useState('mercado');
  const [chOrderType, setChOrderType] = useState('order');
  const [chOrder, setChOrder] = useState('');
  const [chAmount, setChAmount] = useState('');
  const [chMethod, setChMethod] = useState('transferencia');
  const [chRef, setChRef] = useState('');

  // Efectivo
  const [cashUser, setCashUser] = useState('');
  const [cashCurrency, setCashCurrency] = useState('ARS');
  const [debtResult, setDebtResult] = useState<string | null>(null);

  // Conciliación
  const [ledger, setLedger] = useState<DineroLedgerEntry[]>([]);
  const [ledType, setLedType] = useState('');
  const [ledCurrency, setLedCurrency] = useState('ARS');

  // Planes
  const [plans, setPlans] = useState<DineroPlan[]>([]);
  const [planName, setPlanName] = useState('');
  const [planCurrency, setPlanCurrency] = useState('ARS');
  const [planPrice, setPlanPrice] = useState('');
  const [planDays, setPlanDays] = useState('30');
  const [planGrace, setPlanGrace] = useState('15');
  const [planActive, setPlanActive] = useState('1');
  const [subUser, setSubUser] = useState('');
  const [subPlan, setSubPlan] = useState('');

  // Facturas
  const [invoices, setInvoices] = useState<DineroInvoice[]>([]);
  const [invStatus, setInvStatus] = useState('');
  const [invReference, setInvReference] = useState('');

  // Retiros de repartidores (admin)
  const [withdrawals, setWithdrawals] = useState<DineroDriverWithdrawal[]>([]);
  const [withdrawReason, setWithdrawReason] = useState('');

  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);

    Promise.all([
      api.dineroFeeRules().catch(() => null),
      api.dineroMatrix().catch(() => null),
      api.dineroSettlements({ status: 'pending' }).catch(() => null),
      api.dineroCharges({ status: 'pending_verification' }).catch(() => null),
      api.dineroLedger({ currency: 'ARS' }).catch(() => null),
      api.dineroPlans().catch(() => null),
      api.dineroInvoices().catch(() => null),
      api.dineroDriverWithdrawals().catch(() => null),
    ])
      .then(([r, m, s, c, l, p, i, w]) => {
        if (!active) return;
        if (r) setRules(r.data);
        if (m) {
          setMatrix((prev) => {
            const next = { ...prev };
            for (const key of Object.keys(next)) next[key] = m.data[key] ?? '';
            return next;
          });
        }
        if (s) setSettlements(s.data);
        if (c) setCharges(c.data);
        if (l) setLedger(l.data);
        if (p) setPlans(p.data);
        if (i) setInvoices(i.data);
        if (w) setWithdrawals(w.withdrawals);
      })
      .catch((caught: unknown) => {
        if (!active) return;
        setError(firstError(caught, 'No pudimos cargar Dinero.'));
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  if (isLoading || loading) {
    return <LoadingScreen message="Cargando Dinero…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  if (!user.is_admin) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.center}>
          <Text style={styles.title}>Dinero — backoffice</Text>
          <ErrorBanner message="Solo administradores." />
        </View>
      </SafeAreaView>
    );
  }

  function showNotice(text: string) {
    setNotice(text);
  }

  async function reloadSettlements() {
    const res = await api.dineroSettlements({ status: 'pending' });
    setSettlements(res.data);
  }

  async function reloadCharges() {
    const res = await api.dineroCharges({ status: 'pending_verification' });
    setCharges(res.data);
  }

  async function reloadRules() {
    const res = await api.dineroFeeRules();
    setRules(res.data);
  }

  async function reloadPlans() {
    const res = await api.dineroPlans();
    setPlans(res.data);
  }

  async function reloadInvoices() {
    const res = await api.dineroInvoices(
      invStatus ? { status: invStatus } : {},
    );
    setInvoices(res.data);
  }

  async function reloadDriverWithdrawals() {
    const res = await api.dineroDriverWithdrawals();
    setWithdrawals(res.withdrawals);
  }

  async function handleApproveWithdrawal(id: number) {
    setError(null);
    try {
      const res = await api.dineroApproveDriverWithdrawal(id);
      showNotice(res.message);
      await reloadDriverWithdrawals();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos aprobar el retiro.'));
    }
  }

  async function handleRejectWithdrawal(id: number) {
    setError(null);
    try {
      const res = await api.dineroRejectDriverWithdrawal(id, withdrawReason.trim() || null);
      showNotice(res.message);
      await reloadDriverWithdrawals();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos rechazar el retiro.'));
    }
  }

  async function reloadLedger() {
    const res = await api.dineroLedger({
      currency: (ledCurrency.trim() || 'ARS').toUpperCase(),
      ...(ledType ? { type: ledType } : {}),
    });
    setLedger(res.data);
  }

  async function handleSaveRule() {
    setError(null);
    setBusy(true);
    try {
      const res = await api.dineroCreateFeeRule({
        module: ruleModule,
        country: ruleCountry.trim() || null,
        currency: ruleCurrency.trim() || null,
        percent: parseFloat(rulePercent) || 0,
        min_fee: parseFloat(ruleMin) || 0,
        active: ruleActive === '1',
      });
      showNotice(res.message);
      await reloadRules();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos guardar la regla.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleToggleRule(rule: DineroFeeRule) {
    setError(null);
    try {
      const res = await api.dineroToggleFeeRule(rule.id, !rule.active);
      showNotice(res.message);
      await reloadRules();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos actualizar la regla.'));
    }
  }

  function setMatrixField(key: string, value: string) {
    setMatrix((prev) => ({ ...prev, [key]: value }));
  }

  async function handleSaveMatrix() {
    setError(null);
    setBusy(true);
    try {
      const res = await api.dineroSaveMatrix(matrix);
      showNotice(res.message ?? 'Guardado.');
    } catch (caught) {
      setError(firstError(caught, 'No pudimos guardar la matriz.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleCreateSettlement() {
    setError(null);
    const userId = parseInt(settleUser, 10);
    const ids = settleCharges
      .split(',')
      .map((x) => parseInt(x.trim(), 10))
      .filter((x) => !Number.isNaN(x));
    if (Number.isNaN(userId) || ids.length === 0) {
      setError('Ingresá el ID del usuario y al menos un ID de cobro.');
      return;
    }
    setBusy(true);
    try {
      const res = await api.dineroCreateSettlement({
        user_id: userId,
        currency: (settleCurrency.trim() || 'ARS').toUpperCase(),
        charge_ids: ids,
        notes: settleNotes.trim() || null,
      });
      showNotice(res.message);
      setSettleCharges('');
      await reloadSettlements();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos crear la liquidación.'));
    } finally {
      setBusy(false);
    }
  }

  async function handlePaySettlement(id: number) {
    if (!payReference.trim()) {
      setError('Ingresá el comprobante de la transferencia al comercio/prestador.');
      return;
    }
    setError(null);
    try {
      const res = await api.dineroPaySettlement(id, payReference.trim());
      showNotice(res.message);
      await reloadSettlements();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos pagar la liquidación.'));
    }
  }

  async function handleRegisterCharge() {
    setError(null);
    const orderId = parseInt(chOrder, 10);
    const amount = parseFloat(chAmount);
    if (Number.isNaN(orderId) || !(amount > 0)) {
      setError('Ingresá ID de pedido y monto válido.');
      return;
    }
    setBusy(true);
    try {
      const res = await api.dineroRegisterCharge({
        module: chModule,
        order_type: chOrderType,
        order_id: orderId,
        amount,
        method: chMethod,
        reference: chRef.trim() || null,
        currency: 'ARS',
      });
      showNotice(res.message);
      setChOrder('');
      setChAmount('');
      setChRef('');
      await reloadCharges();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos registrar el cobro.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleVerifyCharge(id: number) {
    setError(null);
    try {
      const res = await api.dineroVerifyCharge(id);
      showNotice(res.message);
      await reloadCharges();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos verificar el cobro.'));
    }
  }

  async function handleCheckDebt() {
    const uid = parseInt(cashUser, 10);
    if (Number.isNaN(uid)) {
      setDebtResult('Ingresá el ID del usuario.');
      return;
    }
    setError(null);
    try {
      const res = await api.dineroOperatingStatus(
        uid,
        (cashCurrency.trim() || 'ARS').toUpperCase(),
      );
      const d = res.data;
      setDebtResult(
        `Puede operar: ${d.can_operate ? 'Sí' : 'No'} · Deuda efectivo: ${d.cash_debt} ${d.currency}`,
      );
    } catch {
      setDebtResult('No se pudo consultar.');
    }
  }

  async function handleLoadLedger() {
    setError(null);
    try {
      await reloadLedger();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos cargar la conciliación.'));
    }
  }

  async function handleSavePlan() {
    if (!planName.trim()) {
      setError('El plan necesita un nombre.');
      return;
    }
    setError(null);
    setBusy(true);
    try {
      const res = await api.dineroCreatePlan({
        name: planName.trim(),
        currency: (planCurrency.trim() || 'ARS').toUpperCase(),
        price: parseFloat(planPrice) || 0,
        billing_days: parseInt(planDays, 10) || 30,
        grace_days: parseInt(planGrace, 10) || 15,
        active: planActive === '1',
      });
      showNotice(res.message);
      setPlanName('');
      setPlanPrice('');
      await reloadPlans();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos guardar el plan.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleTogglePlan(id: number, active: boolean) {
    setError(null);
    try {
      const res = await api.dineroUpdatePlan(id, { active });
      showNotice(res.message);
      await reloadPlans();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos actualizar el plan.'));
    }
  }

  async function handleSubscribe() {
    const userId = parseInt(subUser, 10);
    const planId = parseInt(subPlan, 10);
    if (Number.isNaN(userId) || Number.isNaN(planId)) {
      setError('Ingresá ID de usuario y de plan.');
      return;
    }
    setError(null);
    setBusy(true);
    try {
      const res = await api.dineroSubscribe({ user_id: userId, plan_id: planId });
      showNotice(res.message);
      await reloadInvoices();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos suscribir.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleFilterInvoices() {
    setError(null);
    try {
      await reloadInvoices();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos cargar las facturas.'));
    }
  }

  async function handlePayInvoice(id: number) {
    setError(null);
    try {
      const res = await api.dineroPayInvoice(id, invReference.trim() || null);
      showNotice(res.message);
      await reloadInvoices();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos marcar la factura.'));
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Abrir secciones de Dinero"
          onPress={() => setDrawerOpen(true)}
          style={styles.menuButton}
        >
          <Text style={styles.menuText}>☰</Text>
        </Pressable>
        <View>
          <Text style={styles.title}>Dinero — backoffice</Text>
          <Text style={styles.subtitle}>Fase 1: manual · Solo Admin</Text>
        </View>
      </View>
      <ScrollView contentContainerStyle={styles.container}>
        {error ? <ErrorBanner message={error} /> : null}
        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        {tab === 'comisiones' ? (
          <View>
            <SectionCard title="Reglas de comisión">
              {rules.length === 0 ? (
                <Text style={styles.empty}>Sin reglas. Creá la primera abajo.</Text>
              ) : (
                rules.map((rule) => (
                  <View key={rule.id} style={styles.item}>
                    <Text style={styles.itemTitle}>
                      {rule.module} · {rule.country ?? '—'} · {rule.currency ?? '—'}
                    </Text>
                    <Text style={styles.itemSub}>
                      {rule.percent}% · Mínimo {money(rule.min_fee)} ·{' '}
                      {rule.active ? 'Sí' : 'No'}
                    </Text>
                    <PrimaryButton
                      title={rule.active ? 'Desactivar' : 'Activar'}
                      variant="ghost"
                      onPress={() => void handleToggleRule(rule)}
                    />
                  </View>
                ))
              )}
            </SectionCard>
            <SectionCard title="Nueva regla">
              <ComboField label="Módulo" value={ruleModule} options={MODULE_OPTIONS} onChange={setRuleModule} />
              <TextField label="País (vacío = todos)" value={ruleCountry} onChangeText={setRuleCountry} maxLength={2} autoCapitalize="characters" />
              <TextField label="Moneda (vacío = todas)" value={ruleCurrency} onChangeText={setRuleCurrency} maxLength={3} autoCapitalize="characters" />
              <TextField label="% comisión" value={rulePercent} onChangeText={setRulePercent} keyboardType="decimal-pad" />
              <TextField label="Mínimo por cobro" value={ruleMin} onChangeText={setRuleMin} keyboardType="decimal-pad" />
              <ComboField label="Activa" value={ruleActive} options={YES_NO_OPTIONS} onChange={setRuleActive} />
              <PrimaryButton title="Guardar regla" loading={busy} onPress={() => void handleSaveRule()} />
              <Text style={styles.hint}>
                Cambiar el % rige para cobros futuros. Lo ya cobrado conserva su foto.
              </Text>
            </SectionCard>
          </View>
        ) : null}

        {tab === 'matriz' ? (
          <SectionCard title="Cuenta matriz de PideAca (donde pagan los clientes)">
            <TextField label="ARS - CBU" value={matrix.matrix_AR_CBU} onChangeText={(v) => setMatrixField('matrix_AR_CBU', v)} keyboardType="numeric" />
            <TextField label="ARS - Alias" value={matrix.matrix_AR_ALIAS} onChangeText={(v) => setMatrixField('matrix_AR_ALIAS', v)} />
            <TextField label="ARS - Titular" value={matrix.matrix_AR_HOLDER} onChangeText={(v) => setMatrixField('matrix_AR_HOLDER', v)} />
            <TextField label="ARS - Banco" value={matrix.matrix_AR_BANK} onChangeText={(v) => setMatrixField('matrix_AR_BANK', v)} />
            <TextField label="EUR - IBAN" value={matrix.matrix_ES_IBAN} onChangeText={(v) => setMatrixField('matrix_ES_IBAN', v)} />
            <TextField label="EUR - Titular" value={matrix.matrix_ES_HOLDER} onChangeText={(v) => setMatrixField('matrix_ES_HOLDER', v)} />
            <TextField label="EUR - Banco" value={matrix.matrix_ES_BANK} onChangeText={(v) => setMatrixField('matrix_ES_BANK', v)} />
            <PrimaryButton title="Guardar matriz" loading={busy} onPress={() => void handleSaveMatrix()} />
          </SectionCard>
        ) : null}

        {tab === 'liquidaciones' ? (
          <View>
            <SectionCard title="Liquidaciones pendientes">
              <TextField
                label="Referencia de pago (comprobante)"
                value={payReference}
                onChangeText={setPayReference}
                placeholder="Requerida para pagar"
              />
              {settlements.length === 0 ? (
                <Text style={styles.empty}>Sin liquidaciones pendientes.</Text>
              ) : (
                settlements.map((s) => (
                  <View key={s.id} style={styles.item}>
                    <Text style={styles.itemTitle}>
                      #{s.id} · {(s.user && (s.user.name || s.user.email)) || `#${s.user_id}`}
                    </Text>
                    <Text style={styles.itemSub}>
                      Bruto {money(s.gross)} · Comisión {money(s.fee)} · Neto {money(s.net)} {s.currency} · {s.status}
                    </Text>
                    <View style={styles.actions}>
                      <View style={styles.actionFlex}>
                        <PrimaryButton title="Pagar" onPress={() => void handlePaySettlement(s.id)} />
                      </View>
                    </View>
                  </View>
                ))
              )}
            </SectionCard>
            <SectionCard title="Crear liquidación">
              <TextField label="ID usuario (beneficiario)" value={settleUser} onChangeText={setSettleUser} keyboardType="numeric" />
              <TextField label="Moneda" value={settleCurrency} onChangeText={setSettleCurrency} maxLength={3} autoCapitalize="characters" />
              <TextField label="IDs de cobros (coma)" value={settleCharges} onChangeText={setSettleCharges} placeholder="12,13" />
              <TextField label="Notas" value={settleNotes} onChangeText={setSettleNotes} />
              <PrimaryButton title="Crear liquidación" loading={busy} onPress={() => void handleCreateSettlement()} />
            </SectionCard>
          </View>
        ) : null}

        {tab === 'cobros' ? (
          <View>
            <SectionCard title="Registrar cobro manual">
              <ComboField label="Módulo" value={chModule} options={MODULE_OPTIONS} onChange={setChModule} />
              <ComboField label="Tipo pedido" value={chOrderType} options={ORDER_TYPE_OPTIONS} onChange={setChOrderType} />
              <TextField label="ID pedido" value={chOrder} onChangeText={setChOrder} keyboardType="numeric" />
              <TextField label="Monto" value={chAmount} onChangeText={setChAmount} keyboardType="decimal-pad" />
              <ComboField label="Medio" value={chMethod} options={METHOD_OPTIONS} onChange={setChMethod} />
              <TextField label="Referencia" value={chRef} onChangeText={setChRef} placeholder="Código / comprobante" />
              <PrimaryButton title="Registrar cobro" loading={busy} onPress={() => void handleRegisterCharge()} />
            </SectionCard>
            <SectionCard title="Pendientes de verificación">
              {charges.length === 0 ? (
                <Text style={styles.empty}>Sin cobros pendientes.</Text>
              ) : (
                charges.map((c) => (
                  <View key={c.id} style={styles.item}>
                    <Text style={styles.itemTitle}>
                      #{c.id} · {c.order_type} #{c.order_id}
                    </Text>
                    <Text style={styles.itemSub}>
                      {money(c.amount)} {c.currency} · Comisión {money(c.fee)} · {c.method}
                    </Text>
                    {c.method === 'transferencia' ? (
                      <PrimaryButton title="Verificar" onPress={() => void handleVerifyCharge(c.id)} />
                    ) : (
                      <Text style={styles.itemSub}>Auto (efectivo)</Text>
                    )}
                  </View>
                ))
              )}
            </SectionCard>
          </View>
        ) : null}

        {tab === 'efectivo' ? (
          <SectionCard title="Deudas de efectivo por usuario">
            <TextField label="ID usuario" value={cashUser} onChangeText={setCashUser} keyboardType="numeric" />
            <TextField label="Moneda" value={cashCurrency} onChangeText={setCashCurrency} maxLength={3} autoCapitalize="characters" />
            <PrimaryButton title="Consultar deuda" variant="ghost" onPress={() => void handleCheckDebt()} />
            {debtResult ? <Text style={styles.result}>{debtResult}</Text> : null}
          </SectionCard>
        ) : null}

        {tab === 'conciliacion' ? (
          <SectionCard title="Conciliación (últimos movimientos)">
            <ComboField label="Tipo" value={ledType} options={LEDGER_TYPE_OPTIONS} onChange={setLedType} />
            <TextField label="Moneda" value={ledCurrency} onChangeText={setLedCurrency} maxLength={3} autoCapitalize="characters" />
            <PrimaryButton title="Actualizar" variant="ghost" onPress={() => void handleLoadLedger()} />
            {ledger.length === 0 ? (
              <Text style={styles.empty}>Sin movimientos.</Text>
            ) : (
              ledger.map((m) => (
                <View key={m.id} style={styles.item}>
                  <Text style={styles.itemTitle}>
                    #{m.id} · {m.type} · {money(m.amount)} {m.currency}
                  </Text>
                  <Text style={styles.itemSub}>
                    {(m.user && (m.user.name || m.user.email)) || 'PideAca'} ·{' '}
                    {(m.reference_type || '-') + ' ' + (m.reference_id || '')}
                    {m.description ? ` · ${m.description}` : ''}
                  </Text>
                </View>
              ))
            )}
          </SectionCard>
        ) : null}

        {tab === 'planes' ? (
          <View>
            <SectionCard title="Planes Hogar">
              {plans.length === 0 ? (
                <Text style={styles.empty}>Sin planes. Creá el primero abajo.</Text>
              ) : (
                plans.map((p) => (
                  <View key={p.id} style={styles.item}>
                    <Text style={styles.itemTitle}>
                      #{p.id} {p.name} · {money(p.price)} {p.currency} · {p.billing_days}d + {p.grace_days}d gracia
                    </Text>
                    <Text style={[styles.itemSub, p.active ? styles.ok : styles.bad]}>
                      {p.active ? 'Activo' : 'Inactivo'}
                    </Text>
                    <PrimaryButton
                      title={p.active ? 'Desactivar' : 'Activar'}
                      variant="ghost"
                      onPress={() => void handleTogglePlan(p.id, !p.active)}
                    />
                  </View>
                ))
              )}
            </SectionCard>
            <SectionCard title="Nuevo plan">
              <TextField label="Nombre" value={planName} onChangeText={setPlanName} placeholder="Hogar Mensual" />
              <TextField label="Moneda" value={planCurrency} onChangeText={setPlanCurrency} maxLength={3} autoCapitalize="characters" />
              <TextField label="Precio" value={planPrice} onChangeText={setPlanPrice} keyboardType="decimal-pad" />
              <TextField label="Días período" value={planDays} onChangeText={setPlanDays} keyboardType="numeric" />
              <TextField label="Días gracia" value={planGrace} onChangeText={setPlanGrace} keyboardType="numeric" />
              <ComboField label="Activo" value={planActive} options={YES_NO_OPTIONS} onChange={setPlanActive} />
              <PrimaryButton title="Guardar plan" loading={busy} onPress={() => void handleSavePlan()} />
            </SectionCard>
            <SectionCard title="Suscribir prestador">
              <TextField label="ID usuario" value={subUser} onChangeText={setSubUser} keyboardType="numeric" />
              <TextField label="ID plan" value={subPlan} onChangeText={setSubPlan} keyboardType="numeric" />
              <PrimaryButton title="Suscribir" variant="ghost" loading={busy} onPress={() => void handleSubscribe()} />
            </SectionCard>
          </View>
        ) : null}

        {tab === 'facturas' ? (
          <SectionCard title="Facturas de suscripción">
            <ComboField label="Estado" value={invStatus} options={INVOICE_STATUS_OPTIONS} onChange={setInvStatus} />
            <PrimaryButton title="Filtrar" variant="ghost" onPress={() => void handleFilterInvoices()} />
            <TextField
              label="Comprobante (opcional, para marcar pagada)"
              value={invReference}
              onChangeText={setInvReference}
            />
            {invoices.length === 0 ? (
              <Text style={styles.empty}>Sin facturas.</Text>
            ) : (
              invoices.map((inv) => (
                <View key={inv.id} style={styles.item}>
                  <Text style={styles.itemTitle}>
                    #{inv.id} · {(inv.user && (inv.user.name || inv.user.email)) || `#${inv.user_id}`}
                  </Text>
                  <Text style={styles.itemSub}>
                    {money(inv.amount)} {inv.currency} · Vence {inv.due_date || '—'} · {inv.status}
                  </Text>
                  {inv.status === 'pending' || inv.status === 'overdue' ? (
                    <PrimaryButton title="Marcar pagada" onPress={() => void handlePayInvoice(inv.id)} />
                  ) : (
                    <Text style={styles.itemSub}>—</Text>
                  )}
                </View>
              ))
            )}
          </SectionCard>
        ) : null}

        {tab === 'retiros' ? (
          <SectionCard title="Retiros de repartidores pendientes">
            <TextField
              label="Motivo del rechazo (opcional)"
              value={withdrawReason}
              onChangeText={setWithdrawReason}
              placeholder="Se usa solo al rechazar"
            />
            {withdrawals.length === 0 ? (
              <Text style={styles.empty}>Sin retiros pendientes.</Text>
            ) : (
              withdrawals.map((w) => (
                <View key={w.id} style={styles.item}>
                  <Text style={styles.itemTitle}>
                    #{w.id} · {(w.user && (w.user.name || w.user.email)) || `#${w.user_id}`} · {money(w.amount)}
                  </Text>
                  <Text style={styles.itemSub}>
                    {w.method ?? '—'} · {w.status}
                    {w.reference ? ` · Comp. ${w.reference}` : ''}
                    {w.notes ? ` · ${w.notes}` : ''}
                  </Text>
                  <View style={styles.actions}>
                    <View style={styles.actionFlex}>
                      <PrimaryButton title="Aprobar" onPress={() => void handleApproveWithdrawal(w.id)} />
                    </View>
                    <View style={styles.actionFlex}>
                      <PrimaryButton title="Rechazar" variant="ghost" onPress={() => void handleRejectWithdrawal(w.id)} />
                    </View>
                  </View>
                </View>
              ))
            )}
          </SectionCard>
        ) : null}
      </ScrollView>

      {drawerOpen ? (
        <View style={styles.drawerOverlay}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Cerrar secciones de Dinero"
            onPress={() => setDrawerOpen(false)}
            style={styles.drawerBackdrop}
          />
          <View style={styles.drawerPanel}>
            <Text style={styles.drawerTitle}>Dinero — backoffice</Text>
            <Text style={styles.drawerSubtitle}>Fase 1: manual · Solo Admin</Text>
            {TABS.map((t) => (
              <Pressable
                key={t.key}
                accessibilityRole="tab"
                accessibilityState={{ selected: tab === t.key }}
                onPress={() => {
                  setTab(t.key);
                  setDrawerOpen(false);
                }}
                style={[styles.drawerItem, tab === t.key && styles.drawerItemActive]}
              >
                <Text style={[styles.drawerText, tab === t.key && styles.drawerTextActive]}>
                  {t.label}
                </Text>
              </Pressable>
            ))}
          </View>
        </View>
      ) : null}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  container: {
    padding: spacing.lg,
  },
  center: {
    padding: spacing.xl,
  },
  title: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.lg,
    marginTop: spacing.xs,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  menuButton: {
    padding: spacing.xs,
  },
  menuText: {
    color: colors.text,
    fontSize: 20,
  },
  drawerOverlay: {
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
    zIndex: 10,
  },
  drawerBackdrop: {
    backgroundColor: 'rgba(15, 23, 42, 0.5)',
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
  },
  drawerPanel: {
    backgroundColor: colors.surface,
    bottom: 0,
    left: 0,
    padding: spacing.lg,
    position: 'absolute',
    top: 0,
    width: 260,
  },
  drawerTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  drawerSubtitle: {
    color: colors.muted,
    fontSize: 12,
    marginBottom: spacing.lg,
    marginTop: spacing.xs,
  },
  drawerItem: {
    borderRadius: radius.md,
    marginBottom: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  drawerItemActive: {
    backgroundColor: colors.brand,
  },
  drawerText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600',
  },
  drawerTextActive: {
    color: colors.surface,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    marginBottom: spacing.md,
  },
  item: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    marginBottom: spacing.md,
    paddingBottom: spacing.md,
  },
  itemTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  itemSub: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
    marginTop: spacing.xs,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  actionFlex: {
    flex: 1,
  },
  empty: {
    color: colors.muted,
    fontSize: 13,
  },
  hint: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.sm,
  },
  result: {
    color: colors.text,
    fontSize: 13,
    marginTop: spacing.md,
  },
  ok: {
    color: colors.success,
  },
  bad: {
    color: colors.danger,
  },
  notice: {
    backgroundColor: colors.successSoft,
    borderRadius: radius.md,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  noticeText: {
    color: colors.success,
    fontSize: 13,
    fontWeight: '600',
  },
});
