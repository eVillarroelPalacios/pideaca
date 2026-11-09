import { router, useFocusEffect } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import {
  Alert,
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
import { useManaged } from '@/auth/ManagedContext';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type {
  AdminUserItem,
  AdminUserModule,
  TypeUserPagesPage,
  TypeUserPagesType,
} from '@/types/api';

type FieldErrors = {
  name?: string;
  password?: string;
  password_confirmation?: string;
  email?: string;
  general?: string;
};

export default function UsersScreen() {
  const { managed, enter: enterManaged, exit: exitManaged } = useManaged();
  const [users, setUsers] = useState<AdminUserItem[]>([]);
  const [modules, setModules] = useState<AdminUserModule[]>([]);
  const [statuses, setStatuses] = useState<{ id: number; status: string }[]>([]);
  const [types, setTypes] = useState<{ id: number; description: string }[]>([]);
  const [accountEmail, setAccountEmail] = useState('');
  const [actorIsAdmin, setActorIsAdmin] = useState(false);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Permisos: borrador por usuario seleccionado.
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [draftPages, setDraftPages] = useState<number[]>([]);
  const [originalPages, setOriginalPages] = useState<number[]>([]);
  const [selectedModuleId, setSelectedModuleId] = useState<number | null>(null);
  const [savingPermissions, setSavingPermissions] = useState(false);
  const [permissionsMsg, setPermissionsMsg] = useState<string | null>(null);

  // Modal de alta.
  const [createOpen, setCreateOpen] = useState(false);
  const [createName, setCreateName] = useState('');
  const [createPassword, setCreatePassword] = useState('');
  const [createPasswordConfirm, setCreatePasswordConfirm] = useState('');
  const [createErrors, setCreateErrors] = useState<FieldErrors>({});
  const [creating, setCreating] = useState(false);

  // Modal de edicion de datos.
  const [editOpen, setEditOpen] = useState(false);
  const [editUserId, setEditUserId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [editStatusId, setEditStatusId] = useState<number | null>(null);
  const [editRole, setEditRole] = useState('');
  const [editPassword, setEditPassword] = useState('');
  const [editPasswordConfirm, setEditPasswordConfirm] = useState('');
  const [countries, setCountries] = useState<{ id: number; name: string }[]>([]);
  const [regions, setRegions] = useState<{ id: number; name: string }[]>([]);
  const [editCountryId, setEditCountryId] = useState<number | null>(null);
  const [editProvinceId, setEditProvinceId] = useState<number | null>(null);
  const [editStreet, setEditStreet] = useState('');
  const [editNumber, setEditNumber] = useState('');
  const [editAddressId, setEditAddressId] = useState<number | null>(null);
  const [editErrors, setEditErrors] = useState<FieldErrors>({});
  const [savingEdit, setSavingEdit] = useState(false);

  // Modal de busqueda (Fase 2: Buscar Usuario de la web).
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchStatusId, setSearchStatusId] = useState<number | null>(null);
  const [searchTypeId, setSearchTypeId] = useState<number | null>(null);

  // Modal de paginas por tipo (Fase 3).
  const [typePagesOpen, setTypePagesOpen] = useState(false);
  const [typePagesLoading, setTypePagesLoading] = useState(false);
  const [tpTypes, setTpTypes] = useState<TypeUserPagesType[]>([]);
  const [tpPages, setTpPages] = useState<TypeUserPagesPage[]>([]);
  const [tpSelectedTypeId, setTpSelectedTypeId] = useState<number | null>(null);
  const [tpDraftPages, setTpDraftPages] = useState<number[]>([]);
  const [tpSelectedModuleId, setTpSelectedModuleId] = useState<number | null>(null);
  const [tpSaving, setTpSaving] = useState(false);
  const [tpMsg, setTpMsg] = useState<string | null>(null);

  const fetchUsers = useCallback(async () => {
    try {
      // Sin parametro: el Admin recibe TODOS los usuarios (igual que el
      // index web) y el resto su cuenta. El alcance a la cuenta gestionada
      // se aplica en cliente filtrando por email, como hace la web con
      // allUsers/getUserManagementUsers: asi la busqueda siempre ve todo.
      const response = await api.adminUsers();

      setUsers(response.data);
      setModules(response.catalog.modules);
      setStatuses(response.filters.user_statuses);
      setTypes(response.filters.types);
      setAccountEmail(response.account_email);
      setActorIsAdmin(response.actor.is_admin);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar los usuarios.',
      );
    }
  }, [managed?.id]);

  // Se recarga al entrar, al volver de otra pantalla y al cambiar de
  // cuenta gestionada.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchUsers().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchUsers, managed?.id]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchUsers();
    setRefreshing(false);
  }

  const selectedUser = useMemo(
    () => users.find((u) => u.id === selectedUserId) ?? null,
    [users, selectedUserId],
  );

  // La lista principal muestra la cuenta efectiva (gestionada si el Admin
  // activo el modo managed, si no la propia), igual que la web filtra
  // allUsers por email. La busqueda usa `users` completo.
  const effectiveEmail = (managed?.email ?? accountEmail).trim().toLowerCase();
  const accountUsers = useMemo(() => {
    if (!effectiveEmail) {
      return users;
    }

    const scoped = users.filter((u) => u.email.toLowerCase() === effectiveEmail);

    return scoped.length > 0 ? scoped : users;
  }, [users, effectiveEmail]);

  // Limites calculados sobre la cuenta en pantalla (igual que la web cuenta
  // secundarios sobre la lista filtrada).
  const secondaryCount = Math.max(0, accountUsers.length - 1);
  const canAdd = secondaryCount < 3;

  const selectedModule = useMemo(
    () => modules.find((m) => m.id === selectedModuleId) ?? null,
    [modules, selectedModuleId],
  );

  function selectUser(user: AdminUserItem) {
    // Igual que la web: el Admin opera sobre el usuario elegido (modo
    // managed); al tocarse a si mismo sale del modo gestionado.
    if (actorIsAdmin && !user.is_self && (!managed || managed.id !== user.id)) {
      void enterManaged({
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      });
      setNotice(`Gestionando la cuenta de ${user.name}.`);
    } else if (actorIsAdmin && user.is_self && managed) {
      void exitManaged();
      setNotice('Volviste a tu cuenta.');
    }

    setSelectedUserId(user.id);
    setOriginalPages(user.page_ids);
    setDraftPages(user.page_ids);
    const modulesWithSelection = modules.filter((m) =>
      m.pages.some((pg) => user.page_ids.includes(pg.id)),
    );
    setSelectedModuleId(
      modulesWithSelection[0]?.id ?? modules[0]?.id ?? null,
    );
    setPermissionsMsg(null);

    // Igual que la web: si esta inactivo se ofrece activarlo.
    if (user.is_inactive) {
      Alert.alert(
        'Usuario inactivo',
        `¿El usuario ${user.name} está inactivo. ¿Querés activarlo?`,
        [
          { text: 'Ahora no', style: 'cancel' },
          {
            text: 'Activar',
            onPress: () => void activateUser(user),
          },
        ],
      );
    }
  }

  function activeStatusId(): number | null {
    const active = statuses.find(
      (s) => /activ/i.test(s.status) && !/inactiv/i.test(s.status),
    );

    return active?.id ?? null;
  }

  async function activateUser(user: AdminUserItem) {
    const statusId = activeStatusId();

    if (!statusId) {
      setNotice('No hay un estado activo disponible.');
      return;
    }

    try {
      const response = await api.updateAdminUser(user.id, {
        name: user.name,
        email: user.email,
        user_status_id: statusId,
        type_user_id: user.type_user_id,
      });

      setNotice(response.message);
      await fetchUsers();
    } catch (caught) {
      Alert.alert(
        'No pudimos activar',
        caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
      );
    }
  }

  function cancelPermissions() {
    if (!selectedUser) {
      return;
    }

    setDraftPages(selectedUser.page_ids);
    setOriginalPages(selectedUser.page_ids);
    setPermissionsMsg('Cambios descartados.');
  }

  async function savePermissions() {
    if (!selectedUser || savingPermissions) {
      return;
    }

    if (selectedUser.is_principal && !actorIsAdmin) {
      setPermissionsMsg('El usuario principal no puede modificarse desde esta pantalla.');
      return;
    }

    const allowed = new Set(modules.flatMap((m) => m.pages.map((pg) => pg.id)));
    const original = new Set(originalPages.filter((id) => allowed.has(id)));
    const draft = new Set(draftPages.filter((id) => allowed.has(id)));
    const added = [...draft].filter((id) => !original.has(id));
    const removed = [...original].filter((id) => !draft.has(id));

    if (added.length === 0 && removed.length === 0) {
      setPermissionsMsg('No hay cambios para guardar.');
      return;
    }

    setSavingPermissions(true);
    setPermissionsMsg('Guardando cambios…');

    try {
      let failed = 0;

      for (const pageId of added) {
        try {
          await api.attachAdminUserPage(selectedUser.id, pageId);
        } catch {
          failed += 1;
        }
      }

      for (const pageId of removed) {
        try {
          await api.detachAdminUserPage(selectedUser.id, pageId);
        } catch {
          failed += 1;
        }
      }

      await fetchUsers();
      setPermissionsMsg(
        failed > 0
          ? `Algunos permisos no se pudieron guardar (${failed}).`
          : 'Permisos guardados correctamente.',
      );
    } finally {
      setSavingPermissions(false);
    }
  }

  function toggleDraftPage(pageId: number, value: boolean) {
    setDraftPages((current) =>
      value ? [...current, pageId] : current.filter((id) => id !== pageId),
    );
  }

  // Alta.
  function openCreate() {
    if (!canAdd) {
      Alert.alert(
        'Límite alcanzado',
        'Ya alcanzaste el límite de usuarios adicionales para esta cuenta.',
      );
      return;
    }

    setCreateName('');
    setCreatePassword('');
    setCreatePasswordConfirm('');
    setCreateErrors({});
    setNotice(null);
    setCreateOpen(true);
  }

  async function handleCreate() {
    const name = createName.trim();

    if (!name) {
      setCreateErrors({ name: 'El nombre es obligatorio.' });
      return;
    }

    if (createPassword.length < 8) {
      setCreateErrors({ password: 'La contraseña debe tener al menos 8 caracteres.' });
      return;
    }

    if (createPassword !== createPasswordConfirm) {
      setCreateErrors({ password_confirmation: 'Las contraseñas no coinciden.' });
      return;
    }

    setCreating(true);
    setCreateErrors({});

    try {
      // En modo managed el alta va a la cuenta gestionada (igual que la web
      // usa el email managed para el nuevo usuario).
      const response = await api.createAdminUser(
        {
          name,
          password: createPassword,
          password_confirmation: createPasswordConfirm,
        },
        managed?.id ?? null,
      );

      setCreateOpen(false);
      setNotice(response.message);
      await fetchUsers();
      selectUser(response.user);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setCreateErrors({
          name: caught.errors?.name?.[0],
          password: caught.errors?.password?.[0],
          password_confirmation: caught.errors?.password_confirmation?.[0],
          email: caught.errors?.email?.[0],
          general: caught.message,
        });
        setCreateErrors((current) =>
          current.name || current.password || current.password_confirmation || current.email
            ? { ...current, general: undefined }
            : current,
        );
      } else {
        setCreateErrors({ general: 'No pudimos crear el usuario.' });
      }
    } finally {
      setCreating(false);
    }
  }

  // Edicion de datos.
  async function openEdit(user: AdminUserItem) {
    if (!user.can_edit) {
      return;
    }

    selectUser(user);
    setEditUserId(user.id);
    setEditName(user.name);
    setEditStatusId(user.status_id);
    setEditRole(user.principal_role ?? user.role ?? 'Usuario');
    setEditPassword('');
    setEditPasswordConfirm('');
    setEditErrors({});
    setNotice(null);

    try {
      setCountries((await api.profileCountries()).data);
    } catch {
      setCountries([]);
    }

    const address = user.primary_address;
    setEditAddressId(address?.id ?? null);
    setEditCountryId(address?.country_id ?? null);
    setEditProvinceId(address?.province_id ?? null);
    setEditStreet(address?.street ?? '');
    setEditNumber(address?.number ?? '');

    if (address?.country_id) {
      try {
        setRegions((await api.profileRegions(address.country_id)).data);
      } catch {
        setRegions([]);
      }
    } else {
      setRegions([]);
    }

    setEditOpen(true);
  }

  async function handleCountryChange(countryId: number) {
    setEditCountryId(countryId);
    setEditProvinceId(null);

    try {
      setRegions((await api.profileRegions(countryId)).data);
    } catch {
      setRegions([]);
    }
  }

  async function handleSaveEdit() {
    if (editUserId === null) {
      return;
    }

    const user = users.find((u) => u.id === editUserId);

    if (!user) {
      return;
    }

    const name = editName.trim();

    if (!name) {
      setEditErrors({ name: 'El nombre es obligatorio.' });
      return;
    }

    if (editPassword && editPassword.length < 8) {
      setEditErrors({ password: 'La contraseña debe tener al menos 8 caracteres.' });
      return;
    }

    if (editPassword && editPassword !== editPasswordConfirm) {
      setEditErrors({ password_confirmation: 'Las contraseñas no coinciden.' });
      return;
    }

    setSavingEdit(true);
    setEditErrors({});

    try {
      const response = await api.updateAdminUser(editUserId, {
        name,
        email: user.email,
        user_status_id: editStatusId,
        type_user_id: user.type_user_id,
        ...(editPassword
          ? { password: editPassword, password_confirmation: editPasswordConfirm }
          : {}),
      });

      // La direccion se guarda junto con los datos, igual que en la web.
      if (editCountryId) {
        const addressPayload = {
          country_id: editCountryId,
          province_id: editProvinceId,
          street: editStreet.trim() ? editStreet.trim() : null,
          number: editNumber.trim() ? editNumber.trim() : null,
        };

        if (editAddressId) {
          await api.updateAdminUserAddress(editUserId, editAddressId, addressPayload);
        } else {
          await api.createAdminUserAddress(editUserId, addressPayload);
        }
      }

      setEditOpen(false);
      setNotice(response.message);
      await fetchUsers();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setEditErrors({
          name: caught.errors?.name?.[0],
          email: caught.errors?.email?.[0],
          password: caught.errors?.password?.[0],
          password_confirmation: caught.errors?.password_confirmation?.[0],
          general: caught.message,
        });
        setEditErrors((current) =>
          current.name || current.email || current.password || current.password_confirmation
            ? { ...current, general: undefined }
            : current,
        );
      } else {
        setEditErrors({ general: 'No pudimos guardar los datos.' });
      }
    } finally {
      setSavingEdit(false);
    }
  }

  // Fase 4: salir del modo managed (banner "Gestionando cuenta de...").
  async function handleExitManaged() {
    await exitManaged();
    setSelectedUserId(null);
    setOriginalPages([]);
    setDraftPages([]);
    setSelectedModuleId(null);
    setPermissionsMsg(null);
    setNotice('Volviste a tu cuenta.');
  }

  // Inactivar.
  function confirmInactivate(user: AdminUserItem) {    if (user.is_principal) {
      Alert.alert(
        'No se puede eliminar',
        'No podés eliminar al usuario principal de la cuenta.',
      );
      return;
    }

    if (user.is_self) {
      Alert.alert(
        'No se puede eliminar',
        'No puedes inactivar tu propia cuenta.',
      );
      return;
    }

    Alert.alert(
      'Inactivar usuario',
      `¿Inactivar a ${user.name}? Se le quitan sus páginas.`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Inactivar',
          style: 'destructive',
          onPress: () =>
            void (async () => {
              try {
                const response = await api.deleteAdminUser(user.id);
                setNotice(response.message);
                await fetchUsers();
              } catch (caught) {
                Alert.alert(
                  'No pudimos inactivar',
                  caught instanceof ApiError ? caught.message : 'Intentá de nuevo.',
                );
              }
            })(),
        },
      ],
    );
  }

  // Fase 2: Buscar Usuario (misma idea que el modal web: texto + filtros
  // por estado y tipo sobre los usuarios cargados).
  const searchResults = useMemo(() => {
    const term = searchQuery.trim().toLowerCase();

    return users.filter((user) => {
      const matchesText =
        !term ||
        user.name.toLowerCase().includes(term) ||
        user.email.toLowerCase().includes(term);
      const matchesStatus = !searchStatusId || user.status_id === searchStatusId;
      const matchesType = !searchTypeId || user.type_user_id === searchTypeId;

      return matchesText && matchesStatus && matchesType;
    });
  }, [users, searchQuery, searchStatusId, searchTypeId]);

  function openSearch() {
    setSearchQuery('');
    setSearchStatusId(null);
    setSearchTypeId(null);
    setSearchOpen(true);
  }

  // Fase 3: Paginas por tipo (modal web de plantilla por tipo).
  const tpSelectedType = useMemo(
    () => tpTypes.find((t) => t.id === tpSelectedTypeId) ?? null,
    [tpTypes, tpSelectedTypeId],
  );

  const tpModuleIds = useMemo(() => {
    const ids: number[] = [];
    const seen = new Set<number>();

    for (const page of tpPages) {
      if (!seen.has(page.module_id)) {
        seen.add(page.module_id);
        ids.push(page.module_id);
      }
    }

    return ids;
  }, [tpPages]);

  const tpModuleName = useCallback(
    (moduleId: number) => {
      const page = tpPages.find((p) => p.module_id === moduleId);

      return page?.module ?? `Módulo ${moduleId}`;
    },
    [tpPages],
  );

  const tpVisiblePages = useMemo(
    () =>
      tpSelectedModuleId === null
        ? tpPages
        : tpPages.filter((p) => p.module_id === tpSelectedModuleId),
    [tpPages, tpSelectedModuleId],
  );

  async function openTypePages() {
    setTypePagesOpen(true);
    setTypePagesLoading(true);
    setTpMsg(null);

    try {
      const response = await api.typeUserPagesData();

      setTpTypes(response.types);
      setTpPages(response.pages);
      setTpSelectedTypeId(response.types[0]?.id ?? null);
      setTpDraftPages(response.types[0]?.assigned_page_ids ?? []);
      setTpSelectedModuleId(null);
    } catch (caught) {
      setTpMsg(
        caught instanceof ApiError ? caught.message : 'No pudimos cargar los tipos.',
      );
    } finally {
      setTypePagesLoading(false);
    }
  }

  function selectTpType(typeId: number) {
    setTpSelectedTypeId(typeId);
    setTpDraftPages(tpTypes.find((t) => t.id === typeId)?.assigned_page_ids ?? []);
    setTpMsg(null);
  }

  function toggleTpPage(pageId: number, value: boolean) {
    setTpDraftPages((current) =>
      value ? [...current, pageId] : current.filter((id) => id !== pageId),
    );
  }

  async function saveTypePages() {
    if (tpSelectedTypeId === null || tpSaving) {
      return;
    }

    setTpSaving(true);
    setTpMsg('Guardando cambios…');

    try {
      const response = await api.updateTypeUserPages(tpSelectedTypeId, tpDraftPages);

      setTpMsg(response.message);
      const refreshed = await api.typeUserPagesData();

      setTpTypes(refreshed.types);
      setTpPages(refreshed.pages);
      setTpDraftPages(
        refreshed.types.find((t) => t.id === tpSelectedTypeId)?.assigned_page_ids ?? [],
      );
      await fetchUsers();
    } catch (caught) {
      setTpMsg(
        caught instanceof ApiError ? caught.message : 'No pudimos guardar.',
      );
    } finally {
      setTpSaving(false);
    }
  }

  if (loading && users.length === 0) {
    return <LoadingScreen message="Cargando usuarios…" />;
  }

  const progressPct = Math.min(100, Math.round((secondaryCount / 3) * 100));
  const atLimit = !canAdd;
  const readOnlyPermissions = selectedUser
    ? selectedUser.is_principal && !actorIsAdmin
    : false;

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <Text style={styles.title}>Usuarios</Text>
        <View style={styles.addButton} />
      </View>

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
        <Text style={styles.subtitle}>
          Administrá los usuarios de tu cuenta y asigná permisos de páginas.
        </Text>

        <View style={styles.headActions}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Buscar usuario"
            onPress={openSearch}
            style={styles.outlineButton}
          >
            <Text style={styles.outlineText}>🔍 Buscar Usuario</Text>
          </Pressable>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Páginas por tipo"
            onPress={() => void openTypePages()}
            style={styles.outlineButton}
          >
            <Text style={styles.outlineText}>🧩 Páginas por tipo</Text>
          </Pressable>
        </View>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        {managed ? (
          <View style={styles.managedCard}>
            <View style={styles.managedInfo}>
              <Text style={styles.managedTitle}>Gestionando cuenta de {managed.name}</Text>
              <Text style={styles.managedSub}>
                {managed.email}
                {managed.role ? ` · ${managed.role}` : ''}
              </Text>
            </View>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Salir de la cuenta gestionada"
              onPress={() => void handleExitManaged()}
              style={styles.managedExit}
            >
              <Text style={styles.managedExitText}>Salir</Text>
            </Pressable>
          </View>
        ) : null}

        <View style={styles.limitCard}>
          <View style={styles.limitInfo}>
            <Text style={styles.limitTitle}>Usuarios de la cuenta</Text>
            <Text style={styles.limitCount}>
              {secondaryCount} de 3 creados
            </Text>
            <Text style={styles.limitHint}>
              {atLimit
                ? 'Alcanzaste el límite de usuarios adicionales de la cuenta.'
                : 'Podés crear hasta 3 usuarios adicionales.'}
            </Text>
            <View style={styles.progress}>
              <View style={[styles.progressBar, { width: `${progressPct}%` }, atLimit && styles.progressBarFull]} />
            </View>
          </View>
          <PrimaryButton
            title="Agregar nuevo usuario"
            onPress={openCreate}
            disabled={atLimit}
          />
        </View>

        <View style={styles.card}>
          <View style={styles.cardHead}>
            <View>
              <Text style={styles.cardTitle}>Usuarios</Text>
              <Text style={styles.cardSub}>Seleccioná un usuario para editar sus permisos.</Text>
            </View>
            <View style={styles.countBadge}>
              <Text style={styles.countText}>{accountUsers.length}</Text>
            </View>
          </View>

          {accountUsers.length === 0 ? (
            <Text style={styles.emptyText}>No hay usuarios creados en esta cuenta.</Text>
          ) : (
            accountUsers.map((user) => {
              const selected = selectedUserId === user.id;
              return (
                <Pressable
                  key={user.id}
                  accessibilityRole="button"
                  onPress={() => selectUser(user)}
                  style={[styles.userRow, selected && styles.userRowSelected]}
                >
                  <View style={styles.userInfo}>
                    <Text style={styles.userName}>
                      {user.name}
                      {user.is_principal ? ' (principal)' : ''}
                      {user.is_inactive ? ' · Inactivo' : ''}
                    </Text>
                    <Text style={styles.userEmail}>{user.email}</Text>
                  </View>
                  <View style={styles.userSide}>
                    <View style={[styles.roleBadge, user.is_principal && styles.rolePrincipal]}>
                      <Text style={[styles.roleText, user.is_principal && styles.roleTextPrincipal]}>
                        {user.role ?? 'Usuario'}
                      </Text>
                    </View>
                    <View style={styles.userActions}>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Editar permisos"
                        onPress={() => selectUser(user)}
                        style={styles.iconButton}
                      >
                        <Text style={styles.iconText}>✓</Text>
                      </Pressable>
                      {user.can_edit ? (
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Editar datos"
                          onPress={() => void openEdit(user)}
                          style={styles.iconButton}
                        >
                          <Text style={styles.iconText}>✎</Text>
                        </Pressable>
                      ) : null}
                      {!user.is_principal && !user.is_self ? (
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Inactivar usuario"
                          onPress={() => confirmInactivate(user)}
                          style={[styles.iconButton, styles.deleteButton]}
                        >
                          <Text style={[styles.iconText, styles.deleteText]}>🗑</Text>
                        </Pressable>
                      ) : null}
                    </View>
                  </View>
                </Pressable>
              );
            })
          )}
        </View>

        <View style={styles.card}>
          <View style={styles.cardHead}>
            <View style={styles.cardHeadText}>
              <Text style={styles.cardTitle}>Asignar Permisos de Páginas</Text>
              <Text style={styles.cardSub}>
                {selectedUser
                  ? `${selectedUser.name}${selectedUser.email ? ` · ${selectedUser.email}` : ''}`
                  : 'Seleccioná un usuario de la lista.'}
              </Text>
            </View>
            {selectedUser?.role ? (
              <View style={[styles.roleBadge, selectedUser.is_principal && styles.rolePrincipal]}>
                <Text style={[styles.roleText, selectedUser.is_principal && styles.roleTextPrincipal]}>
                  {selectedUser.role}
                </Text>
              </View>
            ) : null}
          </View>

          {!selectedUser ? (
            <Text style={styles.emptyText}>Seleccioná un usuario para asignar permisos.</Text>
          ) : (
            <View>
              {readOnlyPermissions ? (
                <Text style={styles.parentNote}>
                  Este es un usuario principal de la cuenta. Sus permisos se administran desde los
                  módulos de la barra superior y no se pueden modificar desde esta pantalla.
                </Text>
              ) : (
                <Text style={styles.parentNote}>
                  Solo se muestran los módulos y páginas que tu usuario tiene habilitados.
                </Text>
              )}

              <Text style={styles.selectorLabel}>Módulos</Text>
              <View style={styles.chipsWrap}>
                {modules.map((module) => {
                  const count = module.pages.filter((pg) => draftPages.includes(pg.id)).length;
                  const selected = selectedModuleId === module.id;
                  return (
                    <Pressable
                      key={module.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => setSelectedModuleId(module.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                        {module.description} · {count}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
              {modules.length === 0 ? (
                <Text style={styles.emptyText}>No hay módulos disponibles para asignar.</Text>
              ) : null}

              <Text style={styles.selectorLabel}>
                Páginas permitidas · {draftPages.length} seleccionadas
              </Text>
              {(selectedModule?.pages ?? []).map((page) => {
                const checked = draftPages.includes(page.id);
                return (
                  <View key={page.id} style={styles.pageRow}>
                    <View style={styles.pageInfo}>
                      <Text style={styles.serviceName}>{page.description}</Text>
                      {page.url ? <Text style={styles.serviceDescription}>{page.url}</Text> : null}
                    </View>
                    <Switch
                      value={checked}
                      disabled={readOnlyPermissions || savingPermissions}
                      onValueChange={(value) => toggleDraftPage(page.id, value)}
                    />
                  </View>
                );
              })}
              {selectedModule && selectedModule.pages.length === 0 ? (
                <Text style={styles.emptyText}>Este módulo no tiene páginas disponibles.</Text>
              ) : null}
              {!selectedModule ? (
                <Text style={styles.emptyText}>Seleccioná un módulo para ver sus páginas.</Text>
              ) : null}

              {permissionsMsg ? <Text style={styles.permMsg}>{permissionsMsg}</Text> : null}

              {!readOnlyPermissions ? (
                <View style={styles.footerRow}>
                  <PrimaryButton title="Cancelar" variant="ghost" onPress={cancelPermissions} disabled={savingPermissions} />
                  <View style={styles.footerGap} />
                  <PrimaryButton title="Guardar Cambios" onPress={() => void savePermissions()} loading={savingPermissions} />
                </View>
              ) : null}
            </View>
          )}
        </View>
      </ScrollView>

      <Modal visible={createOpen} transparent animationType="fade" onRequestClose={() => setCreateOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Nuevo Usuario</Text>
            <Text style={styles.cardSub}>Comparte el correo de la cuenta y hereda el rol del titular.</Text>
            <TextField label="Nombre" placeholder="Nombre y apellido" value={createName} onChangeText={setCreateName} error={createErrors.name} />
            <TextField label="Correo" value={managed?.email ?? accountEmail} editable={false} />
            <TextField label="Contraseña" value={createPassword} onChangeText={setCreatePassword} secureTextEntry error={createErrors.password} />
            <TextField
              label="Confirmar contraseña"
              value={createPasswordConfirm}
              onChangeText={setCreatePasswordConfirm}
              secureTextEntry
              error={createErrors.password_confirmation ?? createErrors.email}
            />
            {createErrors.general ? <Text style={styles.fieldError}>{createErrors.general}</Text> : null}
            <View style={styles.gap} />
            <PrimaryButton title="Guardar" onPress={() => void handleCreate()} loading={creating} />
            <View style={styles.gap} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setCreateOpen(false)} disabled={creating} />
          </View>
        </View>
      </Modal>

      <Modal visible={editOpen} transparent animationType="fade" onRequestClose={() => setEditOpen(false)}>
        <View style={styles.modalBackdrop}>
          <ScrollView contentContainerStyle={styles.modalScroll}>
            <View style={styles.modalCard}>
              <Text style={styles.modalTitle}>Editar Datos del Usuario</Text>
              <TextField label="Nombre" placeholder="Nombre completo" value={editName} onChangeText={setEditName} error={editErrors.name} />
              <TextField label="Email" value={users.find((u) => u.id === editUserId)?.email ?? ''} editable={false} />
              <Text style={styles.selectorLabel}>País</Text>
              <View style={styles.chipsWrap}>
                {countries.map((country) => {
                  const selected = editCountryId === country.id;
                  return (
                    <Pressable
                      key={country.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => void handleCountryChange(country.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{country.name}</Text>
                    </Pressable>
                  );
                })}
              </View>
              <Text style={styles.selectorLabel}>Provincia / Región</Text>
              <View style={styles.chipsWrap}>
                {regions.map((region) => {
                  const selected = editProvinceId === region.id;
                  return (
                    <Pressable
                      key={region.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => setEditProvinceId(region.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{region.name}</Text>
                    </Pressable>
                  );
                })}
              </View>
              {regions.length === 0 ? <Text style={styles.emptyText}>Seleccioná un país para ver sus regiones.</Text> : null}
              <TextField label="Calle" placeholder="Av. San Martín" value={editStreet} onChangeText={setEditStreet} />
              <TextField label="Número" placeholder="1234" value={editNumber} onChangeText={setEditNumber} />
              <Text style={styles.selectorLabel}>Estado</Text>
              <View style={styles.chipsWrap}>
                {statuses.map((status) => {
                  const selected = editStatusId === status.id;
                  return (
                    <Pressable
                      key={status.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => setEditStatusId(status.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{status.status}</Text>
                    </Pressable>
                  );
                })}
              </View>
              <Text style={styles.selectorLabel}>Rol de la cuenta principal</Text>
              <Text style={styles.cardSub}>{editRole} · El rol lo define el usuario principal de la cuenta.</Text>
              <TextField
                label="Nueva contraseña (opcional)"
                value={editPassword}
                onChangeText={setEditPassword}
                secureTextEntry
                error={editErrors.password}
              />
              <TextField
                label="Confirmar (si cambiás)"
                value={editPasswordConfirm}
                onChangeText={setEditPasswordConfirm}
                secureTextEntry
                error={editErrors.password_confirmation}
              />
              {editErrors.general ? <Text style={styles.fieldError}>{editErrors.general}</Text> : null}
              <View style={styles.gap} />
              <PrimaryButton title="Guardar Cambios" onPress={() => void handleSaveEdit()} loading={savingEdit} />
              <View style={styles.gap} />
              <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setEditOpen(false)} disabled={savingEdit} />
              {(() => {
                const target = users.find((u) => u.id === editUserId);
                if (!target || target.is_principal || target.is_self) {
                  return null;
                }
                return (
                  <Pressable
                    accessibilityRole="button"
                    disabled={savingEdit}
                    onPress={() => {
                      setEditOpen(false);
                      confirmInactivate(target);
                    }}
                    style={styles.dangerLink}
                  >
                    <Text style={styles.dangerLinkText}>Inactivar usuario</Text>
                  </Pressable>
                );
              })()}
            </View>
          </ScrollView>
        </View>
      </Modal>

      <Modal visible={searchOpen} transparent animationType="fade" onRequestClose={() => setSearchOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, styles.tallModal]}>
            <Text style={styles.modalTitle}>Buscar Usuario</Text>
            <TextField
              label="Nombre o correo"
              placeholder="Escribí para filtrar…"
              value={searchQuery}
              onChangeText={setSearchQuery}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <Text style={styles.selectorLabel}>Estado</Text>
            <View style={styles.chipsWrap}>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ selected: searchStatusId === null }}
                onPress={() => setSearchStatusId(null)}
                style={[styles.chip, searchStatusId === null && styles.chipSelected]}
              >
                <Text style={[styles.chipText, searchStatusId === null && styles.chipTextSelected]}>Todos</Text>
              </Pressable>
              {statuses.map((status) => {
                const selected = searchStatusId === status.id;
                return (
                  <Pressable
                    key={status.id}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    onPress={() => setSearchStatusId(selected ? null : status.id)}
                    style={[styles.chip, selected && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{status.status}</Text>
                  </Pressable>
                );
              })}
            </View>
            <Text style={styles.selectorLabel}>Tipo</Text>
            <View style={styles.chipsWrap}>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ selected: searchTypeId === null }}
                onPress={() => setSearchTypeId(null)}
                style={[styles.chip, searchTypeId === null && styles.chipSelected]}
              >
                <Text style={[styles.chipText, searchTypeId === null && styles.chipTextSelected]}>Todos</Text>
              </Pressable>
              {types.map((type) => {
                const selected = searchTypeId === type.id;
                return (
                  <Pressable
                    key={type.id}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    onPress={() => setSearchTypeId(selected ? null : type.id)}
                    style={[styles.chip, selected && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{type.description}</Text>
                  </Pressable>
                );
              })}
            </View>
            <Text style={styles.selectorLabel}>{searchResults.length} resultados</Text>
            <ScrollView style={styles.resultsList}>
              {searchResults.map((user) => (
                <Pressable
                  key={user.id}
                  accessibilityRole="button"
                  onPress={() => {
                    setSearchOpen(false);
                    selectUser(user);
                  }}
                  style={styles.resultRow}
                >
                  <View style={styles.userInfo}>
                    <Text style={styles.userName}>
                      {user.name}
                      {user.is_principal ? ' (principal)' : ''}
                      {user.is_inactive ? ' · Inactivo' : ''}
                    </Text>
                    <Text style={styles.userEmail}>{user.email}</Text>
                  </View>
                  <View style={styles.resultSide}>
                    <Text style={styles.roleText}>{user.role ?? 'Usuario'}</Text>
                  </View>
                </Pressable>
              ))}
              {searchResults.length === 0 ? (
                <Text style={styles.emptyText}>Sin resultados para esos filtros.</Text>
              ) : null}
            </ScrollView>
            <View style={styles.gap} />
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setSearchOpen(false)} />
          </View>
        </View>
      </Modal>

      <Modal visible={typePagesOpen} transparent animationType="fade" onRequestClose={() => setTypePagesOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, styles.tallModal]}>
            <Text style={styles.modalTitle}>Páginas por tipo</Text>
            {typePagesLoading ? (
              <Text style={styles.emptyText}>Cargando tipos…</Text>
            ) : (
              <ScrollView>
                <Text style={styles.selectorLabel}>Tipo de usuario</Text>
                <View style={styles.chipsWrap}>
                  {tpTypes.map((type) => {
                    const selected = tpSelectedTypeId === type.id;
                    return (
                      <Pressable
                        key={type.id}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        onPress={() => selectTpType(type.id)}
                        style={[styles.chip, selected && styles.chipSelected]}
                      >
                        <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                          {type.description} ({type.users_count})
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
                {tpSelectedType ? (
                  <Text style={styles.cardSub}>
                    {tpSelectedType.users_count} usuario(s) con este tipo. Al guardar, las páginas
                    seleccionadas se aplican a todos ellos.
                  </Text>
                ) : null}
                <Text style={styles.selectorLabel}>Módulo</Text>
                <View style={styles.chipsWrap}>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityState={{ selected: tpSelectedModuleId === null }}
                    onPress={() => setTpSelectedModuleId(null)}
                    style={[styles.chip, tpSelectedModuleId === null && styles.chipSelected]}
                  >
                    <Text style={[styles.chipText, tpSelectedModuleId === null && styles.chipTextSelected]}>
                      Todos
                    </Text>
                  </Pressable>
                  {tpModuleIds.map((moduleId) => {
                    const selected = tpSelectedModuleId === moduleId;
                    return (
                      <Pressable
                        key={moduleId}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        onPress={() => setTpSelectedModuleId(selected ? null : moduleId)}
                        style={[styles.chip, selected && styles.chipSelected]}
                      >
                        <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                          {tpModuleName(moduleId)}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
                {tpVisiblePages.map((page) => {
                  const checked = tpDraftPages.includes(page.id);
                  return (
                    <View key={page.id} style={styles.pageRow}>
                      <View style={styles.pageInfo}>
                        <Text style={styles.serviceName}>{page.description}</Text>
                        {page.url ? <Text style={styles.serviceDescription}>{page.url}</Text> : null}
                      </View>
                      <Switch value={checked} onValueChange={(value) => toggleTpPage(page.id, value)} />
                    </View>
                  );
                })}
                {tpVisiblePages.length === 0 ? (
                  <Text style={styles.emptyText}>
                    {tpSelectedTypeId === null
                      ? 'Seleccioná un tipo de usuario.'
                      : 'No hay páginas para este módulo.'}
                  </Text>
                ) : null}
                {tpMsg ? <Text style={styles.permMsg}>{tpMsg}</Text> : null}
              </ScrollView>
            )}
            <View style={styles.gap} />
            <PrimaryButton
              title="Guardar"
              onPress={() => void saveTypePages()}
              loading={tpSaving}
              disabled={tpSelectedTypeId === null}
            />
            <View style={styles.gap} />
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setTypePagesOpen(false)} disabled={tpSaving} />
          </View>
        </View>
      </Modal>
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
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    color: colors.text,
    flexShrink: 1,
    fontSize: 18,
    fontWeight: '800',
    textAlign: 'center',
  },
  addButton: {
    minWidth: 70,
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
  },
  headActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  outlineButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.brand,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  outlineText: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '700',
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
  managedCard: {
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    borderColor: '#bfdbfe',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  managedInfo: {
    flex: 1,
    paddingRight: spacing.md,
  },
  managedTitle: {
    color: '#1d4ed8',
    fontSize: 13,
    fontWeight: '800',
  },
  managedSub: {
    color: '#1d4ed8',
    fontSize: 11,
    marginTop: 2,
  },
  managedExit: {
    backgroundColor: colors.surface,
    borderColor: '#bfdbfe',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  managedExitText: {
    color: '#1d4ed8',
    fontSize: 12,
    fontWeight: '700',
  },
  resultSide: {
    alignItems: 'flex-end',
    gap: spacing.xs,
  },
  limitCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.xl,
  },
  limitInfo: {
    marginBottom: spacing.md,
  },
  limitTitle: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  limitCount: {
    color: colors.brand,
    fontSize: 13,
    fontWeight: '700',
    marginTop: spacing.xs,
  },
  limitHint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  progress: {
    backgroundColor: colors.background,
    borderRadius: radius.pill,
    height: 8,
    marginTop: spacing.sm,
    overflow: 'hidden',
  },
  progressBar: {
    backgroundColor: colors.brand,
    height: '100%',
  },
  progressBarFull: {
    backgroundColor: colors.danger,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.xl,
  },
  cardHead: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  cardHeadText: {
    flex: 1,
    paddingRight: spacing.md,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  cardSub: {
    color: colors.muted,
    fontSize: 12,
    marginBottom: spacing.xs,
    marginTop: 2,
  },
  countBadge: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderRadius: radius.pill,
    justifyContent: 'center',
    minWidth: 26,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  countText: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
  },
  userRow: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.md,
  },
  userRowSelected: {
    backgroundColor: colors.brandSoft,
  },
  userInfo: {
    flex: 1,
    paddingRight: spacing.sm,
  },
  userName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  userEmail: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  userSide: {
    alignItems: 'flex-end',
    gap: spacing.xs,
  },
  roleBadge: {
    backgroundColor: '#eff6ff',
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  rolePrincipal: {
    backgroundColor: colors.brandSoft,
  },
  roleText: {
    color: '#1d4ed8',
    fontSize: 10,
    fontWeight: '700',
  },
  roleTextPrincipal: {
    color: colors.brandDark,
  },
  userActions: {
    flexDirection: 'row',
    gap: spacing.xs,
  },
  iconButton: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  iconText: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '700',
  },
  deleteButton: {
    borderColor: '#fecaca',
  },
  deleteText: {
    color: colors.danger,
  },
  parentNote: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.sm,
    borderWidth: 1,
    color: '#b45309',
    fontSize: 11,
    marginBottom: spacing.md,
    padding: spacing.sm,
  },
  selectorLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: spacing.xs,
    marginTop: spacing.md,
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
  serviceName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  serviceDescription: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  permMsg: {
    color: colors.success,
    fontSize: 12,
    fontWeight: '600',
    marginTop: spacing.md,
  },
  footerRow: {
    flexDirection: 'row',
    marginTop: spacing.md,
  },
  footerGap: {
    width: spacing.sm,
  },
  fieldError: {
    color: colors.danger,
    fontSize: 12,
    marginBottom: spacing.sm,
  },
  dangerLink: {
    alignItems: 'center',
    marginTop: spacing.md,
    paddingVertical: spacing.md,
  },
  dangerLinkText: {
    color: colors.danger,
    fontSize: 14,
    fontWeight: '700',
  },
  gap: {
    height: spacing.sm,
  },
  modalBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalScroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxWidth: 520,
    padding: spacing.lg,
    width: '100%',
  },
  tallModal: {
    maxHeight: '88%',
  },
  resultsList: {
    maxHeight: 320,
  },
  resultRow: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
  },
  modalTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
});
