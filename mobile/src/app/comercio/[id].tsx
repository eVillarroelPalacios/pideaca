import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { LoadingScreen } from '@/components/LoadingScreen';
import { resolveAssetUrl } from '@/lib/assets';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  MarketAddress,
  MarketCatalogResponse,
  MarketOrder,
  MarketProduct,
  MarketProductOptionGroup,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type DeliveryType = 'delivery' | 'takeaway';

type CartLine = {
  /** Identifica producto + variante + opciones para poder fusionar líneas. */
  key: string;
  product_id: number;
  variant_id: number | null;
  quantity: number;
  product_name: string;
  variant_name: string;
  unit_price: number;
  options: number[];
  options_extra: number;
  option_labels: string[];
};

type NewCartLine = {
  product_id: number;
  variant_id: number | null;
  quantity: number;
  product_name: string;
  variant_name: string;
  unit_price: number;
  options: number[];
  options_extra: number;
  option_labels: string[];
};

type Notice = { text: string; error: boolean };

/** Mismas opciones de pago que el `<select>` de la web. */
const PAYMENT_OPTIONS: ComboOption<string>[] = [
  { value: 'efectivo', label: 'Efectivo' },
  { value: 'transfer', label: 'Transferencia' },
];

function firstError(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    if (caught.errors) {
      const first = Object.values(caught.errors)[0];

      if (first && first.length > 0) {
        return first[0];
      }
    }

    return caught.message;
  }

  return fallback;
}

/** Texto de horarios: la web acepta arreglo o cadena; los objetos no se muestran. */
function hoursLabel(hours: Record<string, unknown> | null | undefined): string {
  if (!hours) {
    return '';
  }

  if (Array.isArray(hours)) {
    return hours.map((item) => String(item)).join(' · ');
  }

  if (typeof hours === 'string') {
    return hours;
  }

  return '';
}

function optionHint(group: MarketProductOptionGroup): string {
  const required = group.is_required || group.min_choices > 0;
  const limit = group.max_choices > 0 ? `, hasta ${group.max_choices}` : '';

  return `${required ? 'obligatorio' : 'opcional'}${limit}`;
}

/**
 * Ficha de producto del catálogo: imagen, variante, adicionales con límite,
 * cantidad y botón para agregar. Mantiene su propio borrador para que el
 * carrito de la pantalla solo reciba líneas ya validadas.
 */
function ProductCard({
  product,
  maxQuantity,
  onAdd,
}: {
  product: MarketProduct;
  maxQuantity: number;
  onAdd: (line: NewCartLine) => void;
}) {
  const [variantId, setVariantId] = useState<number | null>(
    product.variants[0]?.id ?? null,
  );
  const [quantity, setQuantity] = useState(1);
  const [selected, setSelected] = useState<Record<number, number[]>>({});
  const [message, setMessage] = useState<string | null>(null);

  const image = resolveAssetUrl(product.image_url);
  const initial = product.name.trim().charAt(0).toUpperCase() || '?';
  const variant = product.variants.find((item) => item.id === variantId) ?? null;
  const unitPrice = variant ? variant.price : product.price;
  const cap = Math.max(maxQuantity, 1);

  const variantOptions: ComboOption<number>[] = product.variants.map((item) => ({
    value: item.id,
    label: `${item.name} — ${money(item.price)}`,
  }));

  function toggleOption(group: MarketProductOptionGroup, optionId: number) {
    const current = selected[group.id] ?? [];
    const active = current.includes(optionId);

    setMessage(null);

    if (active) {
      setSelected((state) => ({
        ...state,
        [group.id]: current.filter((id) => id !== optionId),
      }));
      return;
    }

    if (group.max_choices > 0 && current.length >= group.max_choices) {
      setMessage(
        `Podés elegir hasta ${group.max_choices} opciones de "${group.name}".`,
      );
      return;
    }

    setSelected((state) => ({
      ...state,
      [group.id]: [...current, optionId],
    }));
  }

  function handleAdd() {
    if (product.is_out_of_stock) {
      setMessage('Sin stock en este momento.');
      return;
    }

    if (quantity > cap) {
      setMessage(`Máximo ${cap} unidades por producto.`);
      return;
    }

    const optionIds: number[] = [];
    const optionLabels: string[] = [];
    let optionsExtra = 0;

    for (const group of product.option_groups) {
      const chosen = selected[group.id] ?? [];

      if (group.min_choices > 0 && chosen.length < group.min_choices) {
        setMessage(
          `Elegí al menos ${group.min_choices} opción de "${group.name}".`,
        );
        return;
      }

      if (group.max_choices > 0 && chosen.length > group.max_choices) {
        setMessage(
          `Podés elegir hasta ${group.max_choices} opciones de "${group.name}".`,
        );
        return;
      }

      for (const id of chosen) {
        const option = group.options.find((item) => item.id === id);

        if (!option) {
          continue;
        }

        optionIds.push(option.id);
        optionLabels.push(option.name);
        optionsExtra += option.extra_price;
      }
    }

    onAdd({
      product_id: product.id,
      variant_id: variant?.id ?? null,
      quantity,
      product_name: product.name,
      variant_name: variant?.name ?? '',
      unit_price: unitPrice,
      options: optionIds,
      options_extra: optionsExtra,
      option_labels: optionLabels,
    });

    setQuantity(1);
    setMessage(null);
  }

  return (
    <View style={styles.productCard}>
      <View style={styles.productHead}>
        <View style={styles.productPhoto}>
          {image ? (
            <Image source={{ uri: image }} style={styles.productImage} />
          ) : (
            <Text style={styles.productInitial}>{initial}</Text>
          )}
        </View>

        <View style={styles.productInfo}>
          <View style={styles.productTitleRow}>
            <Text style={styles.productName} numberOfLines={2}>
              {product.name}
            </Text>
            <Text style={styles.productPrice}>{money(unitPrice)}</Text>
          </View>
          {product.description ? (
            <Text style={styles.productDescription}>{product.description}</Text>
          ) : null}
        </View>
      </View>

      {product.variants.length > 1 ? (
        <View style={styles.variantField}>
          <ComboField<number>
            label="Presentación"
            value={variantId}
            options={variantOptions}
            onChange={(next) => setVariantId(next)}
            placeholder="Elegí una presentación"
            title={`Presentación de ${product.name}`}
          />
        </View>
      ) : variant ? (
        <Text style={styles.singleVariant}>{variant.name}</Text>
      ) : null}

      {product.option_groups.map((group) => {
        if (group.options.length === 0) {
          return null;
        }

        const chosen = selected[group.id] ?? [];

        return (
          <View key={group.id} style={styles.optionGroup}>
            <Text style={styles.optionTitle}>
              {group.name}{' '}
              <Text style={styles.optionHint}>({optionHint(group)})</Text>
            </Text>
            <View style={styles.optionList}>
              {group.options.map((option) => {
                const active = chosen.includes(option.id);

                return (
                  <Pressable
                    key={option.id}
                    accessibilityRole="checkbox"
                    accessibilityState={{ checked: active }}
                    onPress={() => toggleOption(group, option.id)}
                    style={[styles.optionChip, active && styles.optionChipActive]}
                  >
                    <Text
                      style={[
                        styles.optionChipText,
                        active && styles.optionChipTextActive,
                      ]}
                    >
                      {option.name}
                      {option.extra_price > 0 ? (
                        <Text style={styles.optionExtra}>
                          {' '}
                          + {money(option.extra_price)}
                        </Text>
                      ) : null}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          </View>
        );
      })}

      {message ? <Text style={styles.productMessage}>{message}</Text> : null}

      <View style={styles.productFooter}>
        <View
          style={[
            styles.qtyControls,
            product.is_out_of_stock && styles.qtyDisabled,
          ]}
        >
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Quitar una unidad"
            disabled={product.is_out_of_stock || quantity <= 1}
            onPress={() => setQuantity((current) => Math.max(1, current - 1))}
            style={styles.qtyButton}
          >
            <Text style={styles.qtyButtonText}>−</Text>
          </Pressable>
          <Text style={styles.qtyValue}>{quantity}</Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Agregar una unidad"
            disabled={product.is_out_of_stock || quantity >= cap}
            onPress={() =>
              setQuantity((current) => Math.min(cap, current + 1))
            }
            style={styles.qtyButton}
          >
            <Text style={styles.qtyButtonText}>+</Text>
          </Pressable>
        </View>

        {product.is_out_of_stock ? (
          <View style={styles.outOfStock}>
            <Text style={styles.outOfStockText}>Sin stock</Text>
          </View>
        ) : (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={`Agregar ${product.name} al carrito`}
            onPress={handleAdd}
            style={styles.addButton}
          >
            <Text style={styles.addButtonText}>🛒 Agregar</Text>
          </Pressable>
        )}
      </View>
    </View>
  );
}

export default function ComercioCatalogScreen() {
  const params = useLocalSearchParams<{
    id?: string | string[];
    repeat?: string | string[];
  }>();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const repeatRaw = Array.isArray(params.repeat) ? params.repeat[0] : params.repeat;
  const providerId = Number(rawId);
  const valid = Number.isFinite(providerId) && providerId > 0;
  const repeatApplied = useRef(false);

  const [catalog, setCatalog] = useState<MarketCatalogResponse | null>(null);
  const [addresses, setAddresses] = useState<MarketAddress[]>([]);
  const [addressId, setAddressId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [cart, setCart] = useState<CartLine[]>([]);
  const [categoryIndex, setCategoryIndex] = useState<number | null>(null);
  const [deliveryType, setDeliveryType] = useState<DeliveryType>('delivery');
  const [paymentMethod, setPaymentMethod] = useState('efectivo');
  const [notes, setNotes] = useState('');
  const [notice, setNotice] = useState<Notice | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState<MarketOrder | null>(null);

  useEffect(() => {
    if (!valid) {
      return;
    }

    let active = true;

    Promise.allSettled([api.marketCatalog(providerId), api.marketAddresses()])
      .then(([catalogResult, addressResult]) => {
        if (!active) {
          return;
        }

        if (catalogResult.status === 'fulfilled') {
          const response = catalogResult.value;

          setCatalog(response);
          setError(null);

          // "Repetir este pedido": se recuperan los productos del pedido
          // anterior y se precargan en el carrito con los precios actuales.
          if (repeatRaw && !repeatApplied.current) {
            repeatApplied.current = true;

            try {
              const requested = JSON.parse(repeatRaw) as {
                product_id: number | null;
                variant_id: number | null;
                quantity: number;
              }[];
              const products = response.categories.flatMap((category) => category.products);
              const cap = Math.max(response.settings.max_quantity_per_item ?? 20, 1);
              const maxLines = response.settings.max_items_per_order ?? 30;
              const lines: CartLine[] = [];
              let skipped = 0;

              for (const entry of requested) {
                if (!entry.product_id) {
                  skipped += 1;
                  continue;
                }

                const product = products.find(
                  (item) => item.id === entry.product_id && !item.is_out_of_stock,
                );

                if (!product) {
                  skipped += 1;
                  continue;
                }

                const variant = entry.variant_id
                  ? product.variants.find((item) => item.id === entry.variant_id) ?? null
                  : null;

                if (entry.variant_id && !variant) {
                  skipped += 1;
                  continue;
                }

                const key = `${product.id}|${variant?.id ?? 'x'}|`;
                const quantity = Math.min(
                  Math.max(Number(entry.quantity) || 1, 1),
                  cap,
                );
                const existing = lines.find((line) => line.key === key);

                if (existing) {
                  existing.quantity = Math.min(existing.quantity + quantity, cap);
                  continue;
                }

                if (lines.length >= maxLines) {
                  skipped += 1;
                  continue;
                }

                lines.push({
                  key,
                  product_id: product.id,
                  variant_id: variant?.id ?? null,
                  quantity,
                  product_name: product.name,
                  variant_name: variant?.name ?? '',
                  unit_price: variant?.price ?? product.price,
                  options: [],
                  options_extra: 0,
                  option_labels: [],
                });
              }

              if (lines.length > 0) {
                setCart(lines);
                setNotice({
                  text: `Cargamos ${lines.length} ${
                    lines.length === 1 ? 'producto' : 'productos'
                  } de tu pedido anterior${skipped > 0 ? ` (${skipped} sin stock)` : ''}.`,
                  error: false,
                });
              } else {
                setNotice({
                  text: 'Los productos de ese pedido ya no están disponibles.',
                  error: true,
                });
              }
            } catch {
              setNotice({
                text: 'No pudimos recuperar los productos de ese pedido.',
                error: true,
              });
            }
          }
        } else {
          setError(
            firstError(
              catalogResult.reason,
              'No pudimos cargar el catálogo del comercio.',
            ),
          );
        }

        if (addressResult.status === 'fulfilled') {
          const list = addressResult.value.addresses;
          const primary = list.find((item) => item.is_primary) ?? list[0];

          setAddresses(list);

          if (primary) {
            setAddressId(primary.id);
          }
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [providerId, repeatRaw, valid]);

  const settings = catalog?.settings;
  const maxQuantity = settings?.max_quantity_per_item ?? 20;
  const maxItems = settings?.max_items_per_order ?? 30;
  const deliveryFee = settings?.delivery_fee ?? 0;

  const provider = catalog?.provider ?? null;
  const categories = useMemo(() => catalog?.categories ?? [], [catalog]);

  const categoryOptions = useMemo<ComboOption<number>[]>(
    () => categories.map((category, index) => ({ value: index, label: category.name })),
    [categories],
  );

  const addressOptions = useMemo<ComboOption<number>[]>(
    () =>
      addresses.map((address) => ({
        value: address.id,
        label: `${address.formatted}${address.is_primary ? ' · principal' : ''}`,
      })),
    [addresses],
  );

  const visibleCategories =
    categoryIndex !== null && categories[categoryIndex]
      ? [categories[categoryIndex]]
      : categories;

  const subtotal = cart.reduce(
    (sum, line) => sum + (line.unit_price + line.options_extra) * line.quantity,
    0,
  );
  const units = cart.reduce((sum, line) => sum + line.quantity, 0);
  const fee = deliveryType === 'delivery' ? deliveryFee : 0;
  const total = subtotal + fee;

  function addToCart(line: NewCartLine) {
    const key = `${line.product_id}|${line.variant_id ?? 'x'}|${[...line.options]
      .sort((a, b) => a - b)
      .join(',')}`;
    const existing = cart.find((item) => item.key === key);

    if (existing) {
      if (existing.quantity + line.quantity > Math.max(maxQuantity, 1)) {
        setNotice({
          text: `Máximo ${maxQuantity} unidades por producto.`,
          error: true,
        });
        return;
      }

      setCart((current) =>
        current.map((item) =>
          item.key === key
            ? { ...item, quantity: item.quantity + line.quantity }
            : item,
        ),
      );
    } else {
      if (cart.length >= maxItems) {
        setNotice({
          text: `Podés llevar hasta ${maxItems} productos distintos por pedido.`,
          error: true,
        });
        return;
      }

      setCart((current) => [...current, { ...line, key }]);
    }

    setNotice({ text: `${line.product_name} agregado al carrito.`, error: false });
    setSubmitError(null);
  }

  function changeLineQty(key: string, delta: number) {
    setCart((current) =>
      current.flatMap((line) => {
        if (line.key !== key) {
          return [line];
        }

        const next = line.quantity + delta;

        if (next < 1) {
          return [];
        }

        return [{ ...line, quantity: Math.min(next, Math.max(maxQuantity, 1)) }];
      }),
    );
    setSubmitError(null);
  }

  function removeLine(key: string) {
    setCart((current) => current.filter((line) => line.key !== key));
    setSubmitError(null);
  }

  async function handleSubmit() {
    if (submitting) {
      return;
    }

    setSubmitError(null);
    setNotice(null);

    if (cart.length === 0) {
      setSubmitError('Agregá al menos un producto antes de confirmar.');
      return;
    }

    if (deliveryType === 'delivery' && !addressId) {
      setSubmitError('Seleccioná la dirección de entrega.');
      return;
    }

    setSubmitting(true);

    try {
      const response = await api.createMarketOrder({
        provider_id: providerId,
        delivery_type: deliveryType,
        address_id: deliveryType === 'delivery' ? addressId : null,
        payment_method: paymentMethod,
        notes: notes.trim() || null,
        items: cart.map((line) => ({
          product_id: line.product_id,
          variant_id: line.variant_id,
          quantity: line.quantity,
          options: line.options,
        })),
      });

      setCart([]);
      setNotes('');
      setSuccess(response.order);
    } catch (caught) {
      setSubmitError(
        firstError(caught, 'No se pudo registrar el pedido. Intentá de nuevo.'),
      );
    } finally {
      setSubmitting(false);
    }
  }

  if (!valid) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.stateScreen}>
          <Text style={styles.stateTitle}>Comercio no válido</Text>
          <Text style={styles.stateText}>No encontramos el comercio solicitado.</Text>
          <Pressable
            accessibilityRole="button"
            onPress={() => router.replace('/comercios')}
            style={styles.stateButton}
          >
            <Text style={styles.stateButtonText}>Volver a comercios</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  if (loading) {
    return <LoadingScreen message="Cargando catálogo…" />;
  }

  if (!catalog || !provider) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.stateScreen}>
          <Text style={styles.stateTitle}>No pudimos abrir el comercio</Text>
          <Text style={styles.stateText}>
            {error ?? 'El comercio no está disponible en este momento.'}
          </Text>
          <Pressable
            accessibilityRole="button"
            onPress={() => router.back()}
            style={styles.stateButton}
          >
            <Text style={styles.stateButtonText}>Volver</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  if (success) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <ScrollView contentContainerStyle={styles.successBody}>
          <View style={styles.successCard}>
            <Text style={styles.successIcon}>✓</Text>
            <Text style={styles.successTitle}>Pedido registrado</Text>
            <Text style={styles.successText}>
              Número <Text style={styles.successStrong}>{success.order_number}</Text>
              . El comercio va a confirmar tu pedido.
            </Text>
            <View style={styles.successTotals}>
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Subtotal</Text>
                <Text style={styles.totalValue}>{money(success.subtotal)}</Text>
              </View>
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>
                  {success.delivery_type === 'takeaway'
                    ? 'Retiro en el local'
                    : 'Envío'}
                </Text>
                <Text style={styles.totalValue}>
                  {success.delivery_type === 'takeaway'
                    ? 'Sin costo'
                    : money(success.delivery_fee)}
                </Text>
              </View>
              <View style={[styles.totalRow, styles.totalRowStrong]}>
                <Text style={styles.totalLabelStrong}>Total</Text>
                <Text style={styles.totalValueStrong}>{money(success.total)}</Text>
              </View>
            </View>

            <Pressable
              accessibilityRole="button"
              onPress={() => setSuccess(null)}
              style={styles.successPrimary}
            >
              <Text style={styles.successPrimaryText}>Seguir comprando</Text>
            </Pressable>
            <Pressable
              accessibilityRole="button"
              onPress={() => router.replace('/comercios')}
              style={styles.successSecondary}
            >
              <Text style={styles.successSecondaryText}>Volver a comercios</Text>
            </Pressable>
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  const subtitle = [provider.zone, hoursLabel(provider.hours)]
    .filter(Boolean)
    .join(' · ');

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Volver a comercios"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>←</Text>
        </Pressable>

        <View style={styles.headerInfo}>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {provider.business_name}
          </Text>
          <Text style={styles.headerSubtitle} numberOfLines={1}>
            {subtitle || 'Catálogo'}
          </Text>
        </View>

        {units > 0 ? (
          <View style={styles.cartBadge}>
            <Text style={styles.cartBadgeIcon}>🛒</Text>
            <View style={styles.cartBadgeCount}>
              <Text style={styles.cartBadgeCountText}>{units}</Text>
            </View>
            <Text style={styles.cartBadgeTotal}>{money(subtotal)}</Text>
          </View>
        ) : null}
      </View>

      <ScrollView
        contentContainerStyle={styles.body}
        keyboardShouldPersistTaps="handled"
      >
        {provider.promo ? (
          <View style={styles.promo}>
            <Text style={styles.promoText}>{provider.promo}</Text>
          </View>
        ) : null}

        {categories.length > 1 ? (
          <View style={styles.categoryField}>
            <ComboField<number>
              label="Categoría"
              value={categoryIndex}
              options={categoryOptions}
              onChange={(next) => setCategoryIndex(next)}
              allowEmpty
              emptyLabel="Todas las categorías"
              onClear={() => setCategoryIndex(null)}
              title="Filtrar por categoría"
            />
          </View>
        ) : null}

        {notice ? (
          <Text
            style={[
              styles.notice,
              notice.error ? styles.noticeError : styles.noticeOk,
            ]}
          >
            {notice.text}
          </Text>
        ) : null}

        {visibleCategories.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyText}>
              Este comercio todavía no tiene productos disponibles.
            </Text>
          </View>
        ) : (
          visibleCategories.map((category) => (
            <View key={category.id} style={styles.categoryBlock}>
              <Text style={styles.categoryTitle}>{category.name}</Text>
              {category.products.map((product) => (
                <ProductCard
                  key={product.id}
                  product={product}
                  maxQuantity={Math.max(maxQuantity, 1)}
                  onAdd={addToCart}
                />
              ))}
            </View>
          ))
        )}

        <View style={styles.cartCard}>
          <View style={styles.cartHead}>
            <Text style={styles.cartTitle}>Tu carrito</Text>
            <Text style={styles.cartCount}>
              {units} {units === 1 ? 'unidad' : 'unidades'} · {cart.length}{' '}
              {cart.length === 1 ? 'producto' : 'productos'}
            </Text>
          </View>

          {cart.length === 0 ? (
            <Text style={styles.cartEmpty}>
              🛒 Tu carrito está vacío. Agregá productos para continuar.
            </Text>
          ) : (
            <>
              {cart.map((line) => (
                <View key={line.key} style={styles.cartLine}>
                  <View style={styles.cartLineTop}>
                    <Text style={styles.cartLineName}>
                      <Text style={styles.cartLineQtyStrong}>
                        {line.quantity}×{' '}
                      </Text>
                      {line.product_name}
                      {line.variant_name ? (
                        <Text style={styles.cartLineVariant}>
                          {' '}
                          ({line.variant_name})
                        </Text>
                      ) : null}
                    </Text>
                    <Text style={styles.cartLineTotal}>
                      {money((line.unit_price + line.options_extra) * line.quantity)}
                    </Text>
                  </View>

                  {line.option_labels.length > 0 ? (
                    <Text style={styles.cartLineOptions}>
                      {line.option_labels.join(', ')}
                    </Text>
                  ) : null}

                  <View style={styles.cartLineBottom}>
                    <View style={styles.lineStepper}>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Quitar una unidad"
                        onPress={() => changeLineQty(line.key, -1)}
                        style={styles.lineStepperButton}
                      >
                        <Text style={styles.lineStepperText}>−</Text>
                      </Pressable>
                      <Text style={styles.lineStepperValue}>{line.quantity}</Text>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Agregar una unidad"
                        onPress={() => changeLineQty(line.key, 1)}
                        style={styles.lineStepperButton}
                      >
                        <Text style={styles.lineStepperText}>+</Text>
                      </Pressable>
                    </View>

                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel={`Quitar ${line.product_name} del carrito`}
                      onPress={() => removeLine(line.key)}
                      style={styles.removeButton}
                    >
                      <Text style={styles.removeText}>🗑</Text>
                    </Pressable>
                  </View>
                </View>
              ))}

              <View style={styles.totals}>
                <View style={styles.totalRow}>
                  <Text style={styles.totalLabel}>Subtotal</Text>
                  <Text style={styles.totalValue}>{money(subtotal)}</Text>
                </View>
                <View style={styles.totalRow}>
                  <Text style={styles.totalLabel}>
                    {deliveryType === 'takeaway'
                      ? 'Retiro en el local'
                      : 'Envío'}
                  </Text>
                  <Text style={styles.totalValue}>
                    {deliveryType === 'takeaway' ? 'Sin costo' : money(fee)}
                  </Text>
                </View>
                <View style={[styles.totalRow, styles.totalRowStrong]}>
                  <Text style={styles.totalLabelStrong}>Total estimado</Text>
                  <Text style={styles.totalValueStrong}>{money(total)}</Text>
                </View>
              </View>

              <View style={styles.field}>
                <ComboField<string>
                  label="Forma de pago"
                  value={paymentMethod}
                  options={PAYMENT_OPTIONS}
                  onChange={setPaymentMethod}
                />
              </View>

              <View style={styles.field}>
                <Text style={styles.fieldLabel}>Nota para el comercio</Text>
                <TextInput
                  value={notes}
                  onChangeText={setNotes}
                  placeholder="Ej: sin cebolla, timbre roto, etc."
                  placeholderTextColor={colors.muted}
                  multiline
                  numberOfLines={3}
                  maxLength={1000}
                  style={styles.notes}
                />
              </View>
            </>
          )}
        </View>

        {cart.length > 0 ? (
          <>
            <View style={styles.deliverySection}>
              <Text style={styles.sectionTitle}>Método de entrega</Text>

              <View style={styles.deliveryCards}>
                <Pressable
                  accessibilityRole="radio"
                  accessibilityState={{ selected: deliveryType === 'delivery' }}
                  onPress={() => setDeliveryType('delivery')}
                  style={[
                    styles.deliveryCard,
                    deliveryType === 'delivery' && styles.deliveryCardActive,
                  ]}
                >
                  <Text style={styles.deliveryIcon}>🛵</Text>
                  <View style={styles.deliveryInfo}>
                    <Text style={styles.deliveryTitle}>Envío con repartidor</Text>
                    <Text style={styles.deliveryHint}>
                      Costo de envío {money(deliveryFee)}
                    </Text>
                  </View>
                  <View
                    style={[
                      styles.radioDot,
                      deliveryType === 'delivery' && styles.radioDotActive,
                    ]}
                  />
                </Pressable>

                <Pressable
                  accessibilityRole="radio"
                  accessibilityState={{ selected: deliveryType === 'takeaway' }}
                  onPress={() => setDeliveryType('takeaway')}
                  style={[
                    styles.deliveryCard,
                    deliveryType === 'takeaway' && styles.deliveryCardActive,
                  ]}
                >
                  <Text style={styles.deliveryIcon}>🏪</Text>
                  <View style={styles.deliveryInfo}>
                    <Text style={styles.deliveryTitle}>Retiro en el local</Text>
                    <Text style={styles.deliveryHint}>Sin costo adicional</Text>
                  </View>
                  <View
                    style={[
                      styles.radioDot,
                      deliveryType === 'takeaway' && styles.radioDotActive,
                    ]}
                  />
                </Pressable>
              </View>

              {deliveryType === 'delivery' ? (
                <View style={styles.field}>
                  <ComboField<number>
                    label="Dirección de entrega *"
                    value={addressId}
                    options={addressOptions}
                    onChange={setAddressId}
                    placeholder={
                      addressOptions.length
                        ? 'Elegí una dirección'
                        : 'No tenés direcciones cargadas'
                    }
                    disabled={addressOptions.length === 0}
                  />
                  {addressOptions.length === 0 ? (
                    <Text style={styles.addressWarning}>
                      No tenés direcciones cargadas. Cargá una desde Tu Perfil
                      para poder pedir con envío.
                    </Text>
                  ) : null}
                </View>
              ) : (
                <View style={styles.pickupBox}>
                  <Text style={styles.pickupText}>
                    <Text style={styles.pickupStrong}>Retirás en: </Text>
                    {provider.address ||
                      'Consultá la dirección del local con el comercio.'}
                  </Text>
                </View>
              )}
            </View>

            {submitError ? (
              <Text style={styles.submitError}>{submitError}</Text>
            ) : null}

            <Pressable
              accessibilityRole="button"
              disabled={submitting}
              onPress={() => void handleSubmit()}
              style={[styles.confirmButton, submitting && styles.buttonDisabled]}
            >
              {submitting ? (
                <ActivityIndicator color={colors.surface} />
              ) : (
                <Text style={styles.confirmText}>
                  🔒 Confirmar compra · {money(total)}
                </Text>
              )}
            </Pressable>
          </>
        ) : null}

        <View style={styles.bottomSpace} />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  header: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  backText: {
    color: '#374151',
    fontSize: 17,
    fontWeight: '700',
    lineHeight: 19,
  },
  headerInfo: {
    flex: 1,
    minWidth: 0,
  },
  headerTitle: {
    color: NAVY,
    fontSize: 16,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  cartBadge: {
    alignItems: 'center',
    backgroundColor: '#fff1eb',
    borderColor: '#f5c6ad',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 5,
    paddingHorizontal: 9,
    paddingVertical: 5,
  },
  cartBadgeIcon: {
    fontSize: 13,
  },
  cartBadgeCount: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    minWidth: 17,
    paddingHorizontal: 5,
    paddingVertical: 1,
  },
  cartBadgeCountText: {
    color: colors.surface,
    fontSize: 10,
    fontWeight: '800',
  },
  cartBadgeTotal: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: '800',
  },
  body: {
    padding: spacing.lg,
  },
  promo: {
    backgroundColor: '#ecfdf5',
    borderRadius: radius.sm,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  promoText: {
    color: '#047857',
    fontSize: 12,
  },
  categoryField: {
    marginBottom: spacing.sm,
  },
  notice: {
    borderRadius: radius.sm,
    borderWidth: 1,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  noticeOk: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
    color: '#047857',
  },
  noticeError: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
    color: '#b91c1c',
  },
  emptyCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.xl,
    paddingVertical: 40,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    textAlign: 'center',
  },
  categoryBlock: {
    marginBottom: spacing.lg,
  },
  categoryTitle: {
    color: '#9a3412',
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.4,
    marginBottom: spacing.sm,
    textTransform: 'uppercase',
  },
  productCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    marginBottom: 10,
    padding: spacing.md,
  },
  productHead: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  productPhoto: {
    alignItems: 'center',
    backgroundColor: '#eef2f6',
    borderRadius: 6,
    height: 78,
    justifyContent: 'center',
    overflow: 'hidden',
    width: 78,
  },
  productImage: {
    height: '100%',
    width: '100%',
  },
  productInitial: {
    color: '#94a3b8',
    fontSize: 26,
    fontWeight: '800',
  },
  productInfo: {
    flex: 1,
    minWidth: 0,
  },
  productTitleRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  productName: {
    color: '#0f172a',
    flex: 1,
    fontSize: 14,
    fontWeight: '700',
  },
  productPrice: {
    color: '#0f172a',
    fontSize: 14,
    fontWeight: '800',
  },
  productDescription: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 3,
  },
  variantField: {
    marginTop: spacing.sm,
  },
  singleVariant: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 6,
  },
  optionGroup: {
    marginTop: spacing.md,
  },
  optionTitle: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
  },
  optionHint: {
    color: '#94a3b8',
    fontWeight: '500',
  },
  optionList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 6,
  },
  optionChip: {
    backgroundColor: '#f8fafc',
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 9,
    paddingVertical: 6,
  },
  optionChipActive: {
    backgroundColor: '#fff1eb',
    borderColor: ACCENT,
  },
  optionChipText: {
    color: '#334155',
    fontSize: 12,
  },
  optionChipTextActive: {
    color: '#9a3412',
    fontWeight: '600',
  },
  optionExtra: {
    color: ACCENT,
    fontSize: 11,
    fontWeight: '700',
  },
  productMessage: {
    color: '#b91c1c',
    fontSize: 11,
    marginTop: 7,
  },
  productFooter: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  qtyControls: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 6,
  },
  qtyDisabled: {
    opacity: 0.5,
  },
  qtyButton: {
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    height: 30,
    justifyContent: 'center',
    width: 30,
  },
  qtyButtonText: {
    color: '#374151',
    fontSize: 15,
    fontWeight: '700',
    lineHeight: 17,
  },
  qtyValue: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '700',
    minWidth: 32,
    textAlign: 'center',
  },
  addButton: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '800',
  },
  outOfStock: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  outOfStockText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '700',
  },
  cartCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginTop: spacing.sm,
    padding: spacing.lg,
  },
  cartHead: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  cartTitle: {
    color: NAVY,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  cartCount: {
    color: colors.muted,
    fontSize: 11,
  },
  cartEmpty: {
    color: colors.muted,
    fontSize: 12,
    paddingVertical: spacing.md,
    textAlign: 'center',
  },
  cartLine: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    borderStyle: 'dashed',
    paddingVertical: spacing.sm,
  },
  cartLineTop: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  cartLineName: {
    color: '#0f172a',
    flex: 1,
    fontSize: 13,
  },
  cartLineQtyStrong: {
    fontWeight: '800',
  },
  cartLineVariant: {
    color: '#94a3b8',
  },
  cartLineTotal: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '700',
  },
  cartLineOptions: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  cartLineBottom: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 6,
  },
  lineStepper: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 4,
  },
  lineStepperButton: {
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    height: 24,
    justifyContent: 'center',
    width: 24,
  },
  lineStepperText: {
    color: '#374151',
    fontSize: 13,
    lineHeight: 15,
  },
  lineStepperValue: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
    minWidth: 20,
    textAlign: 'center',
  },
  removeButton: {
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
  removeText: {
    color: colors.danger,
    fontSize: 14,
  },
  totals: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 3,
  },
  totalRowStrong: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: 5,
    paddingTop: 7,
  },
  totalLabel: {
    color: '#374151',
    fontSize: 13,
  },
  totalValue: {
    color: '#0f172a',
    fontSize: 13,
  },
  totalLabelStrong: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  totalValueStrong: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  field: {
    marginTop: spacing.md,
  },
  fieldLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  notes: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1,
    color: colors.text,
    fontSize: 13,
    minHeight: 68,
    paddingHorizontal: 10,
    paddingVertical: 9,
    textAlignVertical: 'top',
  },
  deliverySection: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginTop: spacing.md,
    padding: spacing.lg,
  },
  sectionTitle: {
    color: NAVY,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.4,
    marginBottom: spacing.sm,
    textTransform: 'uppercase',
  },
  deliveryCards: {
    gap: spacing.sm,
  },
  deliveryCard: {
    alignItems: 'flex-start',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 8,
    borderWidth: 2,
    flexDirection: 'row',
    gap: spacing.sm,
    padding: 11,
  },
  deliveryCardActive: {
    backgroundColor: '#fff7f2',
    borderColor: ACCENT,
  },
  deliveryIcon: {
    fontSize: 18,
    lineHeight: 21,
  },
  deliveryInfo: {
    flex: 1,
    minWidth: 0,
  },
  deliveryTitle: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '800',
  },
  deliveryHint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  radioDot: {
    borderColor: '#cbd5e1',
    borderRadius: radius.pill,
    borderWidth: 2,
    height: 16,
    marginTop: 2,
    width: 16,
  },
  radioDotActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  addressWarning: {
    color: '#b45309',
    fontSize: 12,
    marginTop: 6,
  },
  pickupBox: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: 6,
    borderWidth: 1,
    marginTop: spacing.sm,
    paddingHorizontal: 11,
    paddingVertical: 9,
  },
  pickupText: {
    color: '#475569',
    fontSize: 12,
  },
  pickupStrong: {
    color: '#0f172a',
    fontWeight: '700',
  },
  submitError: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
    borderRadius: 4,
    borderWidth: 1,
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  confirmButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: 4,
    justifyContent: 'center',
    marginTop: spacing.md,
    minHeight: 46,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  buttonDisabled: {
    opacity: 0.65,
  },
  confirmText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '800',
  },
  bottomSpace: {
    height: spacing.xl,
  },
  stateScreen: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  stateTitle: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
    textAlign: 'center',
  },
  stateText: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  stateButton: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  stateButtonText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
  successBody: {
    padding: spacing.lg,
  },
  successCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.xl,
  },
  successIcon: {
    color: '#047857',
    fontSize: 34,
    fontWeight: '800',
    textAlign: 'center',
  },
  successTitle: {
    color: '#047857',
    fontSize: 18,
    fontWeight: '800',
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  successText: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  successStrong: {
    color: NAVY,
    fontWeight: '800',
  },
  successTotals: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: spacing.lg,
    paddingTop: spacing.sm,
  },
  successPrimary: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: 4,
    marginTop: spacing.lg,
    paddingVertical: 12,
  },
  successPrimaryText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '800',
  },
  successSecondary: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    marginTop: spacing.sm,
    paddingVertical: 11,
  },
  successSecondaryText: {
    color: '#374151',
    fontSize: 13,
    fontWeight: '600',
  },
});
