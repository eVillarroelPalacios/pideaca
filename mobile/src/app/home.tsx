import { Link, Redirect, router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import {
  Alert,
  Image,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { useManaged } from '@/auth/ManagedContext';
import { AccountSidebar } from '@/components/AccountSidebar';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { appendPhoto, pickSingleImage } from '@/lib/media';
import { PANEL_META, visiblePanels, type PanelKey } from '@/lib/profilePanels';
import { colors, radius, spacing } from '@/theme';
import type {
  AccountUsersResponse,
  ProfileAddress,
  ProfileResponse,
  ProfileServiceRow,
  ProfileServiceTypesResponse,
  ProfileSubgroupsResponse,
  TrustedDriversResponse,
} from '@/types/api';

const DAYS = [
  { key: 'mon', label: 'Lun' },
  { key: 'tue', label: 'Mar' },
  { key: 'wed', label: 'Mié' },
  { key: 'thu', label: 'Jue' },
  { key: 'fri', label: 'Vie' },
  { key: 'sat', label: 'Sáb' },
  { key: 'sun', label: 'Dom' },
];

type DayHours = { open: boolean; shifts: { from: string; to: string }[] };

function emptyHours(): Record<string, DayHours> {
  const out: Record<string, DayHours> = {};
  for (const d of DAYS) {
    out[d.key] = { open: false, shifts: [] };
  }
  return out;
}

function normalizeHours(raw: ProfileResponse['provider']): Record<string, DayHours> {
  const base = emptyHours();
  const hours = raw?.hours as Record<string, unknown> | null | undefined;
  if (!hours || typeof hours !== 'object') {
    return base;
  }
  for (const d of DAYS) {
    const entry = hours[d.key] as { open?: unknown; shifts?: unknown } | undefined;
    if (!entry || typeof entry !== 'object') {
      continue;
    }
    const open = entry.open === true;
    const shiftsRaw = Array.isArray(entry.shifts) ? entry.shifts : [];
    const shifts = shiftsRaw
      .map((s) => {
        const shift = s as { from?: unknown; to?: unknown };
        return {
          from: typeof shift.from === 'string' ? shift.from : '',
          to: typeof shift.to === 'string' ? shift.to : '',
        };
      })
      .filter((s) => s.from !== '' || s.to !== '');
    base[d.key] = { open, shifts };
  }
  return base;
}

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

export default function HomeScreen() {
  const { user, isLoading, signOut, deleteAccount, refresh } = useAuth();
  const { managed, exit: exitManaged } = useManaged();
  // Modo managed de la web: el Admin opera el Mi Perfil del gestionado.
  const managedId = managed?.id ?? null;
  const [profile, setProfile] = useState<ProfileResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [panel, setPanel] = useState<PanelKey | null>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const params = useLocalSearchParams<{ panel?: string }>();

  // Negocio.
  const [businessName, setBusinessName] = useState('');
  const [businessDescription, setBusinessDescription] = useState('');
  const [businessPhone, setBusinessPhone] = useState('');
  const [businessWhatsapp, setBusinessWhatsapp] = useState('');
  const [businessZone, setBusinessZone] = useState('');
  const [businessPromo, setBusinessPromo] = useState('');
  const [savingBusiness, setSavingBusiness] = useState(false);
  const [businessErrors, setBusinessErrors] = useState<{ business_name?: string }>({});

  // Horarios.
  const [hours, setHours] = useState<Record<string, DayHours>>(emptyHours());
  const [savingHours, setSavingHours] = useState(false);

  // Servicios.
  const [services, setServices] = useState<ProfileServiceRow[]>([]);
  const [serviceModalOpen, setServiceModalOpen] = useState(false);
  const [editingService, setEditingService] = useState<ProfileServiceRow | null>(null);
  const [serviceName, setServiceName] = useState('');
  const [serviceDescription, setServiceDescription] = useState('');
  const [savingService, setSavingService] = useState(false);
  const [serviceError, setServiceError] = useState<string | null>(null);

  // Categorías.
  const [subgroups, setSubgroups] = useState<ProfileSubgroupsResponse | null>(null);
  const [serviceTypes, setServiceTypes] = useState<ProfileServiceTypesResponse | null>(null);
  const [togglingCategory, setTogglingCategory] = useState<number | null>(null);

  // Repartidores.
  const [trusted, setTrusted] = useState<TrustedDriversResponse | null>(null);
  const [trustedSearch, setTrustedSearch] = useState('');
  const [togglingTrusted, setTogglingTrusted] = useState(false);

  // Publicidad.
  const [uploadingImage, setUploadingImage] = useState(false);

  // Dirección.
  const [countries, setCountries] = useState<{ id: number; name: string }[]>([]);
  const [regions, setRegions] = useState<{ id: number; name: string }[]>([]);
  const [countryId, setCountryId] = useState<number | null>(null);
  const [provinceId, setProvinceId] = useState<number | null>(null);
  const [street, setStreet] = useState('');
  const [addressNumber, setAddressNumber] = useState('');
  const [floor, setFloor] = useState('');
  const [postal, setPostal] = useState('');
  const [notes, setNotes] = useState('');
  const [savingAddress, setSavingAddress] = useState(false);
  const [addressError, setAddressError] = useState<string | null>(null);

  // Usuarios.
  const [account, setAccount] = useState<AccountUsersResponse | null>(null);
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [userModalOpen, setUserModalOpen] = useState(false);
  const [editingUserId, setEditingUserId] = useState<number | null>(null);
  const [accountName, setAccountName] = useState('');
  const [accountPassword, setAccountPassword] = useState('');
  const [accountPasswordConfirm, setAccountPasswordConfirm] = useState('');
  const [savingAccountUser, setSavingAccountUser] = useState(false);
  const [accountUserError, setAccountUserError] = useState<string | null>(null);
  const [togglingPage, setTogglingPage] = useState<number | null>(null);

  // Vehículo.
  const [vehicleType, setVehicleType] = useState<string>('');
  const [vehiclePlate, setVehiclePlate] = useState('');
  const [savingVehicle, setSavingVehicle] = useState(false);
  const [vehicleError, setVehicleError] = useState<string | null>(null);

  // Cuenta.
  const [profileName, setProfileName] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);
  const [profileError, setProfileError] = useState<string | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);
  const [passwordError, setPasswordError] = useState<string | null>(null);

  // Mismas secciones que el sidebar de la web, filtradas por rol/permisos
  // del usuario efectivo (el gestionado si hay modo managed).
  const panels = useMemo(
    () => visiblePanels(profile?.flags, managedId),
    [profile, managedId],
  );

  // Panel pedido desde el sidebar de Usuarios (`/home?panel=direccion`).
  // Se usa mientras el parámetro esté presente; al elegir otra sección se
  // limpia el parámetro y manda la selección manual.
  const requestedPanel =
    typeof params.panel === 'string' && params.panel.length > 0
      ? params.panel
      : null;
  const requested = useMemo(
    () =>
      requestedPanel
        ? panels.find((item) => item.key === requestedPanel) ?? null
        : null,
    [panels, requestedPanel],
  );
  const activePanel: PanelKey = requested?.key ?? panel ?? 'cuenta';

  const syncForms = useCallback((data: ProfileResponse) => {
    const provider = data.provider;
    setBusinessName(provider?.business_name ?? '');
    setBusinessDescription(provider?.description ?? '');
    setBusinessPhone(provider?.phone ?? '');
    setBusinessWhatsapp(provider?.whatsapp ?? '');
    setBusinessZone(provider?.zone ?? '');
    setBusinessPromo(provider?.promo ?? '');
    setHours(normalizeHours(provider));
    setServices(provider?.services ?? []);
    setVehicleType(data.user.vehicle_type ?? data.default_vehicle_type ?? '');
    setVehiclePlate(data.user.vehicle_plate ?? '');
    setProfileName(data.user.name ?? '');
    const address: ProfileAddress | null = data.address;
    setStreet(address?.street ?? '');
    setAddressNumber(address?.number ?? '');
    setFloor(address?.floor_apartment ?? '');
    setPostal(address?.postal_code ?? '');
    setNotes(address?.notes ?? '');
    setCountryId(address?.country_id ?? null);
    setProvinceId(address?.province_id ?? null);
  }, []);

  const fetchAll = useCallback(async () => {
    const data = managedId ? await api.adminUserProfile(managedId) : await api.profile();
    setProfile(data);
    syncForms(data);
    setPanel((current) => {
      if (current) {
        return current;
      }
      const initial = data.flags.initial_panel as PanelKey;
      return PANEL_META.some((p) => p.key === initial) ? initial : 'cuenta';
    });

    // Secundarios: cada bloque se intenta y se ignora el 403.
    if (data.flags.can_configure_business) {
      try {
        const res = managedId
          ? await api.adminUserServices(managedId)
          : await api.profileServices();
        setServices(res.services);
      } catch {
        // Sin servicios o sin permiso: se muestra lo del panel principal.
      }
      try {
        setSubgroups(
          managedId ? await api.adminUserSubgroups(managedId) : await api.profileSubgroups(),
        );
      } catch {
        setSubgroups(null);
      }
      if (data.flags.can_see_service_types) {
        try {
          setServiceTypes(
            managedId
              ? await api.adminUserServiceTypes(managedId)
              : await api.profileServiceTypes(),
          );
        } catch {
          setServiceTypes(null);
        }
      } else {
        setServiceTypes(null);
      }
      // Repartidores de confianza no tiene endpoint gestionado en la web:
      // el panel se oculta en modo managed.
      if (data.flags.can_see_trusted_drivers && !managedId) {
        try {
          setTrusted(await api.trustedDrivers());
        } catch {
          setTrusted(null);
        }
      } else {
        setTrusted(null);
      }
    } else {
      setServices([]);
      setSubgroups(null);
      setServiceTypes(null);
      setTrusted(null);
    }

    if (data.flags.can_see_address) {
      try {
        setCountries((await api.profileCountries()).data);
      } catch {
        setCountries([]);
      }
      const cid = data.address?.country_id ?? null;
      if (cid) {
        try {
          setRegions((await api.profileRegions(cid)).data);
        } catch {
          setRegions([]);
        }
      } else {
        setRegions([]);
      }
    } else {
      setCountries([]);
      setRegions([]);
    }

    // El panel de usuarios de Mi Perfil es de la cuenta propia: en modo
    // managed se gestiona desde la pantalla Usuarios.
    if (data.flags.can_manage_users && !managedId) {
      try {
        const users = await api.profileUsers();
        setAccount(users);
        setSelectedUserId((current) => current ?? users.data[0]?.id ?? null);
      } catch {
        setAccount(null);
      }
    } else {
      setAccount(null);
    }

    return data;
  }, [syncForms, managedId]);

  const load = useCallback(
    async (withSpinner: boolean) => {
      if (withSpinner) {
        setLoading(true);
      }
      setError(null);
      try {
        await fetchAll();
      } catch (caught) {
        setError(caught instanceof ApiError ? caught.message : 'No pudimos cargar tu perfil.');
      } finally {
        if (withSpinner) {
          setLoading(false);
        }
      }
    },
    [fetchAll],
  );

  useFocusEffect(
    useCallback(() => {
      let active = true;
      setLoading(true);
      void load(false)
        .catch(() => {
          // load() ya setea el error visible.
        })
        .finally(() => {
          if (active) {
            setLoading(false);
          }
        });
      return () => {
        active = false;
      };
    }, [load]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    setNotice(null);
    try {
      await fetchAll();
      await refresh();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'No pudimos actualizar.');
    } finally {
      setRefreshing(false);
    }
  }

  function showNotice(message: string) {
    setNotice(message);
  }

  // Negocio + horarios comparten el mismo endpoint web.
  async function saveProvider(kind: 'negocio' | 'horarios', successMessage: string) {
    if (!profile?.flags.can_configure_business) {
      return;
    }
    if (kind === 'negocio') {
      const value = businessName.trim();
      if (!value) {
        setBusinessErrors({ business_name: 'El nombre del negocio es obligatorio.' });
        return;
      }
      setBusinessErrors({});
      setSavingBusiness(true);
    } else {
      setSavingHours(true);
    }
    setNotice(null);
    try {
      const payload = {
        business_name: businessName.trim() || profile.provider?.business_name || 'Mi negocio',
        description: businessDescription.trim() ? businessDescription.trim() : null,
        phone: businessPhone.trim() ? businessPhone.trim() : null,
        whatsapp: businessWhatsapp.trim() ? businessWhatsapp.trim() : null,
        zone: businessZone.trim() ? businessZone.trim() : null,
        promo: businessPromo.trim() ? businessPromo.trim() : null,
        hours,
      };
      const response = managedId
        ? await api.updateAdminUserProvider(managedId, payload)
        : await api.updateProvider(payload);
      setProfile((current) => (current ? { ...current, provider: response.provider } : current));
      setServices(response.provider.services ?? []);
      showNotice(successMessage);
    } catch (caught) {
      const message = caught instanceof ApiError ? caught.message : 'No pudimos guardar.';
      if (kind === 'negocio') {
        const fieldError = caught instanceof ApiError ? caught.errors?.business_name?.[0] : undefined;
        setBusinessErrors({ business_name: fieldError ?? message });
      } else {
        setError(message);
      }
    } finally {
      setSavingBusiness(false);
      setSavingHours(false);
    }
  }

  function copyMonFri() {
    setHours((current) => {
      const monday = current.mon ?? { open: false, shifts: [] };
      const next = { ...current };
      for (const key of ['tue', 'wed', 'thu', 'fri']) {
        next[key] = { open: monday.open, shifts: monday.shifts.map((s) => ({ ...s })) };
      }
      return next;
    });
  }

  function toggleDay(day: string) {
    setHours((current) => ({
      ...current,
      [day]: { ...current[day], open: !current[day].open },
    }));
  }

  function updateShift(day: string, index: number, field: 'from' | 'to', value: string) {
    setHours((current) => {
      const shifts = current[day].shifts.map((s, i) => (i === index ? { ...s, [field]: value } : s));
      return { ...current, [day]: { ...current[day], shifts } };
    });
  }

  function addShift(day: string) {
    setHours((current) => ({
      ...current,
      [day]: { ...current[day], shifts: [...current[day].shifts, { from: '', to: '' }] },
    }));
  }

  function removeShift(day: string, index: number) {
    setHours((current) => ({
      ...current,
      [day]: { ...current[day], shifts: current[day].shifts.filter((_, i) => i !== index) },
    }));
  }

  // Servicios.
  function openCreateService() {
    setEditingService(null);
    setServiceName('');
    setServiceDescription('');
    setServiceError(null);
    setServiceModalOpen(true);
  }

  function openEditService(item: ProfileServiceRow) {
    setEditingService(item);
    setServiceName(item.name);
    setServiceDescription(item.description ?? '');
    setServiceError(null);
    setServiceModalOpen(true);
  }

  async function handleSaveService() {
    const name = serviceName.trim();
    if (!name) {
      setServiceError('Ingresá el nombre del servicio.');
      return;
    }
    setSavingService(true);
    setServiceError(null);
    try {
      const payload = { name, description: serviceDescription.trim() };
      const response = managedId
        ? editingService
          ? await api.updateAdminUserService(managedId, editingService.id, payload)
          : await api.createAdminUserService(managedId, payload)
        : editingService
          ? await api.updateProfileService(editingService.id, payload)
          : await api.createProfileService(payload);
      setServiceModalOpen(false);
      setEditingService(null);
      setServiceName('');
      setServiceDescription('');
      showNotice(response.message);
      const res = managedId
        ? await api.adminUserServices(managedId)
        : await api.profileServices();
      setServices(res.services);
    } catch (caught) {
      setServiceError(caught instanceof ApiError ? caught.message : 'No pudimos guardar el servicio.');
    } finally {
      setSavingService(false);
    }
  }

  function confirmDeleteService(item: ProfileServiceRow) {
    Alert.alert('Eliminar servicio', `¿Eliminar "${item.name}"?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              if (managedId) {
                await api.deleteAdminUserService(managedId, item.id);
              } else {
                await api.deleteProfileService(item.id);
              }
              showNotice('Servicio eliminado correctamente.');
              const res = managedId
                ? await api.adminUserServices(managedId)
                : await api.profileServices();
              setServices(res.services);
            } catch (caught) {
              Alert.alert(
                'No pudimos eliminar',
                caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
              );
            }
          })(),
      },
    ]);
  }

  async function moveService(item: ProfileServiceRow, direction: -1 | 1) {
    const order = services.map((s) => s.id);
    const index = order.indexOf(item.id);
    const target = index + direction;
    if (index < 0 || target < 0 || target >= order.length) {
      return;
    }
    const next = [...order];
    [next[index], next[target]] = [next[target], next[index]];
    try {
      const res = await api.reorderProfileServices(next);
      setServices(res.services);
    } catch (caught) {
      Alert.alert('No pudimos reordenar', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    }
  }

  // Categorías.
  async function toggleSubgroup(id: number) {
    setTogglingCategory(id);
    try {
      const res = managedId
        ? await api.toggleAdminUserSubgroup(managedId, id)
        : await api.toggleProfileSubgroup(id);
      setSubgroups((current) => (current ? { ...current, selected: res.selected } : current));
      showNotice(res.message);
    } catch (caught) {
      Alert.alert('No pudimos actualizar', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    } finally {
      setTogglingCategory(null);
    }
  }

  async function toggleServiceType(id: number) {
    setTogglingCategory(id);
    try {
      const res = managedId
        ? await api.toggleAdminUserServiceType(managedId, id)
        : await api.toggleProfileServiceType(id);
      setServiceTypes((current) => (current ? { ...current, selected: res.selected } : current));
      showNotice(res.message);
    } catch (caught) {
      Alert.alert('No pudimos actualizar', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    } finally {
      setTogglingCategory(null);
    }
  }

  // Repartidores.
  async function handleTrustedToggle(value: boolean) {
    setTogglingTrusted(true);
    try {
      const res = await api.updateTrustedDriversSettings(value);
      setTrusted((current) =>
        current ? { ...current, provider: { ...current.provider, ...res.provider } } : current,
      );
      showNotice(res.message);
    } catch (caught) {
      Alert.alert(
        'No pudimos cambiar la modalidad',
        caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
      );
    } finally {
      setTogglingTrusted(false);
    }
  }

  async function handleAddDriver(userId: number, name: string) {
    try {
      const res = await api.addTrustedDriver(userId);
      setTrusted(res);
      showNotice(`${name} se agregó a tus repartidores de confianza.`);
    } catch (caught) {
      Alert.alert('No pudimos agregar', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    }
  }

  function confirmRemoveDriver(id: number, name: string | null) {
    Alert.alert('Quitar repartidor', `¿Quitar a ${name ?? 'este repartidor'} de tu lista?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Quitar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              const res = await api.removeTrustedDriver(id);
              setTrusted(res);
              showNotice('Repartidor quitado correctamente.');
            } catch (caught) {
              if (caught instanceof ApiError && caught.code === 'last_active_driver') {
                Alert.alert('Último repartidor activo', caught.message, [
                  { text: 'Volver', style: 'cancel' },
                  {
                    text: 'Desactivar modalidad',
                    style: 'destructive',
                    onPress: () =>
                      void (async () => {
                        try {
                          const res = await api.removeTrustedDriver(id, true);
                          setTrusted(res);
                          showNotice('Modalidad desactivada y repartidor quitado.');
                        } catch (inner) {
                          Alert.alert(
                            'No pudimos quitar',
                            inner instanceof ApiError ? inner.message : 'Intentá de nuevo.',
                          );
                        }
                      })(),
                  },
                ]);
              } else {
                Alert.alert(
                  'No pudimos quitar',
                  caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
                );
              }
            }
          })(),
      },
    ]);
  }

  // Publicidad.
  async function handleUploadImage() {
    const photo = await pickSingleImage();
    if (!photo) {
      return;
    }
    setUploadingImage(true);
    try {
      const form = new FormData();
      appendPhoto(form, 'image', photo);
      const res = managedId
        ? await api.uploadAdminUserImage(managedId, form)
        : await api.uploadProfileImage(form);
      setProfile((current) => {
        if (!current || !current.provider) {
          return current;
        }
        return {
          ...current,
          provider: { ...current.provider, images: [res.image] },
        };
      });
      showNotice(res.message);
    } catch (caught) {
      Alert.alert('No pudimos subir', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    } finally {
      setUploadingImage(false);
    }
  }

  function confirmDeleteImage(id: number) {
    Alert.alert('Eliminar imagen', '¿Eliminar tu imagen publicitaria?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              const res = managedId
                ? await api.deleteAdminUserImage(managedId, id)
                : await api.deleteProfileImage(id);
              setProfile((current) => {
                if (!current || !current.provider) {
                  return current;
                }
                return {
                  ...current,
                  provider: {
                    ...current.provider,
                    images: current.provider.images.filter((img) => img.id !== id),
                  },
                };
              });
              showNotice(res.message);
            } catch (caught) {
              Alert.alert(
                'No pudimos eliminar',
                caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
              );
            }
          })(),
      },
    ]);
  }

  // Dirección.
  async function handleCountryChange(id: number) {
    setCountryId(id);
    setProvinceId(null);
    try {
      setRegions((await api.profileRegions(id)).data);
    } catch {
      setRegions([]);
    }
  }

  async function handleSaveAddress() {
    if (!countryId) {
      setAddressError('Seleccioná un país.');
      return;
    }
    setSavingAddress(true);
    setAddressError(null);
    try {
      if (managedId) {
        // En modo managed la direccion se guarda por los endpoints de
        // administracion del usuario, igual que en la web.
        const addressPayload = {
          country_id: countryId,
          province_id: provinceId,
          street: street.trim() ? street.trim() : null,
          number: addressNumber.trim() ? addressNumber.trim() : null,
        };
        const res = profile?.address?.id
          ? await api.updateAdminUserAddress(managedId, profile.address.id, addressPayload)
          : await api.createAdminUserAddress(managedId, addressPayload);
        const data = await api.adminUserProfile(managedId);
        setProfile(data);
        syncForms(data);
        showNotice(res.message);
        return;
      }
      const res = await api.saveAddress({
        country_id: countryId,
        province_id: provinceId,
        street: street.trim() ? street.trim() : null,
        number: addressNumber.trim() ? addressNumber.trim() : null,
        floor_apartment: floor.trim() ? floor.trim() : null,
        postal_code: postal.trim() ? postal.trim() : null,
        notes: notes.trim() ? notes.trim() : null,
      });
      setProfile((current) =>
        current ? { ...current, address: res.address, address_shared: res.shared } : current,
      );
      showNotice(res.message ?? 'Dirección guardada correctamente.');
    } catch (caught) {
      setAddressError(caught instanceof ApiError ? caught.message : 'No pudimos guardar la dirección.');
    } finally {
      setSavingAddress(false);
    }
  }

  // Usuarios.
  const selectedAccountUser = useMemo(
    () => account?.data.find((u) => u.id === selectedUserId) ?? null,
    [account, selectedUserId],
  );

  function openCreateAccountUser() {
    setEditingUserId(null);
    setAccountName('');
    setAccountPassword('');
    setAccountPasswordConfirm('');
    setAccountUserError(null);
    setUserModalOpen(true);
  }

  function openEditAccountUser(id: number, name: string) {
    setEditingUserId(id);
    setAccountName(name);
    setAccountPassword('');
    setAccountPasswordConfirm('');
    setAccountUserError(null);
    setUserModalOpen(true);
  }

  async function handleSaveAccountUser() {
    const name = accountName.trim();
    if (!name) {
      setAccountUserError('Ingresá el nombre del usuario.');
      return;
    }
    if (!editingUserId && (accountPassword.length < 8 || accountPassword !== accountPasswordConfirm)) {
      setAccountUserError(
        accountPassword.length < 8
          ? 'La contraseña debe tener al menos 8 caracteres.'
          : 'Las contraseñas no coinciden.',
      );
      return;
    }
    if (editingUserId && accountPassword && accountPassword !== accountPasswordConfirm) {
      setAccountUserError('Las contraseñas no coinciden.');
      return;
    }
    setSavingAccountUser(true);
    setAccountUserError(null);
    try {
      const response = editingUserId
        ? await api.updateProfileUser(editingUserId, {
            name,
            ...(accountPassword ? { password: accountPassword, password_confirmation: accountPasswordConfirm } : {}),
          })
        : await api.createProfileUser({
            name,
            password: accountPassword,
            password_confirmation: accountPasswordConfirm,
          });
      setUserModalOpen(false);
      setEditingUserId(null);
      setAccountName('');
      setAccountPassword('');
      setAccountPasswordConfirm('');
      showNotice(response.message);
      const users = await api.profileUsers();
      setAccount(users);
      setSelectedUserId(response.user.id);
    } catch (caught) {
      setAccountUserError(caught instanceof ApiError ? caught.message : 'No pudimos guardar el usuario.');
    } finally {
      setSavingAccountUser(false);
    }
  }

  function confirmDeleteAccountUser(id: number, name: string) {
    Alert.alert('Inactivar usuario', `¿Inactivar a ${name}? Se le quitan sus páginas.`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Inactivar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              const res = await api.deleteProfileUser(id);
              showNotice(res.message);
              const users = await api.profileUsers();
              setAccount(users);
              setSelectedUserId(users.data[0]?.id ?? null);
            } catch (caught) {
              Alert.alert(
                'No pudimos inactivar',
                caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
              );
            }
          })(),
      },
    ]);
  }

  async function toggleUserPage(userId: number, pageId: number, hasPage: boolean) {
    setTogglingPage(pageId);
    try {
      const res = hasPage
        ? await api.detachProfileUserPage(userId, pageId)
        : await api.attachProfileUserPage(userId, pageId);
      showNotice(res.message);
      const users = await api.profileUsers();
      setAccount(users);
    } catch (caught) {
      Alert.alert('No pudimos actualizar', caught instanceof ApiError ? caught.message : 'Intentá de nuevo.');
    } finally {
      setTogglingPage(null);
    }
  }

  // Vehículo.
  async function handleSaveVehicle() {
    if (!vehicleType) {
      setVehicleError('Seleccioná un tipo de vehículo.');
      return;
    }
    setSavingVehicle(true);
    setVehicleError(null);
    try {
      const res = await api.updateVehicle(vehicleType, vehiclePlate.trim());
      setVehicleType(res.vehicle_type ?? vehicleType);
      setVehiclePlate(res.vehicle_plate ?? '');
      showNotice(res.message);
      await refresh();
    } catch (caught) {
      setVehicleError(caught instanceof ApiError ? caught.message : 'No pudimos guardar el vehículo.');
    } finally {
      setSavingVehicle(false);
    }
  }

  // Cuenta.
  async function handleSaveProfile() {
    const value = profileName.trim();
    if (!value) {
      setProfileError('Ingresá tu nombre.');
      return;
    }
    setSavingProfile(true);
    setProfileError(null);
    try {
      const res = managedId
        ? await api.updateAdminUser(managedId, {
            name: value,
            email: profile?.user.email ?? '',
          })
        : await api.updateProfile(value);
      setProfile((current) => (current ? { ...current, user: { ...current.user, name: res.user.name } } : current));
      showNotice(res.message);
      if (!managedId) {
        await refresh();
      }
    } catch (caught) {
      setProfileError(caught instanceof ApiError ? caught.message : 'No pudimos guardar.');
    } finally {
      setSavingProfile(false);
    }
  }

  async function handleSavePassword() {
    if (newPassword.length < 8) {
      setPasswordError('La contraseña debe tener al menos 8 caracteres.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordError('Las contraseñas no coinciden.');
      return;
    }
    setSavingPassword(true);
    setPasswordError(null);
    try {
      const res = managedId
        ? await api.updateAdminUser(managedId, {
            name: profile?.user.name ?? '',
            email: profile?.user.email ?? '',
            password: newPassword,
            password_confirmation: confirmPassword,
          })
        : await api.updateProfilePassword({
            password: newPassword,
            password_confirmation: confirmPassword,
          });
      setNewPassword('');
      setConfirmPassword('');
      showNotice(res.message);
    } catch (caught) {
      setPasswordError(caught instanceof ApiError ? caught.message : 'No pudimos cambiar la contraseña.');
    } finally {
      setSavingPassword(false);
    }
  }

  function confirmDeleteAccount() {
    Alert.alert(
      'Eliminar cuenta',
      'Tu cuenta será desactivada y se cerrará la sesión. ¿Querés continuar?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Desactivar',
          style: 'destructive',
          onPress: () =>
            void (async () => {
              setDeleting(true);
              try {
                await deleteAccount();
              } catch (caught) {
                Alert.alert(
                  'No pudimos desactivar la cuenta',
                  caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
                );
              } finally {
                setDeleting(false);
              }
            })(),
        },
      ],
    );
  }

  const candidates = useMemo(() => {
    if (!trusted) {
      return [];
    }
    const term = trustedSearch.trim().toLowerCase();
    if (!term) {
      return trusted.candidates;
    }
    return trusted.candidates.filter(
      (c) =>
        c.name.toLowerCase().includes(term) ||
        c.email.toLowerCase().includes(term) ||
        String(c.id).includes(term),
    );
  }, [trusted, trustedSearch]);

  if (isLoading || (loading && !profile)) {
    return <LoadingScreen message="Cargando tu perfil…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  const flags = profile?.flags ?? null;
  const provider = profile?.provider ?? null;
  const sharedAddress = profile?.address_shared ?? false;
  const readOnlyAddress = sharedAddress || !flags?.is_principal;
  const canConfigureBusiness = flags?.can_configure_business ?? false;

  function handlePanelSelect(key: PanelKey) {
    // Igual que `showProfilePanel` de la web: el Admin gestiona usuarios en
    // la pantalla dedicada; el resto ve el panel de usuarios de Mi Perfil.
    if (key === 'usuarios' && flags?.is_admin) {
      router.push('/users');
      return;
    }

    setPanel(key);
    // Si veníamos de `/home?panel=...`, la selección manual manda desde ahora.
    if (requestedPanel) {
      router.setParams({ panel: '' });
    }
    setNotice(null);
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Link href="/menu" asChild>
          <Pressable accessibilityRole="button" accessibilityLabel="Abrir menú" style={styles.menuButton}>
            <Text style={styles.menuText}>☰</Text>
          </Pressable>
        </Link>
        <View style={styles.brandWrap}>
          <Image
            source={require('../../assets/logo.png')}
            style={styles.brandLogo}
            resizeMode="contain"
            accessibilityLabel="PideAca"
          />
          <Text style={styles.brand}>Mi Perfil</Text>
        </View>
        <Pressable accessibilityRole="button" onPress={() => void signOut()} style={styles.logout}>
          <Text style={styles.logoutText}>Salir</Text>
        </Pressable>
      </View>

      {managed ? (
        <View style={styles.managedBanner}>
          <Text style={styles.managedText}>
            Editando perfil de {managed.name}
            {managed.role ? ` · ${managed.role}` : ''}
          </Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Salir del perfil gestionado"
            onPress={() => void exitManaged()}
            style={styles.managedExit}
          >
            <Text style={styles.managedExitText}>Salir</Text>
          </Pressable>
        </View>
      ) : null}

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => void handleRefresh()}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Abrir secciones de la cuenta"
          onPress={() => setSidebarOpen(true)}
          style={styles.profileCard}
        >
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initials(profile?.user.name ?? user.name)}</Text>
          </View>
          <View style={styles.profileInfo}>
            <Text style={styles.name}>{profile?.user.name ?? user.name}</Text>
            <Text style={styles.email}>{profile?.user.email ?? user.email}</Text>
            <View style={styles.badges}>
              <View style={styles.badge}>
                <Text style={styles.badgeText}>{profile?.user.role ?? user.role ?? 'Sin rol'}</Text>
              </View>
              {profile?.user.status ? (
                <View style={[styles.badge, styles.statusBadge]}>
                  <Text style={[styles.badgeText, styles.statusText]}>{profile.user.status}</Text>
                </View>
              ) : null}
              {flags?.is_principal ? (
                <View style={[styles.badge, styles.principalBadge]}>
                  <Text style={[styles.badgeText, styles.principalText]}>Titular</Text>
                </View>
              ) : (
                <View style={[styles.badge, styles.statusBadge]}>
                  <Text style={[styles.badgeText, styles.statusText]}>Secundario</Text>
                </View>
              )}
            </View>
          </View>
          <View style={styles.profileSectionHint}>
            <Text style={styles.profileSectionHintText}>Secciones</Text>
            <Text style={styles.profileChevron}>›</Text>
          </View>
        </Pressable>

        {activePanel === 'negocio' && canConfigureBusiness ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🏪 Información del negocio</Text>
            <Text style={styles.cardSub}>Estos datos se muestran en las tarjetas publicitarias.</Text>
            <TextField
              label="Nombre del negocio"
              placeholder="Ej: Pizzería Los Hermanos"
              value={businessName}
              onChangeText={setBusinessName}
              error={businessErrors.business_name}
            />
            <TextField
              label="Descripción"
              placeholder="Breve descripción de tu negocio…"
              value={businessDescription}
              onChangeText={setBusinessDescription}
              multiline
              numberOfLines={3}
            />
            <Text style={styles.cardTitle}>Contacto y promoción</Text>
            <TextField label="Teléfono" placeholder="+54 11 …" value={businessPhone} onChangeText={setBusinessPhone} />
            <TextField label="WhatsApp" placeholder="5411…" value={businessWhatsapp} onChangeText={setBusinessWhatsapp} />
            <TextField label="Zona de cobertura" placeholder="Zona Norte, Centro…" value={businessZone} onChangeText={setBusinessZone} />
            <TextField label="Promoción" placeholder="2x1 en empanadas…" value={businessPromo} onChangeText={setBusinessPromo} />
            <PrimaryButton title="Guardar negocio" onPress={() => void saveProvider('negocio', 'Perfil de proveedor actualizado.')} loading={savingBusiness} />
          </View>
        ) : null}

        {activePanel === 'horarios' && canConfigureBusiness ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🕒 Horarios de atención</Text>
            <PrimaryButton title="Aplicar Lunes a Viernes" variant="ghost" onPress={copyMonFri} />
            <View style={styles.gap} />
            {DAYS.map((day) => {
              const entry = hours[day.key];
              return (
                <View key={day.key} style={styles.dayRow}>
                  <View style={styles.dayHeader}>
                    <Text style={styles.dayLabel}>{day.label}</Text>
                    <Switch value={entry.open} onValueChange={() => toggleDay(day.key)} />
                  </View>
                  {entry.open ? (
                    <View>
                      {entry.shifts.map((shift, index) => (
                        <View key={`${day.key}-${index}`} style={styles.shiftRow}>
                          <TextField label="Desde" placeholder="09:00" value={shift.from} onChangeText={(v) => updateShift(day.key, index, 'from', v)} />
                          <TextField label="Hasta" placeholder="18:00" value={shift.to} onChangeText={(v) => updateShift(day.key, index, 'to', v)} />
                          <Pressable accessibilityRole="button" onPress={() => removeShift(day.key, index)} style={styles.miniButton}>
                            <Text style={styles.miniButtonText}>Quitar</Text>
                          </Pressable>
                        </View>
                      ))}
                      <Pressable accessibilityRole="button" onPress={() => addShift(day.key)} style={styles.addShift}>
                        <Text style={styles.addShiftText}>＋ Agregar turno</Text>
                      </Pressable>
                    </View>
                  ) : null}
                </View>
              );
            })}
            <PrimaryButton title="Guardar horarios" onPress={() => void saveProvider('horarios', 'Horarios guardados.')} loading={savingHours} />
          </View>
        ) : null}

        {activePanel === 'servicios' && canConfigureBusiness ? (
          <View style={styles.card}>
            <View style={styles.rowHeader}>
              <Text style={styles.cardTitle}>🧰 Mis Servicios</Text>
              <Pressable accessibilityRole="button" onPress={openCreateService} style={styles.addButton}>
                <Text style={styles.addText}>＋ Nuevo</Text>
              </Pressable>
            </View>
            {services.length === 0 ? (
              <Text style={styles.emptyText}>Todavía no agregaste servicios.</Text>
            ) : (
              services.map((item, index) => (
                <View key={item.id} style={styles.serviceCard}>
                  <Text style={styles.serviceName}>
                    {index + 1}. {item.name}
                  </Text>
                  {item.description ? <Text style={styles.serviceDescription}>{item.description}</Text> : null}
                  <View style={styles.serviceActions}>
                    <Pressable accessibilityRole="button" onPress={() => openEditService(item)} style={styles.actionButton}>
                      <Text style={styles.actionText}>Editar</Text>
                    </Pressable>
                    {!managedId ? (
                      <Pressable accessibilityRole="button" onPress={() => moveService(item, -1)} style={styles.actionButton}>
                        <Text style={styles.actionText}>↑</Text>
                      </Pressable>
                    ) : null}
                    {!managedId ? (
                      <Pressable accessibilityRole="button" onPress={() => moveService(item, 1)} style={styles.actionButton}>
                        <Text style={styles.actionText}>↓</Text>
                      </Pressable>
                    ) : null}
                    <Pressable accessibilityRole="button" onPress={() => confirmDeleteService(item)} style={[styles.actionButton, styles.deleteButton]}>
                      <Text style={[styles.actionText, styles.deleteText]}>Eliminar</Text>
                    </Pressable>
                  </View>
                </View>
              ))
            )}
          </View>
        ) : null}

        {activePanel === 'repartidores' && flags?.can_see_trusted_drivers && !managedId ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🛵 Repartidores de confianza</Text>
            <Text style={styles.cardSub}>
              Con la modalidad activa, los pedidos quedan reservados a tu lista.
            </Text>
            <View style={styles.switchRow}>
              <Text style={styles.switchLabel}>Solo repartidores de confianza</Text>
              <Switch
                value={trusted?.provider.trusted_drivers_enabled ?? false}
                disabled={togglingTrusted}
                onValueChange={(value) => void handleTrustedToggle(value)}
              />
            </View>
            <Text style={styles.sectionLabel}>
              En la lista: {trusted?.drivers.length ?? 0} · Modo:{' '}
              {trusted?.provider.trusted_drivers_enabled ? 'cerrado' : 'pool general'}
            </Text>
            {(trusted?.drivers ?? []).map((driver) => (
              <View key={driver.id} style={styles.serviceCard}>
                <Text style={styles.serviceName}>{driver.name ?? `Repartidor #${driver.user_id}`}</Text>
                <Text style={styles.serviceDescription}>
                  {[driver.vehicle_label, driver.vehicle_plate, driver.email].filter(Boolean).join(' · ')}
                </Text>
                <View style={styles.serviceActions}>
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => confirmRemoveDriver(driver.id, driver.name)}
                    style={[styles.actionButton, styles.deleteButton]}
                  >
                    <Text style={[styles.actionText, styles.deleteText]}>Quitar</Text>
                  </Pressable>
                </View>
              </View>
            ))}
            {(trusted?.drivers ?? []).length === 0 ? (
              <Text style={styles.emptyText}>Todavía no hay repartidores en tu lista.</Text>
            ) : null}
            <Text style={styles.sectionLabel}>Agregar repartidor</Text>
            <TextField label="Buscar" placeholder="Nombre, correo o ID…" value={trustedSearch} onChangeText={setTrustedSearch} />
            {candidates.slice(0, 8).map((candidate) => (
              <View key={candidate.id} style={styles.serviceCard}>
                <Text style={styles.serviceName}>{candidate.name}</Text>
                <Text style={styles.serviceDescription}>{candidate.email}</Text>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => void handleAddDriver(candidate.id, candidate.name)}
                  style={styles.actionButton}
                >
                  <Text style={styles.actionText}>Agregar</Text>
                </Pressable>
              </View>
            ))}
            {candidates.length === 0 ? <Text style={styles.emptyText}>Sin candidatos disponibles.</Text> : null}
          </View>
        ) : null}

        {activePanel === 'imagenes' && canConfigureBusiness ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🖼️ Publicidad</Text>
            <Text style={styles.cardSub}>Una sola imagen por proveedor (JPG o PNG, hasta 5 MB).</Text>
            {(provider?.images ?? []).map((image) => (
              <View key={image.id} style={styles.serviceCard}>
                <Text style={styles.serviceName}>{image.image_path}</Text>
                <Text style={styles.serviceDescription}>{image.image_type ?? 'publicidad'}</Text>
                <Pressable accessibilityRole="button" onPress={() => confirmDeleteImage(image.id)} style={[styles.actionButton, styles.deleteButton]}>
                  <Text style={[styles.actionText, styles.deleteText]}>Eliminar</Text>
                </Pressable>
              </View>
            ))}
            {(provider?.images ?? []).length === 0 ? (
              <Text style={styles.emptyText}>No tenés imagen publicitaria cargada.</Text>
            ) : null}
            <PrimaryButton title="Subir imagen" onPress={() => void handleUploadImage()} loading={uploadingImage} />
          </View>
        ) : null}

        {activePanel === 'categorias' && canConfigureBusiness ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🏷️ Categorías y Servicios</Text>
            <Text style={styles.cardSub}>Seleccioná los grupos y subgrupos que ofrecés.</Text>
            {(subgroups?.groups ?? []).map((group) => (
              <View key={group.id} style={styles.groupBlock}>
                <Text style={styles.groupTitle}>{group.description}</Text>
                <View style={styles.chipsWrap}>
                  {group.subgroups.map((sub) => {
                    const selected = subgroups?.selected.includes(sub.id) ?? false;
                    const busy = togglingCategory === sub.id;
                    return (
                      <Pressable
                        key={sub.id}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        disabled={busy}
                        onPress={() => void toggleSubgroup(sub.id)}
                        style={[styles.chip, selected && styles.chipSelected]}
                      >
                        <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{sub.description}</Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            ))}
            {flags?.can_see_service_types ? (
              <View>
                <Text style={styles.cardTitle}>Tipos de servicio</Text>
                <View style={styles.chipsWrap}>
                  {(serviceTypes?.service_types ?? []).map((type) => {
                    const selected = serviceTypes?.selected.includes(type.id) ?? false;
                    const busy = togglingCategory === type.id;
                    return (
                      <Pressable
                        key={type.id}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        disabled={busy}
                        onPress={() => void toggleServiceType(type.id)}
                        style={[styles.chip, selected && styles.chipSelected]}
                      >
                        <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{type.name}</Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            ) : null}
          </View>
        ) : null}

        {activePanel === 'direccion' && flags?.can_see_address ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📍 Mi Dirección</Text>
            {sharedAddress ? (
              <Text style={styles.cardSub}>Dirección del titular de la cuenta (solo lectura).</Text>
            ) : null}
            {readOnlyAddress && !sharedAddress ? (
              <Text style={styles.cardSub}>Solo el titular puede editar la dirección.</Text>
            ) : null}
            <Text style={styles.sectionLabel}>País</Text>
            <View style={styles.chipsWrap}>
              {countries.map((country) => {
                const selected = countryId === country.id;
                return (
                  <Pressable
                    key={country.id}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    disabled={readOnlyAddress}
                    onPress={() => void handleCountryChange(country.id)}
                    style={[styles.chip, selected && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{country.name}</Text>
                  </Pressable>
                );
              })}
            </View>
            <Text style={styles.sectionLabel}>Provincia / Región</Text>
            <View style={styles.chipsWrap}>
              {regions.map((region) => {
                const selected = provinceId === region.id;
                return (
                  <Pressable
                    key={region.id}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    disabled={readOnlyAddress}
                    onPress={() => setProvinceId(region.id)}
                    style={[styles.chip, selected && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{region.name}</Text>
                  </Pressable>
                );
              })}
            </View>
            {regions.length === 0 ? <Text style={styles.emptyText}>Seleccioná un país para ver sus regiones.</Text> : null}
            <TextField label="Calle" placeholder="Av. San Martín" value={street} onChangeText={setStreet} editable={!readOnlyAddress} />
            <TextField label="Número" placeholder="1234" value={addressNumber} onChangeText={setAddressNumber} editable={!readOnlyAddress} />
            <TextField label="Piso / Depto" placeholder="3B" value={floor} onChangeText={setFloor} editable={!readOnlyAddress} />
            <TextField label="Código Postal" placeholder="B1602" value={postal} onChangeText={setPostal} editable={!readOnlyAddress} />
            <TextField label="Notas de ubicación" placeholder="Frente a la plaza…" value={notes} onChangeText={setNotes} multiline editable={!readOnlyAddress} />
            {addressError ? <Text style={styles.fieldError}>{addressError}</Text> : null}
            {!readOnlyAddress ? (
              <PrimaryButton title="Guardar dirección" onPress={() => void handleSaveAddress()} loading={savingAddress} />
            ) : null}
          </View>
        ) : null}

        {activePanel === 'usuarios' && flags?.can_manage_users && !managedId ? (
          <View style={styles.card}>
            <View style={styles.rowHeader}>
              <Text style={styles.cardTitle}>👥 Usuarios de la cuenta</Text>
              {account?.limits.can_add ? (
                <Pressable accessibilityRole="button" onPress={openCreateAccountUser} style={styles.addButton}>
                  <Text style={styles.addText}>＋ Nuevo</Text>
                </Pressable>
              ) : null}
            </View>
            <Text style={styles.cardSub}>
              {account?.limits.secondary_count ?? 0} secundarios de {account?.limits.max_secondary ?? 3} · mismo correo
              para toda la cuenta.
            </Text>
            {(account?.data ?? []).map((accountUser) => {
              const selected = selectedUserId === accountUser.id;
              return (
                <Pressable
                  key={accountUser.id}
                  accessibilityRole="button"
                  onPress={() => setSelectedUserId(accountUser.id)}
                  style={[styles.serviceCard, selected && styles.serviceCardSelected]}
                >
                  <Text style={styles.serviceName}>
                    {accountUser.name} {accountUser.is_principal ? '· Titular' : ''}{' '}
                    {accountUser.is_self ? '· Vos' : ''}
                  </Text>
                  <Text style={styles.serviceDescription}>
                    {[accountUser.role, accountUser.status].filter(Boolean).join(' · ')}
                  </Text>
                  {accountUser.can_edit ? (
                    <View style={styles.serviceActions}>
                      <Pressable
                        accessibilityRole="button"
                        onPress={() => openEditAccountUser(accountUser.id, accountUser.name)}
                        style={styles.actionButton}
                      >
                        <Text style={styles.actionText}>Editar</Text>
                      </Pressable>
                      {!accountUser.is_self ? (
                        <Pressable
                          accessibilityRole="button"
                          onPress={() => confirmDeleteAccountUser(accountUser.id, accountUser.name)}
                          style={[styles.actionButton, styles.deleteButton]}
                        >
                          <Text style={[styles.actionText, styles.deleteText]}>Inactivar</Text>
                        </Pressable>
                      ) : null}
                    </View>
                  ) : (
                    <Text style={styles.serviceDescription}>El titular no se edita desde acá.</Text>
                  )}
                </Pressable>
              );
            })}
            {selectedAccountUser ? (
              <View style={styles.pagesBlock}>
                <Text style={styles.cardTitle}>Páginas de {selectedAccountUser.name}</Text>
                {(account?.catalog.modules ?? []).map((module) => (
                  <View key={module.id} style={styles.groupBlock}>
                    <Text style={styles.groupTitle}>{module.description}</Text>
                    {module.pages.map((page) => {
                      const hasPage = selectedAccountUser.page_ids.includes(page.id);
                      const busy = togglingPage === page.id;
                      const canToggle = selectedAccountUser.can_edit;
                      return (
                        <View key={page.id} style={styles.pageRow}>
                          <View style={styles.pageInfo}>
                            <Text style={styles.serviceName}>{page.description}</Text>
                            {page.url ? <Text style={styles.serviceDescription}>{page.url}</Text> : null}
                          </View>
                          <Switch
                            value={hasPage}
                            disabled={!canToggle || busy}
                            onValueChange={() => void toggleUserPage(selectedAccountUser.id, page.id, hasPage)}
                          />
                        </View>
                      );
                    })}
                  </View>
                ))}
              </View>
            ) : null}
          </View>
        ) : null}

        {activePanel === 'vehiculo' && flags?.is_repartidor && !managedId ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>🚗 Mi Vehículo</Text>
            <Text style={styles.cardSub}>Se usa para asignarte los pedidos.</Text>
            <View style={styles.chipsWrap}>
              {(profile?.vehicle_types ?? []).map((option) => {
                const selected = vehicleType === option.value;
                return (
                  <Pressable
                    key={option.value}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    onPress={() => setVehicleType(option.value)}
                    style={[styles.chip, selected && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{option.label}</Text>
                  </Pressable>
                );
              })}
            </View>
            <TextField label="Patente / Placa (opcional)" placeholder="Ej: AB123CD" value={vehiclePlate} onChangeText={setVehiclePlate} maxLength={20} autoCapitalize="characters" />
            {vehicleError ? <Text style={styles.fieldError}>{vehicleError}</Text> : null}
            <PrimaryButton title="Guardar vehículo" onPress={() => void handleSaveVehicle()} loading={savingVehicle} />
          </View>
        ) : null}

        {activePanel === 'cuenta' ? (
          <View>
            <View style={styles.card}>
              <Text style={styles.cardTitle}>👤 Datos personales</Text>
              <TextField label="Nombre" value={profileName} onChangeText={setProfileName} error={profileError} />
              <TextField label="Correo" value={profile?.user.email ?? user.email} editable={false} />
              <Text style={styles.cardSub}>
                Estado: {profile?.user.status ?? user.status ?? '-'} · Tipo:{' '}
                {profile?.user.role ?? user.role ?? '-'}
              </Text>
              <PrimaryButton title="Guardar nombre" onPress={() => void handleSaveProfile()} loading={savingProfile} />
            </View>
            <View style={styles.card}>
              <Text style={styles.cardTitle}>🔐 Cambiar contraseña</Text>
              <TextField label="Nueva contraseña" value={newPassword} onChangeText={setNewPassword} secureTextEntry />
              <TextField label="Confirmar nueva contraseña" value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry error={passwordError} />
              <PrimaryButton title="Guardar contraseña" onPress={() => void handleSavePassword()} loading={savingPassword} />
            </View>
            
          </View>
        ) : null}
      </ScrollView>

      <Modal visible={serviceModalOpen} transparent animationType="fade" onRequestClose={() => setServiceModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>{editingService ? 'Editar servicio' : 'Nuevo servicio'}</Text>
            <TextField label="Nombre del servicio" placeholder="Ej: Pizza a la piedra" value={serviceName} onChangeText={setServiceName} maxLength={255} error={serviceError} />
            <TextField label="Descripción (opcional)" placeholder="A la piedra…" value={serviceDescription} onChangeText={setServiceDescription} multiline />
            <View style={styles.gap} />
            <PrimaryButton title={editingService ? 'Actualizar' : 'Guardar'} onPress={() => void handleSaveService()} loading={savingService} />
            <View style={styles.gap} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setServiceModalOpen(false)} disabled={savingService} />
          </View>
        </View>
      </Modal>

      <Modal visible={userModalOpen} transparent animationType="fade" onRequestClose={() => setUserModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>{editingUserId ? 'Editar usuario' : 'Nuevo usuario'}</Text>
            <Text style={styles.cardSub}>Comparte el correo de la cuenta y hereda el rol del titular.</Text>
            <TextField label="Nombre" placeholder="Nombre y apellido" value={accountName} onChangeText={setAccountName} error={accountUserError} />
            <TextField label={editingUserId ? 'Nueva contraseña (opcional)' : 'Contraseña'} value={accountPassword} onChangeText={setAccountPassword} secureTextEntry />
            <TextField label={editingUserId ? 'Confirmar (si cambiás)' : 'Confirmar contraseña'} value={accountPasswordConfirm} onChangeText={setAccountPasswordConfirm} secureTextEntry />
            <View style={styles.gap} />
            <PrimaryButton title={editingUserId ? 'Actualizar' : 'Crear'} onPress={() => void handleSaveAccountUser()} loading={savingAccountUser} />
            <View style={styles.gap} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setUserModalOpen(false)} disabled={savingAccountUser} />
          </View>
        </View>
      </Modal>

      <AccountSidebar
        visible={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        user={profile?.user ?? (user ? {
          name: user.name,
          email: user.email,
          role: user.role,
          status: user.status,
          is_principal: flags?.is_principal ?? null,
        } : null)}
        flags={flags}
        managedId={managedId}
        managed={managed}
        activePanel={activePanel}
        onSelectPanel={handlePanelSelect}
        onExitManaged={() => void exitManaged()}
        loading={loading}
        error={error}
      />
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
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  menuButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 38,
    justifyContent: 'center',
    width: 38,
  },
  menuText: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  brand: {
    color: colors.brand,
    fontSize: 20,
    fontWeight: '800',
  },
  brandWrap: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  brandLogo: {
    height: 36,
    width: 44,
  },
  logout: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  logoutText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  managedBanner: {
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    borderBottomColor: '#bfdbfe',
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  managedText: {
    color: '#1d4ed8',
    flex: 1,
    fontSize: 13,
    fontWeight: '700',
    paddingRight: spacing.md,
  },
  managedExit: {
    backgroundColor: colors.surface,
    borderColor: '#bfdbfe',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  managedExitText: {
    color: '#1d4ed8',
    fontSize: 12,
    fontWeight: '700',
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  notice: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  noticeText: {
    color: colors.success,
    fontSize: 14,
  },
  profileCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    flexDirection: 'row',
    gap: spacing.lg,
    marginBottom: spacing.lg,
    padding: spacing.xl,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 64,
    justifyContent: 'center',
    width: 64,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 22,
    fontWeight: '800',
  },
  profileInfo: {
    flex: 1,
  },
  name: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '700',
  },
  email: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  badge: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  badgeText: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '700',
  },
  statusBadge: {
    backgroundColor: colors.background,
  },
  statusText: {
    color: colors.muted,
  },
  principalBadge: {
    backgroundColor: colors.successSoft,
  },
  principalText: {
    color: colors.success,
  },
  profileSectionHint: {
    alignItems: 'center',
    gap: 2,
  },
  profileSectionHintText: {
    color: colors.brandDark,
    fontSize: 11,
    fontWeight: '700',
  },
  profileChevron: {
    color: colors.brandDark,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 20,
  },
  chipsWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  chip: {
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  chipSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: colors.brandDark,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.xl,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
  cardSub: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  sectionLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: spacing.xs,
    marginTop: spacing.md,
  },
  gap: {
    height: spacing.sm,
  },
  rowHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  addButton: {
    alignItems: 'flex-end',
    minWidth: 70,
  },
  addText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  dayRow: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.sm,
    padding: spacing.md,
  },
  dayHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  dayLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  shiftRow: {
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  miniButton: {
    alignSelf: 'flex-start',
    borderColor: '#fecaca',
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  miniButtonText: {
    color: colors.danger,
    fontSize: 12,
    fontWeight: '600',
  },
  addShift: {
    marginTop: spacing.sm,
  },
  addShiftText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  serviceCard: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.sm,
    padding: spacing.md,
  },
  serviceCardSelected: {
    borderColor: colors.brand,
  },
  serviceName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  serviceDescription: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  serviceActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  actionButton: {
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  actionText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  deleteButton: {
    borderColor: '#fecaca',
  },
  deleteText: {
    color: colors.danger,
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  switchLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
  },
  fieldError: {
    color: colors.danger,
    fontSize: 12,
    marginBottom: spacing.sm,
  },
  groupBlock: {
    marginBottom: spacing.md,
  },
  groupTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  pagesBlock: {
    marginTop: spacing.lg,
  },
  pageRow: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
  },
  pageInfo: {
    flex: 1,
    paddingRight: spacing.md,
  },
  deleteAccount: {
    alignItems: 'center',
    marginTop: spacing.md,
    paddingVertical: spacing.md,
  },
  modalBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxWidth: 480,
    padding: spacing.lg,
    width: '100%',
  },
  modalTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.md,
  },
});
