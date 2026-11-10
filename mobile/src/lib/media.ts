import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';

export type LocalPhoto = {
  uri: string;
  name: string;
  mimeType: string;
  size?: number | null;
};

export type LocalAudio = {
  uri: string;
  name: string;
  mimeType: string;
};

function toLocalPhoto(asset: ImagePicker.ImagePickerAsset, fallbackName: string): LocalPhoto {
  const name = asset.fileName ?? fallbackName;

  return {
    uri: asset.uri,
    name,
    mimeType: asset.mimeType ?? 'image/jpeg',
    size: asset.fileSize ?? null,
  };
}

async function ensureLibraryPermission(): Promise<boolean> {
  const result = await ImagePicker.requestMediaLibraryPermissionsAsync();
  return result.granted;
}

async function ensureCameraPermission(): Promise<boolean> {
  const result = await ImagePicker.requestCameraPermissionsAsync();
  return result.granted;
}

/** Elige una foto de la galería. Devuelve null si no hay permiso o se cancela. */
export async function pickSingleImage(): Promise<LocalPhoto | null> {
  if (!(await ensureLibraryPermission())) {
    return null;
  }

  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ['images'],
    quality: 0.7,
  });

  if (result.canceled || result.assets.length === 0) {
    return null;
  }

  return toLocalPhoto(result.assets[0], `foto_${Date.now()}.jpg`);
}

/** Saca una foto con la cámara. Devuelve null si no hay permiso o se cancela. */
export async function takePhoto(): Promise<LocalPhoto | null> {
  if (!(await ensureCameraPermission())) {
    return null;
  }

  const result = await ImagePicker.launchCameraAsync({
    mediaTypes: ['images'],
    quality: 0.7,
  });

  if (result.canceled || result.assets.length === 0) {
    return null;
  }

  return toLocalPhoto(result.assets[0], `foto_${Date.now()}.jpg`);
}

/** Elige hasta `max` fotos de la galería. */
export async function pickMultipleImages(max: number): Promise<LocalPhoto[]> {
  if (!(await ensureLibraryPermission())) {
    return [];
  }

  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ['images'],
    allowsMultipleSelection: true,
    selectionLimit: max,
    quality: 0.7,
  });

  if (result.canceled) {
    return [];
  }

  return result.assets.map((asset, index) =>
    toLocalPhoto(asset, `foto_${Date.now()}_${index}.jpg`),
  );
}

/** Agrega una foto al FormData para subirla al API. */
export function appendPhoto(form: FormData, field: string, photo: LocalPhoto): void {
  form.append(field, {
    uri: photo.uri,
    name: photo.name,
    type: photo.mimeType,
  } as unknown as Blob);
}

/** Agrega una nota de voz al FormData para subirla al API. */
export function appendAudio(form: FormData, field: string, audio: LocalAudio): void {
  form.append(field, {
    uri: audio.uri,
    name: audio.name,
    type: audio.mimeType,
  } as unknown as Blob);
}

/**
 * Coordenadas actuales del dispositivo. Pide permiso solo cuando se usa.
 * Devuelve null si no hay permiso o falla (la solicitud sigue sin GPS).
 */
export async function getCurrentCoords(): Promise<{
  latitude: number;
  longitude: number;
} | null> {
  try {
    const { status } = await Location.requestForegroundPermissionsAsync();

    if (status !== 'granted') {
      return null;
    }

    const position = await Location.getCurrentPositionAsync({});
    return {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
    };
  } catch {
    return null;
  }
}
